package objectstorage

import objectstorage.FilestorageService
import io.ktor.server.application.*
import io.ktor.server.response.*

class FilestorageHandler(private val service: FilestorageService) {
    suspend fun getFiles() {
        // Implementation
    }

    suspend fun getFile() {
        // Implementation
    }

    suspend fun uploadFile() {
        // Implementation
    }

    suspend fun deleteFile() {
        // Implementation
    }
}
