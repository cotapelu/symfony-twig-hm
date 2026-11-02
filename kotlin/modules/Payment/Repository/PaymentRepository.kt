package payment

import java.time.LocalDateTime
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

enum class PaymentStatus {
    PENDING, PROCESSING, COMPLETED, FAILED, REFUNDED, CANCELLED
}

enum class PaymentMethod {
    CREDIT_CARD, DEBIT_CARD, PAYPAL, STRIPE, BANK_TRANSFER
}

enum class BookingType {
    HOTEL, FLIGHT, CAR, RESTAURANT, PACKAGE, EVENT, EXPERIENCE
}

data class Payment(
    val id: String = UUID.randomUUID().toString(),
    val userId: String,
    val bookingId: String,
    val bookingType: BookingType,
    val amount: Double,
    val currency: String = "USD",
    val paymentMethod: PaymentMethod,
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
    val id: String = UUID.randomUUID().toString(),
    val clientSecret: String,
    val amount: Double,
    val currency: String,
    val status: String,
    val bookingId: String,
    val bookingType: BookingType
)

data class Refund(
    val id: String = UUID.randomUUID().toString(),
    val paymentId: String,
    val amount: Double,
    val reason: String?,
    val status: String,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

class PaymentRepository {
    private val payments = ConcurrentHashMap<String, Payment>()
    private val refunds = ConcurrentHashMap<String, Refund>()

    fun save(payment: Payment): Payment {
        payments[payment.id] = payment
        return payment
    }

    fun findById(id: String): Payment? {
        return payments[id]
    }

    fun findByUserId(userId: String): List<Payment> {
        return payments.values.filter { it.userId == userId }
    }

    fun findByBookingId(bookingId: String): List<Payment> {
        return payments.values.filter { it.bookingId == bookingId }
    }

    fun findByStripePaymentIntentId(intentId: String): Payment? {
        return payments.values.find { it.stripePaymentIntentId == intentId }
    }

    fun findByPaypalOrderId(orderId: String): Payment? {
        return payments.values.find { it.paypalOrderId == orderId }
    }

    fun update(payment: Payment): Payment {
        payments[payment.id] = payment
        return payment
    }

    fun saveRefund(refund: Refund): Refund {
        refunds[refund.id] = refund
        return refund
    }

    fun findRefundsByPaymentId(paymentId: String): List<Refund> {
        return refunds.values.filter { it.paymentId == paymentId }
    }

    fun getUserPaymentStats(userId: String): Map<String, Any> {
        val userPayments = findByUserId(userId)
        return mapOf(
            "totalPayments" to userPayments.size,
            "totalAmount" to userPayments.filter { it.status == PaymentStatus.COMPLETED }.sumOf { it.amount },
            "pendingPayments" to userPayments.filter { it.status == PaymentStatus.PENDING }.size,
            "failedPayments" to userPayments.filter { it.status == PaymentStatus.FAILED }.size,
            "refundedAmount" to userPayments.filter { it.status == PaymentStatus.REFUNDED }.sumOf { it.amount }
        )
    }
}
