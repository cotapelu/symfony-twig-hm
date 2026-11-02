package credentialmanager

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal

class CredentialManagerHandler(private val passwordService: PasswordManagerService) {

    suspend fun createEntry(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))

            val request = call.receive<CreateEntryRequest>()

            if (request.title.isBlank() || request.username.isBlank() || request.encryptedPassword.isBlank()) {
                return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Title, username, and password are required"))
            }

            val entry = passwordService.createEntry(
                userId = userId,
                title = request.title,
                username = request.username,
                encryptedPassword = request.encryptedPassword,
                website = request.website,
                notes = request.notes,
                categoryId = request.categoryId,
                tags = request.tags
            )

            call.respond(HttpStatusCode.Created, mapOf(
                "entry" to entry,
                "status" to "success",
                "message" to "Credential entry created successfully"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to create entry")))
        }
    }

    suspend fun getEntry(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))

            val entryId = call.parameters["id"]
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Entry ID required"))

            val entry = passwordService.getEntry(userId, entryId)
                ?: return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Entry not found"))

            call.respond(mapOf("entry" to entry))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get entry")))
        }
    }

    suspend fun searchEntries(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))

            val query = call.request.queryParams["query"]
            val categoryId = call.request.queryParams["categoryId"]
            val tags = call.request.queryParams["tags"]?.split(",")
            val isFavorite = call.request.queryParams["isFavorite"]?.toBooleanStrictOrNull()
            val limit = call.request.queryParams["limit"]?.toIntOrNull() ?: 50
            val offset = call.request.queryParams["offset"]?.toIntOrNull() ?: 0

            val result = passwordService.searchEntries(
                userId = userId,
                query = query,
                categoryId = categoryId,
                tags = tags,
                isFavorite = isFavorite,
                limit = limit,
                offset = offset
            )

            call.respond(mapOf(
                "entries" to result.entries,
                "totalCount" to result.totalCount,
                "hasMore" to result.hasMore
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun updateEntry(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))

            val entryId = call.parameters["id"]
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Entry ID required"))

            val request = call.receive<UpdateEntryRequest>()

            val updated = passwordService.updateEntry(
                userId = userId,
                entryId = entryId,
                title = request.title,
                username = request.username,
                encryptedPassword = request.encryptedPassword,
                website = request.website,
                notes = request.notes,
                categoryId = request.categoryId,
                tags = request.tags,
                isFavorite = request.isFavorite
            )

            if (!updated) {
                return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Entry not found"))
            }

            call.respond(mapOf(
                "status" to "success",
                "message" to "Entry updated successfully"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to update entry")))
        }
    }

    suspend fun deleteEntry(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))

            val entryId = call.parameters["id"]
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Entry ID required"))

            val deleted = passwordService.deleteEntry(userId, entryId)

            if (!deleted) {
                return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Entry not found"))
            }

            call.respond(mapOf(
                "status" to "success",
                "message" to "Entry deleted successfully"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to delete entry")))
        }
    }

    suspend fun generatePassword(call: ApplicationCall) {
        try {
            val request = call.receive<PasswordGeneratorRequest>()

            val result = passwordService.generatePassword(
                length = request.length,
                includeUppercase = request.includeUppercase,
                includeLowercase = request.includeLowercase,
                includeNumbers = request.includeNumbers,
                includeSymbols = request.includeSymbols,
                excludeSimilarCharacters = request.excludeSimilarCharacters,
                excludeAmbiguous = request.excludeAmbiguous
            )

            call.respond(mapOf(
                "password" to result.password,
                "strength" to result.strength.name,
                "crackTime" to result.crackTime,
                "score" to result.score
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Password generation failed")))
        }
    }

    suspend fun checkPasswordStrength(call: ApplicationCall) {
        try {
            val request = call.receive<CheckPasswordRequest>()

            val result = passwordService.checkPasswordStrength(request.password)

            call.respond(mapOf(
                "strength" to result.strength.name,
                "crackTime" to result.crackTime,
                "score" to result.score
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Password check failed")))
        }
    }

    suspend fun getPasswordHealth(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))

            val health = passwordService.getPasswordHealth(userId)

            call.respond(mapOf(
                "health" to mapOf(
                    "totalEntries" to health.totalEntries,
                    "compromisedCount" to health.compromisedCount,
                    "weakCount" to health.weakCount,
                    "reusedCount" to health.reusedCount,
                    "expiredCount" to health.expiredCount,
                    "score" to calculateHealthScore(health)
                ),
                "issues" to health.entries.map { mapOf(
                    "entryId" to it.entryId,
                    "title" to it.title,
                    "issue" to it.issue.name,
                    "severity" to it.severity.name
                )}
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Health check failed")))
        }
    }

    suspend fun toggleFavorite(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            val userId = principal.subject ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject"))

            val entryId = call.parameters["id"]
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Entry ID required"))

            val toggled = passwordService.toggleFavorite(userId, entryId)

            if (!toggled) {
                return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Entry not found"))
            }

            call.respond(mapOf(
                "status" to "success",
                "message" to "Favorite toggled"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Toggle failed")))
        }
    }

    private fun calculateHealthScore(health: PasswordHealthResult): Int = health.totalEntries
 {
        val total        if (total == 0) return 100
        val issues = health.compromisedCount * 40 + health.weakCount * 20 + health.reusedCount * 15 + health.expiredCount * 10
        return maxOf(0, 100 - (issues * 100 / (total * 40)))
    }
}

data class CreateEntryRequest(
    val title: String,
    val username: String,
    val encryptedPassword: String,
    val website: String? = null,
    val notes: String? = null,
    val categoryId: String? = null,
    val tags: List<String> = emptyList()
)

data class UpdateEntryRequest(
    val title: String? = null,
    val username: String? = null,
    val encryptedPassword: String? = null,
    val website: String? = null,
    val notes: String? = null,
    val categoryId: String? = null,
    val tags: List<String>? = null,
    val isFavorite: Boolean? = null
)
