package pushnotifications

import pushnotifications.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.jetbrains.exposed.sql.*
import org.jetbrains.exposed.sql.transactions.transaction

class DeviceTokenRepository {
    suspend fun registerDevice(
        userId: String,
        deviceToken: String,
        deviceType: String,
        osVersion: String?,
        appVersion: String?
    ) = withContext(Dispatchers.IO) {
        transaction {
            val existing = DeviceTokens.select { (DeviceTokens.userId eq userId) and (DeviceTokens.deviceToken eq deviceToken) }
                .singleOrNull()

            if (existing != null) {
                DeviceTokens.update({ (DeviceTokens.userId eq userId) and (DeviceTokens.deviceToken eq deviceToken) }) {
                    it[DeviceTokens.isActive] = true
                    it[DeviceTokens.lastUsedAt] = java.time.LocalDateTime.now()
                    it[DeviceTokens.osVersion] = osVersion
                    it[DeviceTokens.appVersion] = appVersion
                    it[DeviceTokens.updatedAt] = java.time.LocalDateTime.now()
                }
            } else {
                DeviceTokens.insert {
                    it[DeviceTokens.userId] = userId
                    it[DeviceTokens.deviceToken] = deviceToken
                    it[DeviceTokens.deviceType] = deviceType
                    it[DeviceTokens.osVersion] = osVersion
                    it[DeviceTokens.appVersion] = appVersion
                    it[DeviceTokens.isActive] = true
                    it[DeviceTokens.createdAt] = java.time.LocalDateTime.now()
                    it[DeviceTokens.updatedAt] = java.time.LocalDateTime.now()
                }
            }
        }
    }

    suspend fun getTokensByUser(userId: String) = withContext(Dispatchers.IO) {
        transaction {
            DeviceTokens.select { (DeviceTokens.userId eq userId) and (DeviceTokens.isActive eq true) }
                .map { it[DeviceTokens.deviceToken] }
        }
    }

    suspend fun deactivateToken(deviceToken: String) = withContext(Dispatchers.IO) {
        transaction {
            DeviceTokens.update({ DeviceTokens.deviceToken eq deviceToken }) {
                it[DeviceTokens.isActive] = false
                it[DeviceTokens.updatedAt] = java.time.LocalDateTime.now()
            }
        }
    }
}

class NotificationRepository {
    suspend fun createNotification(
        title: String,
        body: String,
        data: String?,
        type: String,
        priority: String = "normal",
        userIds: String,
        tokens: String,
        platform: String
    ): Int = withContext(Dispatchers.IO) {
        transaction {
            val notificationId = Notifications.insert {
                it[Notifications.title] = title
                it[Notifications.body] = body
                it[Notifications.data] = data
                it[Notifications.type] = type
                it[Notifications.priority] = priority
                it[Notifications.userIds] = userIds
                it[Notifications.tokens] = tokens
                it[Notifications.platform] = platform
                it[Notifications.status] = "pending"
                it[Notifications.createdAt] = java.time.LocalDateTime.now()
            } get Notifications.id
            notificationId.value
        }
    }

    suspend fun markAsSent(notificationId: Int) = withContext(Dispatchers.IO) {
        transaction {
            Notifications.update({ Notifications.id eq notificationId }) {
                it[Notifications.status] = "sent"
                it[Notifications.sentAt] = java.time.LocalDateTime.now()
            }
        }
    }

    suspend fun markAsFailed(notificationId: Int, reason: String) = withContext(Dispatchers.IO) {
        transaction {
            Notifications.update({ Notifications.id eq notificationId }) {
                it[Notifications.status] = "failed"
                it[Notifications.failedAt] = java.time.LocalDateTime.now()
                it[Notifications.failureReason] = reason
            }
        }
    }
}
