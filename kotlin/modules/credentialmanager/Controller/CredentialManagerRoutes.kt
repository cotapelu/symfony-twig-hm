package credentialmanager

import credentialmanager.PasswordManagerService
import credentialmanager.PasswordEntry
import credentialmanager.PasswordCategory
import credentialmanager.PasswordGeneratorRequest
import credentialmanager.SearchRequest
import credentialmanager.CheckPasswordRequest
import io.ktor.http.*
import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import com.auth0.jwt.interfaces.JWTPrincipal

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

fun Application.configureCredentialManagerRouting(passwordManagerService: PasswordManagerService) {
	routing {
		get("/api/credentialmanager/vault") {
			requireAuth(call)
			val principal = call.principal<JWTPrincipal>()
				?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
			val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))
			try {
				val entries = passwordManagerService.getUserEntries(userId)
				call.respond(entries)
			} catch (e: Exception) {
				call.respond(HttpStatusCode.InternalServerError, mapOf("error" to e.message))
			}
		}

		post("/api/credentialmanager/vault") {
			try {
				val principal = call.principal<JWTPrincipal>()
					?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
				val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))
				val entry = call.receive<PasswordEntry>()
				entry.userId = userId
				val savedEntry = passwordManagerService.createEntry(entry, userId)
				call.respond(HttpStatusCode.Created, savedEntry)
			} catch (e: Exception) {
				call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
			}
		}

		get("/api/credentialmanager/vault/{id}") {
			val entryId = call.parameters["id"] ?: ""
			try {
				val entry = passwordManagerService.getEntry(entryId)
				call.respond(entry)
			} catch (e: Exception) {
				call.respond(HttpStatusCode.NotFound, mapOf("error" to e.message))
			}
		}

		put("/api/credentialmanager/vault/{id}") {
			try {
				val entryId = call.parameters["id"] ?: ""
				val entry = call.receive<PasswordEntry>()
				entry.id = entryId
				val updatedEntry = passwordManagerService.updateEntry(entryId, entry)
				call.respond(updatedEntry)
			} catch (e: Exception) {
				call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
			}
		}

		delete("/api/credentialmanager/vault/{id}") {
			val entryId = call.parameters["id"] ?: ""
			try {
				passwordManagerService.deleteEntry(entryId)
				call.respond(mapOf("message" to "Entry deleted"))
			} catch (e: Exception) {
				call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
			}
		}

		post("/api/credentialmanager/vault/search") {
			try {
				val principal = call.principal<JWTPrincipal>()
					?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
				val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))
				val request = call.receive<SearchRequest>()
				request.userId = userId
				val result = passwordManagerService.searchEntries(request)
				call.respond(result)
			} catch (e: Exception) {
				call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
			}
		}

		post("/api/credentialmanager/generate") {
			try {
				val request = call.receive<PasswordGeneratorRequest>()
				val result = passwordManagerService.generatePassword(request)
				call.respond(result)
			} catch (e: Exception) {
				call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
			}
		}

		get("/api/credentialmanager/categories") {
			val categories = passwordManagerService.getCategories()
			call.respond(categories)
		}

		post("/api/credentialmanager/categories") {
			try {
				val category = call.receive<PasswordCategory>()
				val savedCategory = passwordManagerService.createCategory(category)
				call.respond(HttpStatusCode.Created, savedCategory)
			} catch (e: Exception) {
				call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
			}
		}

		post("/api/credentialmanager/check") {
			try {
				val request = call.receive<CheckPasswordRequest>()
				val result = passwordManagerService.checkPasswordStrength(request.password)
				call.respond(result)
			} catch (e: Exception) {
				call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
			}
		}
	}
}