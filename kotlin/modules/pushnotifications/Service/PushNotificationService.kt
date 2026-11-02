package pushnotifications

import pushnotifications.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class PushNotificationService(
    private val deviceTokenRepository: DeviceTokenRepository,
    private val notificationRepository: NotificationRepository
) {
    suspend fun registerDevice(
        userId: String,
        deviceToken: String,
        deviceType: String,
        osVersion: String?,
        appVersion: String?
    ) = withContext(Dispatchers.IO) {
        deviceTokenRepository.registerDevice(userId, deviceToken, deviceType, osVersion, appVersion)
    }

    suspend fun sendPush(
        title: String,
        body: String,
        userIds: List<String>,
        platform: String = "all",
        type: String = "notification",
        priority: String = "normal",
        data: Map<String, Any>? = null
    ): Int = withContext(Dispatchers.IO) {
        val tokens = mutableListOf<String>()
        for (userId in userIds) {
            tokens.addAll(deviceTokenRepository.getTokensByUser(userId))
        }

        if (tokens.isEmpty()) {
            throw Exception("No device tokens found for users")
        }

        val tokensJson = tokens.joinToString(",")
        val userIdsJson = userIds.joinToString(",")
        val dataJson = data?.entries?.joinToString(",") { "\"${it.key}\":\"${it.value}\"" }

        val notificationId = notificationRepository.createNotification(
            title = title,
            body = body,
            data = dataJson?.let { "[$it]" },
            type = type,
            priority = priority,
            userIds = userIdsJson,
            tokens = tokensJson,
            platform = platform
        )

        val success = sendToFCM(tokens, title, body, data)

        if (success) {
            notificationRepository.markAsSent(notificationId)
        } else {
            notificationRepository.markAsFailed(notificationId, "Failed to send to FCM")
        }

        notificationId
    }

    suspend fun sendToUser(
        userId: String,
        title: String,
        body: String,
        type: String = "notification",
        data: Map<String, Any>? = null
    ): Int = withContext(Dispatchers.IO) {
        val tokens = deviceTokenRepository.getTokensByUser(userId)
        if (tokens.isEmpty()) {
            throw Exception("No device tokens found for user")
        }
        sendPush(title, body, listOf(userId), "all", type, "normal", data)
    }

    suspend fun sendCampaign(
        name: String,
        title: String,
        body: String,
        platform: String = "all",
        type: String = "campaign",
        scheduledAt: java.time.LocalDateTime? = null
    ) = withContext(Dispatchers.IO) {
    }

    suspend fun deactivateDevice(deviceToken: String) = withContext(Dispatchers.IO) {
        deviceTokenRepository.deactivateToken(deviceToken)
    }

    private suspend fun sendToFCM(
        tokens: List<String>,
        title: String,
        body: String,
        data: Map<String, Any>?
    ): Boolean = withContext(Dispatchers.IO) {
        return@withContext try {
            true
        } catch (e: Exception) {
            false
        }
    }

    private suspend fun sendToAPNs(
        tokens: List<String>,
        title: String,
        body: String,
        data: Map<String, Any>?
    ): Boolean = withContext(Dispatchers.IO) {
        return@withContext try {
            true
        } catch (e: Exception) {
            false
        }
    }
}
