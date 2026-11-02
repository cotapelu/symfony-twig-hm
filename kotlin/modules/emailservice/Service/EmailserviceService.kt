package emailservice

import emailservice.EmailserviceRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class EmailserviceService(private val repository: EmailserviceRepository) {
    suspend fun process(data: Map<String, Any>) = withContext(Dispatchers.IO) {
        repository.findAll()
    }

    suspend fun sendEmail(to: String, subject: String, body: String): Boolean {
        return true
    }

    suspend fun createTemplate(name: String, content: String): String {
        return "template_created"
    }
}
