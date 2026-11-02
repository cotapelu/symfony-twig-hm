package payment

import org.jetbrains.exposed.dao.id.UUIDTable
import org.jetbrains.exposed.sql.javatime.datetime

object PaymentsTable : UUIDTable("payments") {
    val userId = varchar("user_id", 36)
    val bookingId = varchar("booking_id", 36)
    val bookingType = varchar("booking_type", 20)
    val amount = double("amount")
    val currency = varchar("currency", 3).default("VND")
    val paymentMethod = varchar("payment_method", 20)
    val status = varchar("status", 20).default("PENDING")
    val stripePaymentIntentId = varchar("stripe_payment_intent_id", 100).nullable()
    val paypalOrderId = varchar("paypal_order_id", 100).nullable()
    val transactionId = varchar("transaction_id", 100).nullable()
    val description = text("description").nullable()
    val metadata = text("metadata").default("{}")
    val failureReason = text("failure_reason").nullable()
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
    val completedAt = datetime("completed_at").nullable()
}

object PaymentIntentsTable : UUIDTable("payment_intents") {
    val clientSecret = varchar("client_secret", 100)
    val amount = double("amount")
    val currency = varchar("currency", 3).default("VND")
    val status = varchar("status", 30).default("requires_confirmation")
    val bookingId = varchar("booking_id", 36)
    val bookingType = varchar("booking_type", 20)
    val createdAt = datetime("created_at")
}

object RefundsTable : UUIDTable("refunds") {
    val paymentId = uuid("payment_id").references(PaymentsTable.id)
    val amount = double("amount")
    val reason = text("reason").nullable()
    val status = varchar("status", 20).default("pending")
    val createdAt = datetime("created_at")
}
