package emailservice

import emailservice.EmailserviceService
import io.ktor.server.application.*

class EmailserviceHandler(private val service: EmailserviceService) {
    suspend fun sendEmail() {}
    suspend fun getTemplates() {}
    suspend fun createTemplate() {}
    suspend fun getCampaigns() {}
    suspend fun createCampaign() {}
}
