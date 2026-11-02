package peertopeer

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal

class PeerToPeerHandler(private val fileService: FileSharingService) {

    private fun getUserIdFromToken(call: ApplicationCall): String? {
        val principal = call.principal<JWTPrincipal>() ?: return null
        return principal.subject
    }

    suspend fun uploadFile(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))

            val request = call.receive<FileUploadRequest>()

            if (request.fileName.isBlank() || request.mimeType.isBlank() || request.size <= 0) {
                return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Valid file name, mime type, and size required"))
            }

            val response = fileService.initiateUpload(
                userId = userId,
                fileName = request.fileName,
                mimeType = request.mimeType,
                size = request.size,
                checksum = request.checksum,
                encrypt = request.encrypt,
                metadata = request.metadata
            )

            call.respond(HttpStatusCode.Created, mapOf(
                "fileInfo" to mapOf(
                    "id" to response.fileInfo.id,
                    "name" to response.fileInfo.name,
                    "size" to response.fileInfo.size,
                    "mimeType" to response.fileInfo.mimeType
                ),
                "uploadUrl" to response.uploadUrl,
                "expiresAt" to response.expiresAt.toString()
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Upload initiation failed")))
        }
    }

    suspend fun completeUpload(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))

            val fileId = call.parameters["id"]
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "File ID required"))

            val completed = fileService.completeUpload(userId, fileId)

            if (!completed) {
                return call.respond(HttpStatusCode.NotFound, mapOf("error" to "File not found or upload not in progress"))
            }

            call.respond(mapOf(
                "status" to "success",
                "message" to "File uploaded successfully",
                "fileId" to fileId
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Upload completion failed")))
        }
    }

    suspend fun downloadFile(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call) ?: ""
            val fileId = call.parameters["id"]
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "File ID required"))

            val fileInfo = fileService.getFileInfo(userId, fileId)
                ?: return call.respond(HttpStatusCode.NotFound, mapOf("error" to "File not found"))

            val downloadUrl = fileService.generateDownloadUrl(fileId, userId)

            call.respond(mapOf(
                "fileInfo" to mapOf(
                    "id" to fileInfo.id,
                    "name" to fileInfo.name,
                    "size" to fileInfo.size,
                    "mimeType" to fileInfo.mimeType
                ),
                "downloadUrl" to downloadUrl,
                "expiresIn" to 3600
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Download request failed")))
        }
    }

    suspend fun shareFile(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))

            val request = call.receive<ShareFileRequest>()

            if (request.fileId.isBlank()) {
                return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "File ID required"))
            }

            val shareInfo = fileService.createShare(
                userId = userId,
                fileId = request.fileId,
                shareType = request.shareType,
                sharedWithId = request.sharedWithId,
                sharedWithEmail = request.sharedWithEmail,
                expiresAt = request.expiresAt,
                permissions = request.permissions
            )

            call.respond(HttpStatusCode.Created, mapOf(
                "share" to mapOf(
                    "id" to shareInfo.id,
                    "fileId" to shareInfo.fileId,
                    "shareType" to shareInfo.shareType.name,
                    "permissions" to shareInfo.permissions.name,
                    "publicLink" to shareInfo.publicLink,
                    "expiresAt" to shareInfo.expiresAt?.toString()
                ),
                "status" to "success"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Sharing failed")))
        }
    }

    suspend fun revokeShare(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))

            val shareId = call.parameters["id"]
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Share ID required"))

            val revoked = fileService.revokeShare(userId, shareId)

            if (!revoked) {
                return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Share not found"))
            }

            call.respond(mapOf(
                "status" to "success",
                "message" to "Share revoked successfully"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Revoke failed")))
        }
    }

    suspend fun getSharedFiles(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))

            val sharedWithMe = call.request.queryParams["type"] != "outgoing"
            val limit = call.request.queryParams["limit"]?.toIntOrNull() ?: 50
            val offset = call.request.queryParams["offset"]?.toIntOrNull() ?: 0

            val result = fileService.getSharedFiles(userId, sharedWithMe, limit, offset)

            call.respond(mapOf(
                "files" to result.files.map { mapOf(
                    "id" to it.id,
                    "name" to it.name,
                    "size" to it.size,
                    "mimeType" to it.mimeType,
                    "sharedAt" to it.createdAt.toString()
                )},
                "totalCount" to result.totalCount,
                "hasMore" to result.hasMore
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get shared files")))
        }
    }

    suspend fun searchFiles(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call) ?: ""

            val query = call.request.queryParams["query"]
            val mimeType = call.request.queryParams["mimeType"]
            val limit = call.request.queryParams["limit"]?.toIntOrNull() ?: 20
            val offset = call.request.queryParams["offset"]?.toIntOrNull() ?: 0

            val result = fileService.searchFiles(
                userId = userId,
                query = query,
                mimeType = mimeType,
                limit = limit,
                offset = offset
            )

            call.respond(mapOf(
                "files" to result.files.map { mapOf(
                    "id" to it.id,
                    "name" to it.name,
                    "size" to it.size,
                    "mimeType" to it.mimeType,
                    "ownerId" to it.ownerId
                )},
                "totalCount" to result.totalCount,
                "hasMore" to result.hasMore
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun deleteFile(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))

            val fileId = call.parameters["id"]
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "File ID required"))

            val deleted = fileService.deleteFile(userId, fileId)

            if (!deleted) {
                return call.respond(HttpStatusCode.NotFound, mapOf("error" to "File not found"))
            }

            call.respond(mapOf(
                "status" to "success",
                "message" to "File deleted successfully"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Delete failed")))
        }
    }

    suspend fun getStorageUsage(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))

            val usage = fileService.getStorageUsage(userId)

            call.respond(mapOf(
                "used" to usage.used,
                "total" to usage.total,
                "available" to usage.total - usage.used,
                "percentage" to (usage.used * 100 / usage.total)
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get storage usage")))
        }
    }

    suspend fun getRecentFiles(call: ApplicationCall) {
        try {
            val userId = getUserIdFromToken(call) ?: ""
            val limit = call.request.queryParams["limit"]?.toIntOrNull() ?: 10

            val files = fileService.getRecentFiles(userId, limit)

            call.respond(mapOf(
                "files" to files.map { mapOf(
                    "id" to it.id,
                    "name" to it.name,
                    "size" to it.size,
                    "mimeType" to it.mimeType,
                    "accessedAt" to it.lastAccessedAt?.toString() ?: it.createdAt.toString()
                )}
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get recent files")))
        }
    }
}

data class FileUploadRequest(
    val fileName: String,
    val mimeType: String,
    val size: Long,
    val checksum: String = "",
    val encrypt: Boolean = false,
    val metadata: Map<String, String> = emptyMap()
)

data class ShareFileRequest(
    val fileId: String,
    val shareType: ShareType = ShareType.PRIVATE,
    val sharedWithId: String? = null,
    val sharedWithEmail: String? = null,
    val expiresAt: java.time.Instant? = null,
    val permissions: SharePermissions = SharePermissions.READ
)