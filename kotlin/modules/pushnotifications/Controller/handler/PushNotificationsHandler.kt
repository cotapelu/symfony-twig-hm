package pushnotifications

import pushnotifications.PushNotificationService
import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*

class PushNotificationsHandler(
    private val pushNotificationService: PushNotificationService
) {
    suspend fun registerDevice(call: ApplicationCall) {
        val params = call.receiveParameters()
        val userId = params["userId"] ?: return call.respondText("Missing userId")
        val deviceToken = params["deviceToken"] ?: return call.respondText("Missing deviceToken")
        val deviceType = params["deviceType"] ?: "ios"
        val osVersion = params["osVersion"]
        val appVersion = params["appVersion"]
        
        pushNotificationService.registerDevice(userId, deviceToken, deviceType, osVersion, appVersion)
        call.respond(mapOf("status" to "registered"))
    }

    suspend fun sendNotification(call: ApplicationCall) {
        val params = call.receiveParameters()
        val title = params["title"] ?: return call.respondText("Missing title")
        val body = params["body"] ?: return call.respondText("Missing body")
        val userIds = params["userIds"]?.split(",") ?: return call.respondText("Missing userIds")
        val platform = params["platform"] ?: "all"
        val type = params["type"] ?: "notification"
        val priority = params["priority"] ?: "normal"
        
        val notificationId = pushNotificationService.sendPush(
            title = title,
            body = body,
            userIds = userIds,
            platform = platform,
            type = type,
            priority = priority
        )
        
        call.respond(mapOf("notificationId" to notificationId, "status" to "sent"))
    }

    suspend fun sendToUser(call: ApplicationCall) {
        val userId = call.parameters["userId"] ?: return call.respondText("Missing userId")
        val params = call.receiveParameters()
        val title = params["title"] ?: return call.respondText("Missing title")
        val body = params["body"] ?: return call.respondText("Missing body")
        val type = params["type"] ?: "notification"
        
        val notificationId = pushNotificationService.sendToUser(
            userId = userId,
            title = title,
            body = body,
            type = type
        )
        
        call.respond(mapOf("notificationId" to notificationId, "status" to "sent"))
    }

    suspend fun deactivateDevice(call: ApplicationCall) {
        val deviceToken = call.receiveParameters()["deviceToken"] ?: return call.respondText("Missing deviceToken")
        pushNotificationService.deactivateDevice(deviceToken)
        call.respond(mapOf("status" to "deactivated"))
    }

    suspend fun createCampaign(call: ApplicationCall) {
        val params = call.receiveParameters()
        val name = params["name"] ?: return call.respondText("Missing name")
        val title = params["title"] ?: return call.respondText("Missing title")
        val body = params["body"] ?: return call.respondText("Missing body")
        val platform = params["platform"] ?: "all"
        
        pushNotificationService.sendCampaign(
            name = name,
            title = title,
            body = body,
            platform = platform,
            type = "campaign"
        )
        
        call.respond(mapOf("status" to "created"))
    }
}
