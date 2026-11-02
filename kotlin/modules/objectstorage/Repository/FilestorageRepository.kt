package objectstorage

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class FilestorageRepository {
    suspend fun findAll() = withContext(Dispatchers.IO) { emptyList<Any>() }
    suspend fun findById(id: String) = withContext(Dispatchers.IO) { null }
    suspend fun save(file: Any) = withContext(Dispatchers.IO) { file }
    suspend fun delete(id: String) = withContext(Dispatchers.IO) { }
}
