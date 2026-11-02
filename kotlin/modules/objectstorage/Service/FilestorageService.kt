package objectstorage

import objectstorage.FilestorageRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class FilestorageService(private val repository: FilestorageRepository) {
    suspend fun process(data: Map<String, Any>) = withContext(Dispatchers.IO) {
        repository.findAll()
    }

    suspend fun uploadFile(fileData: ByteArray, filename: String): String {
        return "file_uploaded_$filename"
    }

    suspend fun getFile(fileId: String): Any? {
        return repository.findById(fileId)
    }

    suspend fun deleteFile(fileId: String): Boolean {
        repository.delete(fileId)
        return true
    }
}
