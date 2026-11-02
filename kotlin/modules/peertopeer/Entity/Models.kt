package peertopeer

import java.io.InputStream
import java.util.UUID
import java.time.Instant

data class FileInfo(
    val id: String = UUID.randomUUID().toString(),
    val name: String,
    val originalName: String,
    val mimeType: String,
    val size: Long,
    val checksum: String,
    val ownerId: String,
    val storagePath: String,
    val isEncrypted: Boolean = false,
    val encryptionKeyId: String? = null,
    val createdAt: Instant = Instant.now(),
    val updatedAt: Instant = Instant.now(),
    val lastAccessedAt: Instant? = null,
    val accessCount: Int = 0,
    val metadata: Map<String, String> = emptyMap()
)

data class ShareInfo(
    val id: String = UUID.randomUUID().toString(),
    val fileId: String,
    val ownerId: String,
    val shareType: ShareType,
    val sharedWithId: String? = null,
    val sharedWithEmail: String? = null,
    val publicLink: String? = null,
    val expiresAt: Instant? = null,
    val permissions: SharePermissions = SharePermissions.READ,
    val accessCount: Int = 0,
    val createdAt: Instant = Instant.now()
)

enum class ShareType {
    PRIVATE,
    PUBLIC,
    GROUP
}

enum class SharePermissions {
    READ,
    WRITE,
    ADMIN
}

data class UploadRequest(
    val fileName: String,
    val mimeType: String,
    val size: Long,
    val checksum: String,
    val encrypt: Boolean = false,
    val metadata: Map<String, String> = emptyMap()
)

data class UploadResponse(
    val fileInfo: FileInfo,
    val uploadUrl: String,
    val expiresAt: Instant
)

data class DownloadRequest(
    val fileId: String,
    val range: String? = null
)

data class ShareRequest(
    val fileId: String,
    val shareType: ShareType,
    val sharedWithId: String? = null,
    val sharedWithEmail: String? = null,
    val expiresAt: Instant? = null,
    val permissions: SharePermissions = SharePermissions.READ
)

data class FileSearchRequest(
    val query: String? = null,
    val mimeType: String? = null,
    val ownerId: String? = null,
    val createdAfter: Instant? = null,
    val createdBefore: Instant? = null,
    val limit: Int = 20,
    val offset: Int = 0
)

data class FileSearchResult(
    val files: List<FileInfo>,
    val totalCount: Int,
    val hasMore: Boolean
)

class FileSharingException(message: String, cause: Throwable? = null) : Exception(message, cause)
