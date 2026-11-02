package featureflags

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class FeatureflagsRepository {
    suspend fun findAll() = withContext(Dispatchers.IO) { emptyList<Any>() }
    suspend fun findById(id: String) = withContext(Dispatchers.IO) { null }
    suspend fun save(flag: Any) = withContext(Dispatchers.IO) { flag }
}
