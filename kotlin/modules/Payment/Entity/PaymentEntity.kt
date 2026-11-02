package payment

import java.time.LocalDateTime

enum class PaymentStatus {
    PENDING, PROCESSING, COMPLETED, FAILED, REFUNDED, CANCELLED
}

enum class PaymentMethod {
    CREDIT_CARD, DEBIT_CARD, PAYPAL, STRIPE, BANK_TRANSFER, WALLET
}

enum class BookingType {
    HOTEL, FLIGHT, CAR, RESTAURANT, PACKAGE, EVENT, EXPERIENCE, PARKING, OTHER
}

data class Payment(
    val id: String = "",
    val userId: String = "",
    val bookingId: String = "",
    val bookingType: BookingType = BookingType.OTHER,
    val amount: Double = 0.0,
    val currency: String = "VND",
    val paymentMethod: PaymentMethod = PaymentMethod.CREDIT_CARD,
    val status: PaymentStatus = PaymentStatus.PENDING,
    val stripePaymentIntentId: String? = null,
    val paypalOrderId: String? = null,
    val transactionId: String? = null,
    val description: String? = null,
    val metadata: Map<String, String> = emptyMap(),
    val failureReason: String? = null,
    val createdAt: LocalDateTime = LocalDateTime.now(),
    val updatedAt: LocalDateTime = LocalDateTime.now(),
    val completedAt: LocalDateTime? = null
)

data class PaymentIntent(
    val id: String = "",
    val clientSecret: String = "",
    val amount: Double = 0.0,
    val currency: String = "VND",
    val status: String = "requires_confirmation",
    val bookingId: String = "",
    val bookingType: BookingType = BookingType.OTHER
)

data class Refund(
    val id: String = "",
    val paymentId: String = "",
    val amount: Double = 0.0,
    val reason: String? = null,
    val status: String = "pending",
    val createdAt: LocalDateTime = LocalDateTime.now()
)
