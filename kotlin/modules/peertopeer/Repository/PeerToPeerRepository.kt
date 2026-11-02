package peertopeer

import java.time.Instant
import java.util.UUID

interface PeerToPeerRepository {
    suspend fun saveFile(file: FileInfo): FileInfo
    suspend fun findFileById(id: String): FileInfo?
    suspend fun findFilesByOwner(ownerId: String): List<FileInfo>
    suspend fun searchFiles(request: FileSearchRequest): FileSearchResult
    suspend fun deleteFile(id: String): Boolean
    suspend fun updateFileAccess(id: String): Boolean
    
    suspend fun saveShare(share: ShareInfo): ShareInfo
    suspend fun findShareById(id: String): ShareInfo?
    suspend fun findSharesByFileId(fileId: String): List<ShareInfo>
    suspend fun findSharesByOwner(ownerId: String): List<ShareInfo>
    suspend fun findSharesByPublicLink(link: String): ShareInfo?
    suspend fun deleteShare(id: String): Boolean
    suspend fun incrementShareAccessCount(id: String): Boolean
}

class InMemoryPeerToPeerRepository : PeerToPeerRepository {
    private val files = mutableMapOf<String, FileInfo>()
    private val shares = mutableMapOf<String, ShareInfo>()
    private val publicLinks = mutableMapOf<String, String>()

    override suspend fun saveFile(file: FileInfo): FileInfo {
        val savedFile = file.copy(updatedAt = Instant.now())
        files[file.id] = savedFile
        return savedFile
    }

    override suspend fun findFileById(id: String): FileInfo? {
        return files[id]
    }

    override suspend fun findFilesByOwner(ownerId: String): List<FileInfo> {
        return files.values.filter { it.ownerId == ownerId }.sortedByDescending { it.createdAt }
    }

    override suspend fun searchFiles(request: FileSearchRequest): FileSearchResult {
        var results = files.values.toList()
        
        request.ownerId?.let { ownerId ->
            results = results.filter { it.ownerId == ownerId }
        }
        
        request.query?.let { query ->
            results = results.filter { 
                it.name.contains(query, ignoreCase = true) ||
                it.originalName.contains(query, ignoreCase = true)
            }
        }
        
        request.mimeType?.let { mime ->
            results = results.filter { it.mimeType.startsWith(mime) }
        }
        
        request.createdAfter?.let { after ->
            results = results.filter { it.createdAt.isAfter(after) }
        }
        
        request.createdBefore?.let { before ->
            results = results.filter { it.createdAt.isBefore(before) }
        }
        
        val totalCount = results.size
        val paginatedResults = results.drop(request.offset).take(request.limit)
        
        return FileSearchResult(
            files = paginatedResults,
            totalCount = totalCount,
            hasMore = (request.offset + request.limit) < totalCount
        )
    }

    override suspend fun deleteFile(id: String): Boolean {
        val fileShares = shares.values.filter { it.fileId == id }.map { it.id }
        fileShares.forEach { shares.remove(it) }
        return files.remove(id) != null
    }

    override suspend fun updateFileAccess(id: String): Boolean {
        val file = files[id] ?: return false
        files[id] = file.copy(
            lastAccessedAt = Instant.now(),
            accessCount = file.accessCount + 1
        )
        return true
    }

    override suspend fun saveShare(share: ShareInfo): ShareInfo {
        val savedShare = shares[share.id] ?: share
        shares[share.id] = savedShare
        
        if (share.shareType == ShareType.PUBLIC && share.publicLink != null) {
            publicLinks[share.publicLink] = share.id
        }
        
        return savedShare
    }

    override suspend fun findShareById(id: String): ShareInfo? {
        return shares[id]
    }

    override suspend fun findSharesByFileId(fileId: String): List<ShareInfo> {
        return shares.values.filter { it.fileId == fileId }.sortedByDescending { it.createdAt }
    }

    override suspend fun findSharesByOwner(ownerId: String): List<ShareInfo> {
        return shares.values.filter { it.ownerId == ownerId }.sortedByDescending { it.createdAt }
    }

    override suspend fun findSharesByPublicLink(link: String): ShareInfo? {
        val shareId = publicLinks[link] ?: return null
        return shares[shareId]
    }

    override suspend fun deleteShare(id: String): Boolean {
        val share = shares[id]
        if (share?.publicLink != null) {
            publicLinks.remove(share.publicLink)
        }
        return shares.remove(id) != null
    }

    override suspend fun incrementShareAccessCount(id: String): Boolean {
        val share = shares[id] ?: return false
        shares[id] = share.copy(accessCount = share.accessCount + 1)
        return true
    }
}
