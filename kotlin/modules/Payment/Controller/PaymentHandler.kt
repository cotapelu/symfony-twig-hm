package payment

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal

class PaymentHandler(
    private val paymentService: PaymentService,
    private val idempotencyService: IdempotencyService
) {

    private suspend fun requireAuthAndOwnership(call: ApplicationCall, paymentId: String): Pair<String, Payment>? {
        val principal = call.principal<JWTPrincipal>()
            ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return null }
        val userId = principal.subject
            ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return null }

        val payment = paymentService.getPayment(paymentId)
            ?: run { call.respond(HttpStatusCode.NotFound, mapOf("error" to "Payment not found")); return null }

        // Ownership check: user can only access their own payments
        // TODO: Add admin bypass if needed (check roles claim)
        if (payment.userId != userId) {
            call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Access denied: not your payment"))
            return null
        }

        return Pair(userId, payment)
    }

    suspend fun createPaymentIntent(call: ApplicationCall) {
        val idemKey = call.request.headers["Idempotency-Key"]
        if (idemKey == null) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Idempotency-Key header required"))
            return
        }
        // Check cache first
        val cached = idempotencyService.get<Map<String, Any>>(idemKey)
        if (cached != null) {
            val (body, status) = cached
            call.respond(status, body)
            return
        }
        try {
            val request = call.receive<CreatePaymentRequest>()
            val principal = call.principal<JWTPrincipal>()
                ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return }
            val userId = principal.subject ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return }

            val intent = paymentService.createPaymentIntent(
                userId = userId,
                bookingId = request.bookingId,
                bookingType = BookingType.valueOf(request.bookingType.uppercase()),
                amount = request.amount,
                currency = request.currency ?: "USD",
                paymentMethod = PaymentMethod.valueOf(request.paymentMethod.uppercase()),
                description = request.description
            )

            val responseBody = mapOf(
                "clientSecret" to intent.clientSecret,
                "paymentId" to intent.id,
                "amount" to intent.amount,
                "currency" to intent.currency,
                "status" to intent.status
            )
            idempotencyService.set(idemKey, responseBody, HttpStatusCode.Created)
            call.respond(HttpStatusCode.Created, responseBody)
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to create payment intent")))
        }
    }

    suspend fun confirmPayment(call: ApplicationCall) {
        val idemKey = call.request.headers["Idempotency-Key"]
        if (idemKey == null) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Idempotency-Key header required"))
            return
        }
        val cached = idempotencyService.get<Map<String, Any>>(idemKey)
        if (cached != null) {
            val (body, status) = cached
            call.respond(status, body)
            return
        }
        // Authentication & ownership check
        val paymentId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Payment ID required")); return }
        val authCheck = requireAuthAndOwnership(call, paymentId)
        if (authCheck == null) return
        try {
            val request = call.receive<ConfirmPaymentRequest>()
            val payment = paymentService.confirmPayment(paymentId, request.transactionId)
            val responseBody = mapOf(
                "payment" to payment,
                "status" to "success",
                "message" to "Payment confirmed successfully"
            )
            idempotencyService.set(idemKey, responseBody, HttpStatusCode.OK)
            call.respond(HttpStatusCode.OK, responseBody)
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to confirm payment")))
        }
    }

    suspend fun webhookStripe(call: ApplicationCall) {
        try {
            // In a real implementation, verify webhook signature
            val event = call.receive<Map<String, Any>>()

            when (event["type"]) {
                "payment_intent.succeeded" -> {
                    val intent = event["data"] as? Map<*, *>
                    val intentId = intent?.get("id") as? String
                    if (intentId != null) {
                        paymentService.confirmPaymentByStripeIntent(intentId, null)
                    }
                }
                "payment_intent.payment_failed" -> {
                    val intent = event["data"] as? Map<*, *>
                    val intentId = intent?.get("id") as? String
                    val error = ((intent?.get("last_payment_error") as? Map<*, *>)?.get("message")) as? String
                    if (intentId != null) {
                        val payment = paymentService.paymentRepository.findByStripePaymentIntentId(intentId)
                        if (payment != null) {
                            paymentService.failPayment(payment.id, error ?: "Payment failed")
                        }
                    }
                }
            }

            call.respond(HttpStatusCode.OK, mapOf("received" to true))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to e.message))
        }
    }

    suspend fun processRefund(call: ApplicationCall) {
        val idemKey = call.request.headers["Idempotency-Key"]
        if (idemKey == null) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Idempotency-Key header required"))
            return
        }
        val cached = idempotencyService.get<Map<String, Any>>(idemKey)
        if (cached != null) {
            val (body, status) = cached
            call.respond(status, body)
            return
        }
        // Authentication & ownership check
        val paymentId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Payment ID required")); return }
        val authCheck = requireAuthAndOwnership(call, paymentId)
        if (authCheck == null) return
        try {
            val request = call.receive<RefundRequest>()
            val refund = paymentService.processRefund(
                paymentId = paymentId,
                amount = request.amount,
                reason = request.reason
            )
            val responseBody = mapOf(
                "refund" to refund,
                "status" to "success"
            )
            idempotencyService.set(idemKey, responseBody, HttpStatusCode.Created)
            call.respond(HttpStatusCode.Created, responseBody)
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to process refund")))
        }
    }

    suspend fun getPaymentStatus(call: ApplicationCall) {
        try {
            val paymentId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Payment ID required")); return }
            // Authentication & ownership check
            val authCheck = requireAuthAndOwnership(call, paymentId)
            if (authCheck == null) return
            val status = paymentService.getPaymentStatus(paymentId)
            if (status != null) {
                call.respond(status)
            } else {
                // Should not happen since we already checked payment exists
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Payment not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get payment status")))
        }
    }

    suspend fun getUserPayments(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return }
            val userId = principal.subject ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return }

            val payments = paymentService.getUserPayments(userId)

            call.respond(mapOf(
                "payments" to payments,
                "count" to payments.size
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get payments")))
        }
    }

    suspend fun getUserPaymentStats(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return }
            val userId = principal.subject ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return }

            val stats = paymentService.getUserPaymentStats(userId)

            call.respond(stats)
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get payment stats")))
        }
    }

    suspend fun cancelPayment(call: ApplicationCall) {
        val idemKey = call.request.headers["Idempotency-Key"]
        if (idemKey == null) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Idempotency-Key header required"))
            return
        }
        val cached = idempotencyService.get<Map<String, Any>>(idemKey)
        if (cached != null) {
            val (body, status) = cached
            call.respond(status, body)
            return
        }
        // Authentication & ownership check
        val paymentId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Payment ID required")); return }
        val authCheck = requireAuthAndOwnership(call, paymentId)
        if (authCheck == null) return
        try {
            val payment = paymentService.cancelPayment(paymentId)
            val responseBody = mapOf(
                "payment" to payment,
                "status" to "cancelled"
            )
            idempotencyService.set(idemKey, responseBody, HttpStatusCode.OK)
            call.respond(HttpStatusCode.OK, responseBody)
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to cancel payment")))
        }
    }
}

data class CreatePaymentRequest(
    val bookingId: String,
    val bookingType: String,
    val amount: Double,
    val currency: String? = "USD",
    val paymentMethod: String,
    val description: String? = null
)

data class ConfirmPaymentRequest(
    val transactionId: String? = null
)

data class RefundRequest(
    val amount: Double? = null,
    val reason: String? = null
)
