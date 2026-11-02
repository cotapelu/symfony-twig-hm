package payment

import java.time.LocalDateTime
import java.util.UUID

class PaymentService(private val paymentRepository: PaymentRepository) {

fun createPaymentIntent(
	userId: String,
	bookingId: String,
	bookingType: BookingType,
	amount: Double,
	currency: String = "USD",
	paymentMethod: PaymentMethod,
	description: String? = null
): PaymentIntent {
	// Input validation
	require(amount > 0) { "Amount must be positive" }
	require(amount <= 1000000) { "Amount exceeds maximum limit" }
	require(userId.isNotBlank()) { "User ID is required" }
	require(bookingId.isNotBlank()) { "Booking ID is required" }
	require(currency.isNotBlank()) { "Currency is required" }

	// Generate a mock client secret (in real implementation, this would call Stripe/PayPal)
        val clientSecret = "pi_${UUID.randomUUID().toString().replace("-", "").substring(0, 24)}_secret_${UUID.randomUUID().toString().replace("-", "").substring(0, 24)}"
        
        val payment = Payment(
            userId = userId,
            bookingId = bookingId,
            bookingType = bookingType,
            amount = amount,
            currency = currency,
            paymentMethod = paymentMethod,
            status = PaymentStatus.PENDING,
            stripePaymentIntentId = if (paymentMethod == PaymentMethod.STRIPE) clientSecret.split("_secret_")[0] else null,
            description = description,
            metadata = mapOf(
                "clientSecret" to clientSecret,
                "intentCreatedAt" to LocalDateTime.now().toString()
            )
        )

        paymentRepository.save(payment)

        return PaymentIntent(
            id = payment.id,
            clientSecret = clientSecret,
            amount = amount,
            currency = currency,
            status = "requires_confirmation",
            bookingId = bookingId,
            bookingType = bookingType
        )
    }

    fun confirmPayment(paymentId: String, transactionId: String?): Payment {
        val payment = paymentRepository.findById(paymentId)
            ?: throw IllegalArgumentException("Payment not found")

        if (payment.status != PaymentStatus.PENDING && payment.status != PaymentStatus.PROCESSING) {
            throw IllegalStateException("Payment cannot be confirmed - current status: ${payment.status}")
        }

        val confirmedPayment = payment.copy(
            status = PaymentStatus.COMPLETED,
            transactionId = transactionId ?: UUID.randomUUID().toString(),
            completedAt = LocalDateTime.now(),
            updatedAt = LocalDateTime.now()
        )

        return paymentRepository.update(confirmedPayment)
    }

    fun confirmPaymentByStripeIntent(stripeIntentId: String, transactionId: String?): Payment {
        val payment = paymentRepository.findByStripePaymentIntentId(stripeIntentId)
            ?: throw IllegalArgumentException("Payment not found for Stripe intent: $stripeIntentId")

        return confirmPayment(payment.id, transactionId)
    }

    fun processRefund(paymentId: String, amount: Double? = null, reason: String? = null): Refund {
        val payment = paymentRepository.findById(paymentId)
            ?: throw IllegalArgumentException("Payment not found")

        if (payment.status != PaymentStatus.COMPLETED) {
            throw IllegalStateException("Only completed payments can be refunded")
        }

        val refundAmount = amount ?: payment.amount
        
        if (refundAmount > payment.amount) {
            throw IllegalArgumentException("Refund amount cannot exceed payment amount")
        }

        val refund = Refund(
            paymentId = paymentId,
            amount = refundAmount,
            reason = reason,
            status = "completed"
        )

        paymentRepository.saveRefund(refund)

        // Update payment status if full refund
        if (refundAmount >= payment.amount) {
            paymentRepository.update(payment.copy(
                status = PaymentStatus.REFUNDED,
                updatedAt = LocalDateTime.now()
            ))
        }

        return refund
    }

    fun failPayment(paymentId: String, reason: String): Payment {
        val payment = paymentRepository.findById(paymentId)
            ?: throw IllegalArgumentException("Payment not found")

        val failedPayment = payment.copy(
            status = PaymentStatus.FAILED,
            failureReason = reason,
            updatedAt = LocalDateTime.now()
        )

        return paymentRepository.update(failedPayment)
    }

    fun cancelPayment(paymentId: String): Payment {
        val payment = paymentRepository.findById(paymentId)
            ?: throw IllegalArgumentException("Payment not found")

        if (payment.status == PaymentStatus.COMPLETED) {
            throw IllegalStateException("Completed payments cannot be cancelled, use refund instead")
        }

        val cancelledPayment = payment.copy(
            status = PaymentStatus.CANCELLED,
            updatedAt = LocalDateTime.now()
        )

        return paymentRepository.update(cancelledPayment)
    }

    fun getPayment(paymentId: String): Payment? {
        return paymentRepository.findById(paymentId)
    }

    fun getUserPayments(userId: String): List<Payment> {
        return paymentRepository.findByUserId(userId)
    }

    fun getBookingPayments(bookingId: String): List<Payment> {
        return paymentRepository.findByBookingId(bookingId)
    }

    fun getPaymentStatus(paymentId: String): Map<String, Any>? {
        val payment = paymentRepository.findById(paymentId) ?: return null
        
        return mapOf(
            "paymentId" to payment.id,
            "status" to payment.status.name,
            "amount" to payment.amount,
            "currency" to payment.currency,
            "createdAt" to payment.createdAt.toString(),
            "completedAt" to (payment.completedAt?.toString() ?: "null"),
            "failureReason" to (payment.failureReason ?: "null")
        )
    }

    fun getUserPaymentStats(userId: String): Map<String, Any> {
        return paymentRepository.getUserPaymentStats(userId)
    }
}
