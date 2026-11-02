package credentialmanager

import java.time.Instant
import java.util.UUID

interface CredentialRepository {
    suspend fun save(entry: PasswordEntry): PasswordEntry
    suspend fun findById(id: String): PasswordEntry?
    suspend fun findByUserId(userId: String): List<PasswordEntry>
    suspend fun findByTitle(userId: String, title: String): PasswordEntry?
    suspend fun findByWebsite(userId: String, website: String): List<PasswordEntry>
    suspend fun search(request: SearchRequest): SearchResult
    suspend fun delete(id: String): Boolean
    suspend fun deleteByUserId(userId: String): Int
    suspend fun countByUserId(userId: String): Int
    suspend fun findExpired(): List<PasswordEntry>
    suspend fun findExpiringSoon(days: Int): List<PasswordEntry>
    suspend fun findByCategory(userId: String, categoryId: String): List<PasswordEntry>
    suspend fun findFavorites(userId: String): List<PasswordEntry>
    suspend fun updateLastUsed(id: String): Boolean
    suspend fun saveCategory(category: PasswordCategory): PasswordCategory
    suspend fun findCategoryById(id: String): PasswordCategory?
    suspend fun findCategoriesByUserId(userId: String): List<PasswordCategory>
    suspend fun deleteCategory(id: String): Boolean
}

class InMemoryCredentialRepository : CredentialRepository {
    private val entries = mutableMapOf<String, PasswordEntry>()
    private val categories = mutableMapOf<String, PasswordCategory>()
    
    init {
        initializeDefaultCategories()
    }
    
    private fun initializeDefaultCategories() {
        val defaultCategories = listOf(
            PasswordCategory(name = "Login", icon = "login", color = "#4CAF50"),
            PasswordCategory(name = "Email", icon = "email", color = "#2196F3"),
            PasswordCategory(name = "Social", icon = "social", color = "#9C27B0"),
            PasswordCategory(name = "Financial", icon = "financial", color = "#FF9800"),
            PasswordCategory(name = "Work", icon = "work", color = "#607D8B"),
            PasswordCategory(name = "Other", icon = "other", color = "#9E9E9E")
        )
        defaultCategories.forEach { categories[it.id] = it }
    }

    override suspend fun save(entry: PasswordEntry): PasswordEntry {
        val savedEntry = entry.copy(updatedAt = Instant.now())
        entries[entry.id] = savedEntry
        return savedEntry
    }

    override suspend fun findById(id: String): PasswordEntry? {
        return entries[id]
    }

    override suspend fun findByUserId(userId: String): List<PasswordEntry> {
        return entries.values.filter { it.userId == userId }.toList()
    }

    override suspend fun findByTitle(userId: String, title: String): PasswordEntry? {
        return entries.values.find { it.userId == userId && it.title.equals(title, ignoreCase = true) }
    }

    override suspend fun findByWebsite(userId: String, website: String): List<PasswordEntry> {
        return entries.values.filter { 
            it.userId == userId && it.website?.contains(website, ignoreCase = true) == true 
        }.toList()
    }

    override suspend fun search(request: SearchRequest): SearchResult {
        var results = entries.values.filter { it.userId == request.userId ?: "" }
        
        request.query?.let { query ->
            results = results.filter { 
                it.title.contains(query, ignoreCase = true) ||
                it.username.contains(query, ignoreCase = true) ||
                it.website?.contains(query, ignoreCase = true) == true ||
                it.notes?.contains(query, ignoreCase = true) == true
            }
        }
        
        request.categoryId?.let { catId ->
            results = results.filter { it.categoryId == catId }
        }
        
        request.isFavorite?.let { fav ->
            results = results.filter { it.isFavorite == fav }
        }
        
        val totalCount = results.size
        val offsetResults = results.drop(request.offset).take(request.limit)
        
        return SearchResult(
            entries = offsetResults,
            totalCount = totalCount,
            hasMore = (request.offset + request.limit) < totalCount
        )
    }

    override suspend fun delete(id: String): Boolean {
        return entries.remove(id) != null
    }

    override suspend fun deleteByUserId(userId: String): Int {
        val toRemove = entries.values.filter { it.userId == userId }.map { it.id }
        toRemove.forEach { entries.remove(it) }
        return toRemove.size
    }

    override suspend fun countByUserId(userId: String): Int {
        return entries.values.count { it.userId == userId }
    }

    override suspend fun findExpired(): List<PasswordEntry> {
        val now = Instant.now()
        return entries.values.filter { it.expiresAt?.isBefore(now) == true }.toList()
    }

    override suspend fun findExpiringSoon(days: Int): List<PasswordEntry> {
        val now = Instant.now()
        val threshold = now.plusSeconds(days.toLong() * 24 * 60 * 60)
        return entries.values.filter { 
            it.expiresAt != null && it.expiresAt.isBefore(threshold) && it.expiresAt.isAfter(now)
        }.toList()
    }

    override suspend fun findByCategory(userId: String, categoryId: String): List<PasswordEntry> {
        return entries.values.filter { it.userId == userId && it.categoryId == categoryId }.toList()
    }

    override suspend fun findFavorites(userId: String): List<PasswordEntry> {
        return entries.values.filter { it.userId == userId && it.isFavorite }.toList()
    }

    override suspend fun updateLastUsed(id: String): Boolean {
        val entry = entries[id] ?: return false
        entries[id] = entry.copy(lastUsedAt = Instant.now())
        return true
    }

    override suspend fun saveCategory(category: PasswordCategory): PasswordCategory {
        categories[category.id] = category
        return category
    }

    override suspend fun findCategoryById(id: String): PasswordCategory? {
        return categories[id]
    }

    override suspend fun findCategoriesByUserId(userId: String): List<PasswordCategory> {
        return categories.values.toList()
    }

    override suspend fun deleteCategory(id: String): Boolean {
        return categories.remove(id) != null
    }
}

private val SearchRequest.userId: String?
    get() = null
