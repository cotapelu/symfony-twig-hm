package credentialmanager

import java.time.Instant
import java.util.UUID

data class PasswordEntry(
    val id: String = UUID.randomUUID().toString(),
    val userId: String = "",
    val title: String,
    val username: String,
    val encryptedPassword: String,
    val website: String? = null,
    val notes: String? = null,
    val categoryId: String? = null,
    val tags: List<String> = emptyList(),
    val favicon: String? = null,
    val isFavorite: Boolean = false,
    val lastUsedAt: Instant? = null,
    val expiresAt: Instant? = null,
    val createdAt: Instant = Instant.now(),
    val updatedAt: Instant = Instant.now(),
    val passwordHistory: List<PasswordHistoryEntry> = emptyList()
)

data class PasswordHistoryEntry(
    val password: String,
    val changedAt: Instant
)

data class PasswordCategory(
    val id: String = UUID.randomUUID().toString(),
    val name: String,
    val icon: String? = null,
    val color: String? = null,
    val parentId: String? = null,
    val sortOrder: Int = 0,
    val createdAt: Instant = Instant.now()
)

data class PasswordGeneratorRequest(
    val length: Int = 16,
    val includeUppercase: Boolean = true,
    val includeLowercase: Boolean = true,
    val includeNumbers: Boolean = true,
    val includeSymbols: Boolean = true,
    val excludeSimilarCharacters: Boolean = false,
    val excludeAmbiguous: Boolean = false
)

data class PasswordGeneratorResult(
    val password: String,
    val strength: PasswordStrength,
    val crackTime: String,
    val score: Int
)

enum class PasswordStrength {
    VERY_WEAK,
    WEAK,
    FAIR,
    GOOD,
    STRONG,
    VERY_STRONG
}

data class PasswordHealthResult(
    val totalEntries: Int,
    val compromisedCount: Int,
    val weakCount: Int,
    val reusedCount: Int,
    val expiredCount: Int,
    val entries: List<PasswordHealthEntry>
)

data class PasswordHealthEntry(
    val entryId: String,
    val title: String,
    val issue: PasswordHealthIssue,
    val severity: HealthSeverity
)

enum class PasswordHealthIssue {
    COMPROMISED,
    WEAK,
    REUSED,
    EXPIRED,
    DUPLICATE
}

enum class HealthSeverity {
    LOW,
    MEDIUM,
    HIGH,
    CRITICAL
}

data class SearchRequest(
    val query: String? = null,
    val categoryId: String? = null,
    val tags: List<String>? = null,
    val isFavorite: Boolean? = null,
    val website: String? = null,
    val sortBy: String = "updatedAt",
    val sortOrder: String = "DESC",
    val limit: Int = 50,
    val offset: Int = 0
)

data class SearchResult(
    val entries: List<PasswordEntry>,
    val totalCount: Int,
    val hasMore: Boolean
)

class PasswordManagerException(message: String, cause: Throwable? = null) : Exception(message, cause)

data class CheckPasswordRequest(
    val password: String
)
