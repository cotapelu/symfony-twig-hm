package com.phuongxa.models

import org.jetbrains.exposed.sql.*
import org.jetbrains.exposed.sql.javatime.*
import org.jetbrains.exposed.dao.id.*
import org.jetbrains.exposed.dao.*
import java.util.*

// ==================== IDENTITY & ACCESS ====================

object KotlinUsers : UUIDTable("kotlin.users") {
    val email = varchar("email", 180).uniqueIndex()
    val phone = varchar("phone", 20).nullable()
    val passwordHash = varchar("password_hash", 255)
    val status = varchar("status", 20).default("active")
    val createdAt = datetime("created_at").defaultCurrentDateTime()
    val lastLoginAt = datetime("last_login_at").nullable()
}

class KotlinUser(id: EntityID<UUID>) : UUIDEntity(id) {
    var email by KotlinUsers.email
    var phone by KotlinUsers.phone
    var passwordHash by KotlinUsers.passwordHash
    var status by KotlinUsers.status
    var createdAt by KotlinUsers.createdAt
    var lastLoginAt by KotlinUsers.lastLoginAt
}

object KotlinSessions : UUIDTable("kotlin.sessions") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val device = varchar("device", 255).nullable()
    val ipAddress = varchar("ip_address", 50).nullable()
    val expiresAt = datetime("expires_at")
    val createdAt = datetime("created_at").defaultCurrentDateTime()
}

class KotlinSession(id: EntityID<UUID>) : UUIDEntity(id) {
    var userId by KotlinSessions.userId
    var device by KotlinSessions.device
    var ipAddress by KotlinSessions.ipAddress
    var expiresAt by KotlinSessions.expiresAt
    var createdAt by KotlinSessions.createdAt
}

object KotlinRoles : UUIDTable("kotlin.roles") {
    val name = varchar("name", 50).uniqueIndex()
    val permissions = json("permissions", List::class.java).default(emptyList())
    val createdAt = datetime("created_at").defaultCurrentDateTime()
}

class KotlinRole(id: EntityID<UUID>) : UUIDEntity(id) {
    var name by KotlinRoles.name
    var permissions by KotlinRoles.permissions
    var createdAt by KotlinRoles.createdAt
}

object KotlinUserRoles : UUIDTable("kotlin.user_roles") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val roleId = uuid("role_id").references(KotlinRoles.id)
}

object OAuthProviders : UUIDTable("kotlin.oauth_providers") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val provider = varchar("provider", 20)
    val providerUserId = varchar("provider_user_id", 100)
    val accessToken = text("access_token").nullable()
    val refreshToken = text("refresh_token").nullable()
    val expiresAt = datetime("expires_at").nullable()
    
    init {
        uniqueIndex(userId, provider)
    }
}

object RefreshTokens : UUIDTable("kotlin.refresh_tokens") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val token = varchar("token", 255).uniqueIndex()
    val expiresAt = datetime("expires_at")
    val createdAt = datetime("created_at").defaultCurrentDateTime()
}

// ==================== PAYMENTS ====================

object Payments : UUIDTable("kotlin.payments") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val type = varchar("type", 20)  // PAYMENT, REFUND
    val amount = long("amount_cents")
    val currency = varchar("currency", 3).default("VND")
    val status = varchar("status", 20).default("PENDING")  // PENDING, COMPLETED, FAILED
    val provider = varchar("provider", 50).nullable()  // STRIPE, MOMO, ZALOPAY
    val providerRef = varchar("provider_ref", 100).nullable()
    val metadata = json("metadata", Map::class.java).default(emptyMap())
    val createdAt = datetime("created_at").defaultCurrentDateTime()
    val updatedAt = datetime("updated_at").defaultCurrentDateTime()
}

class Payment(id: EntityID<UUID>) : UUIDEntity(id) {
    var userId by Payments.userId
    var type by Payments.type
    var amount by Payments.amount
    var currency by Payments.currency
    var status by Payments.status
    var provider by Payments.provider
    var providerRef by Payments.providerRef
    var metadata by Payments.metadata
    var createdAt by Payments.createdAt
    var updatedAt by Payments.updatedAt
}

object PaymentIntents : UUIDTable("kotlin.payment_intents") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val amount = long("amount_cents")
    val currency = varchar("currency", 3).default("VND")
    val status = varchar("status", 20).default("REQUIRES_PAYMENT")
    val clientSecret = varchar("client_secret", 255).uniqueIndex()
    val metadata = json("metadata", Map::class.java).default(emptyMap())
    val createdAt = datetime("created_at").defaultCurrentDateTime()
    val expiresAt = datetime("expires_at")
}

class PaymentIntent(id: EntityID<UUID>) : UUIDEntity(id) {
    var userId by PaymentIntents.userId
    var amount by PaymentIntents.amount
    var currency by PaymentIntents.currency
    var status by PaymentIntents.status
    var clientSecret by PaymentIntents.clientSecret
    var metadata by PaymentIntents.metadata
    var createdAt by PaymentIntents.createdAt
    var expiresAt by PaymentIntents.expiresAt
}

// ==================== PUSH NOTIFICATIONS ====================

object PushQueue : UUIDTable("kotlin.push_queue") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val title = varchar("title", 255)
    val body = text("body")
    val data = json("data", Map::class.java).default(emptyMap())
    val status = varchar("status", 20).default("PENDING")  // PENDING, SENT, FAILED
    val scheduledAt = datetime("scheduled_at").nullable()
    val sentAt = datetime("sent_at").nullable()
    val createdAt = datetime("created_at").defaultCurrentDateTime()
}

class PushNotification(id: EntityID<UUID>) : UUIDEntity(id) {
    var userId by PushQueue.userId
    var title by PushQueue.title
    var body by PushQueue.body
    var data by PushQueue.data
    var status by PushQueue.status
    var scheduledAt by PushQueue.scheduledAt
    var sentAt by PushQueue.sentAt
    var createdAt by PushQueue.createdAt
}

object DeviceChannels : UUIDTable("kotlin.device_channels") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val platform = varchar("platform", 20)  // ANDROID, IOS
    val endpoint = varchar("endpoint", 500)
    val status = varchar("status", 20).default("ACTIVE")
    val createdAt = datetime("created_at").defaultCurrentDateTime()
    val updatedAt = datetime("updated_at").defaultCurrentDateTime()
    
    init {
        uniqueIndex(userId, platform)
    }
}

// ==================== MONITORING ====================

object SystemMetrics : UUIDTable("kotlin.system_metrics") {
    val service = varchar("service", 50)
    val metricType = varchar("metric_type", 50)
    val value = double("value")
    val timestamp = datetime("timestamp").defaultCurrentDateTime()
    
    init {
        index(false, service, metricType, timestamp)
    }
}

object Alerts : UUIDTable("kotlin.alerts") {
    val severity = varchar("severity", 20)  // INFO, WARNING, ERROR, CRITICAL
    val service = varchar("service", 50)
    val message = text("message")
    val status = varchar("status", 20).default("ACTIVE")  // ACTIVE, ACKNOWLEDGED, RESOLVED
    val acknowledgedAt = datetime("acknowledged_at").nullable()
    val createdAt = datetime("created_at").defaultCurrentDateTime()
}

// ==================== WORKFLOW ====================

object Workflows : UUIDTable("kotlin.workflows") {
    val name = varchar("name", 255)
    val definition = json("definition", Map::class.java)  // Workflow definition
    val status = varchar("status", 20).default("DRAFT")  // DRAFT, ACTIVE, ARCHIVED
    val createdBy = uuid("created_by").references(KotlinUsers.id)
    val createdAt = datetime("created_at").defaultCurrentDateTime()
    val updatedAt = datetime("updated_at").defaultCurrentDateTime()
}

class Workflow(id: EntityID<UUID>) : UUIDEntity(id) {
    var name by Workflows.name
    var definition by Workflows.definition
    var status by Workflows.status
    var createdBy by Workflows.createdBy
    var createdAt by Workflows.createdAt
    var updatedAt by Workflows.updatedAt
}

object WorkflowInstances : UUIDTable("kotlin.workflow_instances") {
    val workflowId = uuid("workflow_id").references(Workflows.id)
    val status = varchar("status", 20).default("RUNNING")  // RUNNING, COMPLETED, FAILED, CANCELLED
    val state = json("state", Map::class.java).default(emptyMap())
    val currentStep = varchar("current_step", 50).nullable()
    val startedAt = datetime("started_at").defaultCurrentDateTime()
    val completedAt = datetime("completed_at").nullable()
}

class WorkflowInstance(id: EntityID<UUID>) : UUIDEntity(id) {
    var workflowId by WorkflowInstances.workflowId
    var status by WorkflowInstances.status
    var state by WorkflowInstances.state
    var currentStep by WorkflowInstances.currentStep
    var startedAt by WorkflowInstances.startedAt
    var completedAt by WorkflowInstances.completedAt
}

// ==================== FILE STORAGE ====================

object FileMetadata : UUIDTable("kotlin.file_metadata") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val bucket = varchar("bucket", 50)
    val key = varchar("key", 500)
    val size = long("size")
    val mimeType = varchar("mime_type", 100)
    val metadata = json("metadata", Map::class.java).default(emptyMap())
    val createdAt = datetime("created_at").defaultCurrentDateTime()
    
    init {
        index(false, userId, bucket)
    }
}

class FileMeta(id: EntityID<UUID>) : UUIDEntity(id) {
    var userId by FileMetadata.userId
    var bucket by FileMetadata.bucket
    var key by FileMetadata.key
    var size by FileMetadata.size
    var mimeType by FileMetadata.mimeType
    var metadata by FileMetadata.metadata
    var createdAt by FileMetadata.createdAt
}

// ==================== FEATURE FLAGS ====================

object FeatureFlags : UUIDTable("kotlin.feature_flags") {
    val key = varchar("key", 100).uniqueIndex()
    val environment = varchar("environment", 20).default("development")
    val enabled = bool("enabled").default(false)
    val rules = json("rules", Map::class.java).default(emptyMap())
    val createdAt = datetime("created_at").defaultCurrentDateTime()
    val updatedAt = datetime("updated_at").defaultCurrentDateTime()
    
    init {
        index(false, key, environment)
    }
}

class FeatureFlag(id: EntityID<UUID>) : UUIDEntity(id) {
    var key by FeatureFlags.key
    var environment by FeatureFlags.environment
    var enabled by FeatureFlags.enabled
    var rules by FeatureFlags.rules
    var createdAt by FeatureFlags.createdAt
    var updatedAt by FeatureFlags.updatedAt
}

// ==================== EMAIL ====================

object EmailQueue : UUIDTable("kotlin.email_queue") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val template = varchar("template", 100)
    val subject = varchar("subject", 255)
    val body = text("body")
    val status = varchar("status", 20).default("PENDING")  // PENDING, SENT, FAILED
    val sentAt = datetime("sent_at").nullable()
    val createdAt = datetime("created_at").defaultCurrentDateTime()
}

class EmailRecord(id: EntityID<UUID>) : UUIDEntity(id) {
    var userId by EmailQueue.userId
    var template by EmailQueue.template
    var subject by EmailQueue.subject
    var body by EmailQueue.body
    var status by EmailQueue.status
    var sentAt by EmailQueue.sentAt
    var createdAt by EmailQueue.createdAt
}

object EmailTemplates : UUIDTable("kotlin.email_templates") {
    val name = varchar("name", 100).uniqueIndex()
    val category = varchar("category", 50)
    val subject = varchar("subject", 255)
    val body = json("body", Map::class.java)  // {html, text}
    val createdAt = datetime("created_at").defaultCurrentDateTime()
    val updatedAt = datetime("updated_at").defaultCurrentDateTime()
}

// ==================== BOOKING SERVICES ====================

object HotelBookings : UUIDTable("kotlin.hotel_bookings") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val hotelId = varchar("hotel_id", 36)
    val roomType = varchar("room_type", 50)
    val checkIn = date("check_in")
    val checkOut = date("check_out")
    val totalAmount = long("total_amount_cents")
    val status = varchar("status", 20).default("PENDING")
    val metadata = json("metadata", Map::class.java).default(emptyMap())
    val createdAt = datetime("created_at").defaultCurrentDateTime()
}

object FlightTickets : UUIDTable("kotlin.flight_tickets") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val flightNumber = varchar("flight_number", 20)
    val passengerName = varchar("passenger_name", 255)
    val seatNumber = varchar("seat_number", 10).nullable()
    val priceAmount = long("price_amount_cents")
    val status = varchar("status", 20).default("BOOKED")
    val createdAt = datetime("created_at").defaultCurrentDateTime()
}

object CarRentals : UUIDTable("kotlin.car_rentals") {
    val userId = uuid("user_id").references(KotlinUsers.id)
    val carType = varchar("car_type", 50)
    val pickupLocation = varchar("pickup_location", 255)
    val dropoffLocation = varchar("dropoff_location", 255)
    val pickupDate = datetime("pickup_date")
    val dropoffDate = datetime("dropoff_date")
    val totalAmount = long("total_amount_cents")
    val status = varchar("status", 20).default("PENDING")
    val createdAt = datetime("created_at").defaultCurrentDateTime()
}

// ==================== REPOSITORY FUNCTIONS ====================

fun findUserByEmail(email: String): KotlinUser? {
    return KotlinUser.find { KotlinUsers.email eq email }.firstOrNull()
}

fun findActiveSessions(userId: UUID): List<KotlinSession> {
    return KotlinSession.find { 
        (KotlinSessions.userId eq userId) and (KotlinSessions.expiresAt greater datetimeNow()) 
    }.toList()
}

fun findUserPayments(userId: UUID, limit: Int = 20): List<Payment> {
    return Payment.find { 
        Payments.userId eq userId 
    }.orderBy(Payments.createdAt to Sort.DESC).limit(limit).toList()
}

fun findServiceAlerts(service: String, limit: Int = 50): List<Alert> {
    return Alert.find { 
        Alerts.service eq service 
    }.orderBy(Alerts.createdAt to Sort.DESC).limit(limit).toList()
}

fun getActiveFeatureFlags(environment: String): List<FeatureFlag> {
    return FeatureFlag.find { 
        (FeatureFlags.environment eq environment) and (FeatureFlags.enabled eq true) 
    }.toList()
}