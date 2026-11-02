package peertopeer

import java.io.InputStream
import java.io.OutputStream
import java.security.MessageDigest
import java.time.Instant
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap
import java.util.concurrent.Executors
import java.util.concurrent.TimeUnit
import kotlin.coroutines.coroutineContext
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class FileSharingService(
    private val storagePath: String,
    private val maxFileSize: Long = 10L * 1024 * 1024 * 1024, // 10GB
    private val allowedMimeTypes: Set<String> = setOf(
        "image/*", "video/*", "audio/*", "application/pdf",
        "application/zip", "text/*"
    )
) {
    private val files = ConcurrentHashMap<String, FileInfo>()
    private val shares = ConcurrentHashMap<String, ShareInfo>()
    private val executor = Executors.newFixedThreadPool(4)

    suspend fun uploadFile(
        inputStream: InputStream,
        request: UploadRequest,
        userId: String
    ): UploadResponse = withContext(Dispatchers.IO) {
        if (request.size > maxFileSize) {
            throw FileSharingException("File size exceeds maximum allowed size")
        }

        val fileId = UUID.randomUUID().toString()
        val storageFileName = "${fileId}_${request.fileName}"
        val storagePathFull = "$storagePath/$storageFileName"

        val checksum = calculateChecksum(inputStream)

        if (checksum != request.checksum) {
            throw FileSharingException("Checksum verification failed")
        }

        inputStream.reset()

        saveFile(inputStream, storagePathFull)

        val fileInfo = FileInfo(
            id = fileId,
            name = request.fileName,
            originalName = request.fileName,
            mimeType = request.mimeType,
            size = request.size,
            checksum = checksum,
            ownerId = userId,
            storagePath = storagePathFull,
            isEncrypted = request.encrypt,
            metadata = request.metadata
        )

        files[fileId] = fileInfo

        val uploadUrl = "/api/files/upload/$fileId"
        val expiresAt = Instant.now().plusSeconds(3600)

        UploadResponse(
            fileInfo = fileInfo,
            uploadUrl = uploadUrl,
            expiresAt = expiresAt
        )
    }

    suspend fun downloadFile(
        fileId: String,
        userId: String,
        range: String? = null
    ): InputStream = withContext(Dispatchers.IO) {
        val fileInfo = files[fileId]
            ?: throw FileSharingException("File not found")

        if (!canAccessFile(fileInfo, userId)) {
            throw FileSharingException("Access denied")
        }

        val updatedFile = fileInfo.copy(
            lastAccessedAt = Instant.now(),
            accessCount = fileInfo.accessCount + 1
        )
        files[fileId] = updatedFile

        openFile(fileInfo.storagePath)
    }

    suspend fun streamFile(
        fileId: String,
        userId: String,
        outputStream: OutputStream,
        bufferSize: Int = 8192
    ): Long = withContext(Dispatchers.IO) {
        val fileInfo = files[fileId]
            ?: throw FileSharingException("File not found")

        if (!canAccessFile(fileInfo, userId)) {
            throw FileSharingException("Access denied")
        }

        var totalBytes = 0L
        val buffer = ByteArray(bufferSize)
        val inputStream = openFile(fileInfo.storagePath)

        try {
            var bytesRead: Int
            while (inputStream.read(buffer).also { bytesRead = it } != -1) {
                outputStream.write(buffer, 0, bytesRead)
                totalBytes += bytesRead
            }
            totalBytes
        } finally {
            inputStream.close()
        }
    }

    suspend fun shareFile(request: ShareRequest, userId: String): ShareInfo = withContext(Dispatchers.IO) {
        val fileInfo = files[request.fileId]
            ?: throw FileSharingException("File not found")

        if (fileInfo.ownerId != userId) {
            throw FileSharingException("Only file owner can share")
        }

        val shareId = UUID.randomUUID().toString()
        val publicLink = if (request.shareType == ShareType.PUBLIC) {
            "/api/files/share/$shareId"
        } else null

        val shareInfo = ShareInfo(
            id = shareId,
            fileId = request.fileId,
            ownerId = userId,
            shareType = request.shareType,
            sharedWithId = request.sharedWithId,
            sharedWithEmail = request.sharedWithEmail,
            publicLink = publicLink,
            expiresAt = request.expiresAt,
            permissions = request.permissions
        )

        shares[shareId] = shareInfo
        shareInfo
    }

    suspend fun revokeShare(shareId: String, userId: String) = withContext(Dispatchers.IO) {
        val shareInfo = shares[shareId]
            ?: throw FileSharingException("Share not found")

        if (shareInfo.ownerId != userId) {
            throw FileSharingException("Only share owner can revoke")
        }

        shares.remove(shareId)
    }

    suspend fun searchFiles(request: FileSearchRequest): FileSearchResult = withContext(Dispatchers.IO) {
        var results = files.values.filter { file ->
            (request.query == null || file.name.contains(request.query, ignoreCase = true) ||
                    file.originalName.contains(request.query, ignoreCase = true)) &&
            (request.mimeType == null || file.mimeType.startsWith(request.mimeType)) &&
            (request.ownerId == null || file.ownerId == request.ownerId) &&
            (request.createdAfter == null || file.createdAt.isAfter(request.createdAfter)) &&
            (request.createdBefore == null || file.createdAt.isBefore(request.createdBefore))
        }

        val totalCount = results.size
        val offset = minOf(request.offset, totalCount)
        val limit = minOf(request.limit, totalCount - offset)

        results = results
            .sortedByDescending { it.createdAt }
            .drop(offset)
            .take(limit)

        FileSearchResult(
            files = results,
            totalCount = totalCount,
            hasMore = offset + limit < totalCount
        )
    }

    suspend fun deleteFile(fileId: String, userId: String) = withContext(Dispatchers.IO) {
        val fileInfo = files[fileId]
            ?: throw FileSharingException("File not found")

        if (fileInfo.ownerId != userId) {
            throw FileSharingException("Only file owner can delete")
        }

        deletePhysicalFile(fileInfo.storagePath)
        files.remove(fileId)

        shares.values.filter { it.fileId == fileId }.forEach { shares.remove(it.id) }
    }

    suspend fun getFileInfo(fileId: String): FileInfo? = withContext(Dispatchers.IO) {
        files[fileId]
    }

    suspend fun updateFileMetadata(
        fileId: String,
        metadata: Map<String, String>,
        userId: String
    ): FileInfo = withContext(Dispatchers.IO) {
        val fileInfo = files[fileId]
            ?: throw FileSharingException("File not found")

        if (fileInfo.ownerId != userId) {
            throw FileSharingException("Only file owner can update metadata")
        }

        val updatedFile = fileInfo.copy(
            metadata = fileInfo.metadata + metadata,
            updatedAt = Instant.now()
        )
        files[fileId] = updatedFile
        updatedFile
    }

    private fun canAccessFile(fileInfo: FileInfo, userId: String): Boolean {
        if (fileInfo.ownerId == userId) return true

        val userShares = shares.values.filter {
            it.fileId == fileInfo.id &&
            (it.sharedWithId == userId || it.sharedWithId == null) &&
            (it.expiresAt == null || it.expiresAt.isAfter(Instant.now()))
        }

        return userShares.isNotEmpty()
    }

    private fun calculateChecksum(inputStream: InputStream): String {
        val digest = MessageDigest.getInstance("SHA-256")
        val buffer = ByteArray(8192)
        var bytesRead: Int

        while (inputStream.read(buffer).also { bytesRead = it } != -1) {
            digest.update(buffer, 0, bytesRead)
        }

        return digest.digest().joinToString("") { "%02x".format(it) }
    }

    private fun saveFile(inputStream: InputStream, path: String) {
        java.io.File(path).outputStream().use { output ->
            inputStream.copyTo(output)
        }
    }

    private fun openFile(path: String): InputStream {
        return java.io.File(path).inputStream()
    }

    private fun deletePhysicalFile(path: String) {
        java.io.File(path).delete()
    }

    fun shutdown() {
        executor.shutdown()
        try {
            if (!executor.awaitTermination(60, TimeUnit.SECONDS)) {
                executor.shutdownNow()
            }
        } catch (e: InterruptedException) {
            executor.shutdownNow()
        }
    }
}
