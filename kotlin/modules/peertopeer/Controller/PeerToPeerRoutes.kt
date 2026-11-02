package peertopeer

import peertopeer.FileSharingService
import peertopeer.ShareType
import peertopeer.SharePermissions
import io.ktor.http.*
import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import com.auth0.jwt.interfaces.JWTPrincipal
import java.io.InputStream
import java.util.UUID

private fun authenticate(call: ApplicationCall): Boolean {
	val authHeader = call.request.headers["Authorization"]
	return authHeader != null && authHeader.startsWith("Bearer ") && authHeader.length > 7
}

private fun requireAuth(call: ApplicationCall) {
	if (!authenticate(call)) {
		call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
		throw IllegalArgumentException("Unauthorized")
	}
}

private fun getUserId(call: ApplicationCall): String? {
    val principal = call.principal<JWTPrincipal>() ?: return null
    return principal.subject
}

fun Application.configurePeerToPeerRouting(fileSharingService: FileSharingService) {
	routing {
		get("/api/peertopeer/files") {
			requireAuth(call)
            val userId = getUserId(call) ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            val files = fileSharingService.listFiles(userId, 50, 0)
            call.respond(files)
        }

        get("/api/peertopeer/files/{id}") {
            requireAuth(call)
            val fileId = call.parameters["id"] ?: ""
            try {
                val file = fileSharingService.getFile(fileId)
                call.respond(file)
            } catch (e: Exception) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to e.message))
            }
        }

        post("/api/peertopeer/files/upload") {
            requireAuth(call)
            try {
                val userId = getUserId(call) ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                val multipartData = call.receiveMultipart()
                val fileInfo = fileSharingService.processUpload(multipartData, userId)
                call.respond(HttpStatusCode.Created, fileInfo)
            } catch (e: Exception) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
            }
        }

        delete("/api/peertopeer/files/{id}") {
            val fileId = call.parameters["id"] ?: ""
            val userId = getUserId(call) ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            try {
                fileSharingService.deleteFile(fileId, userId)
                call.respond(mapOf("message" to "File deleted"))
            } catch (e: Exception) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
            }
        }

        post("/api/peertopeer/share") {
            try {
                val userId = getUserId(call) ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                val request = call.receive<ShareRequest>()
                val shareInfo = fileSharingService.createShareLink(request.fileId, userId, request.shareType, request.sharedWithId, request.expiresAt)
                call.respond(HttpStatusCode.Created, shareInfo)
            } catch (e: Exception) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
            }
        }

        get("/api/peertopeer/share/{id}") {
            val shareId = call.parameters["id"] ?: ""
            try {
                val share = fileSharingService.getShareInfo(shareId)
                call.respond(share)
            } catch (e: Exception) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to e.message))
            }
        }

        post("/api/peertopeer/download/{id}") {
            val shareId = call.parameters["id"] ?: ""
            val userId = getUserId(call) ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            try {
                val downloadInfo = fileSharingService.generateDownloadUrl(shareId, userId)
                call.respond(downloadInfo)
            } catch (e: Exception) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
            }
        }

        get("/api/peertopeer/search") {
            val query = call.request.queryParameters["query"]
            val mimeType = call.request.queryParameters["mimeType"]
            val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 20
            val offset = call.request.queryParameters["offset"]?.toIntOrNull() ?: 0

            val result = fileSharingService.searchFiles(query, mimeType, limit, offset)
            call.respond(result)
        }

        post("/api/peertopeer/files/{id}/access") {
            val fileId = call.parameters["id"] ?: ""
            val userId = getUserId(call) ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            try {
                fileSharingService.recordFileAccess(fileId, userId)
                call.respond(mapOf("message" to "Access recorded"))
            } catch (e: Exception) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
            }
        }
    }
}