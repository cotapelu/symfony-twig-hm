package identitymanagement

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class IdentitymanagementRepository {
    suspend fun findAll() = withContext(Dispatchers.IO) { emptyList<Any>() }
    suspend fun findById(id: String) = withContext(Dispatchers.IO) { null }
    suspend fun save(user: Any) = withContext(Dispatchers.IO) { user }
}
