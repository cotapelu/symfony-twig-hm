package emailservice

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class EmailserviceRepository {
    suspend fun findAll() = withContext(Dispatchers.IO) { emptyList<Any>() }
    suspend fun findById(id: String) = withContext(Dispatchers.IO) { null }
    suspend fun save(email: Any) = withContext(Dispatchers.IO) { email }
}
