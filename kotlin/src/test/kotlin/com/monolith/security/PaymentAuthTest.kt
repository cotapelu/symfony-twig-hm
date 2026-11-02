package com.monolith.security

import io.ktor.server.testing.*
import io.ktor.http.*
import io.ktor.server.application.*
import io.ktor.server.auth.*
import io.ktor.server.auth.jwt.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import com.auth0.jwt.JWT
import com.auth0.jwt.algorithms.Algorithm
import kotlin.test.*
import payment.*
import payment.idempotency.IdempotencyService // adjust package if needed

class PaymentAuthTest {

    private val jwtSecret = "test-secret-for-validation-only"

    private fun createToken(subject: String): String {
        return JWT.create()
            .withIssuer("kotlin-backend")
            .withSubject(subject)
            .sign(Algorithm.HMAC256(jwtSecret))
    }

    private fun setupTestApplication(paymentUserId: String): TestApplicationEngine {
        val repo = PaymentRepository()
        val paymentId = "pay_test_123"
        val payment = Payment(
            id = paymentId,
            userId = paymentUserId,
            bookingId = "book_1",
            bookingType = BookingType.HOTEL,
            amount = 100.0,
            currency = "USD",
            paymentMethod = PaymentMethod.CREDIT_CARD,
            status = PaymentStatus.PENDING
        )
        repo.save(payment)

        val service = PaymentService(repo)
        val handler = PaymentHandler(service, IdempotencyService())

        return TestApplicationEngine().apply {
            environment {
                // install plugins
                install(Authentication) {
                    jwt("auth-jwt") {
                        realm = "Test"
                        verifier(
                            JWT.require(Algorithm.HMAC256(jwtSecret))
                                .withIssuer("kotlin-backend")
                                .build()
                        )
                        validate { credential ->
                            if (credential.payload.subject != null) {
                                JWTPrincipal(credential.payload)
                            } else {
                                null
                            }
                        }
                    }
                }
            }
            application {
                routing {
                    // configure payment routes with handler
                    // Assuming extension function is available
                    // We need to import it: import payment.configurePaymentRoutes
                    // This import depends on module structure; adjust if needed
                    // For test, we can directly set up routes here:
                    route("/api/payments") {
                        get("/{id}/status") {
                            handler.getPaymentStatus(call)
                        }
                        post("/{id}/confirm") {
                            handler.confirmPayment(call)
                        }
                        post("/{id}/cancel") {
                            handler.cancelPayment(call)
                        }
                        post("/{id}/refund") {
                            handler.processRefund(call)
                        }
                        get("/my") {
                            handler.getUserPayments(call)
                        }
                        get("/stats") {
                            handler.getUserPaymentStats(call)
                        }
                        post("/intent") {
                            handler.createPaymentIntent(call)
                        }
                    }
                }
            }
        }
    }

    @Test
    fun `payment status returns 401 without token`() = with(setupTestApplication("alice")) {
        val response = client.get("/api/payments/pay_test_123/status")
        assertEquals(HttpStatusCode.Unauthorized, response.status)
    }

    @Test
    fun `payment status returns 403 for different user`() = with(setupTestApplication("alice")) {
        val token = createToken("bob")
        val response = client.get("/api/payments/pay_test_123/status") {
            header(HttpHeaders.Authorization, "Bearer $token")
        }
        assertEquals(HttpStatusCode.Forbidden, response.status)
    }

    @Test
    fun `payment status returns 200 for owner`() = with(setupTestApplication("alice")) {
        val token = createToken("alice")
        val response = client.get("/api/payments/pay_test_123/status") {
            header(HttpHeaders.Authorization, "Bearer $token")
        }
        assertEquals(HttpStatusCode.OK, response.status)
        // Optionally verify response contains expected keys
        val body = response.bodyAsText()
        assertTrue(body.contains("paymentId") || body.contains("pay_test_123"))
    }

    @Test
    fun `confirm payment returns 403 for non-owner`() = with(setupTestApplication("alice")) {
        val token = createToken("bob")
        // Need to send body for confirm
        val response = client.post("/api/payments/pay_test_123/confirm") {
            header(HttpHeaders.Authorization, "Bearer $token")
            header(HttpHeaders.ContentType, "application/json")
            setBody("""{"transactionId": "tx_999"}""")
        }
        assertEquals(HttpStatusCode.Forbidden, response.status)
    }

    @Test
    fun `confirm payment returns 200 for owner`() = with(setupTestApplication("alice")) {
        val token = createToken("alice")
        val response = client.post("/api/payments/pay_test_123/confirm") {
            header(HttpHeaders.Authorization, "Bearer $token")
            header(HttpHeaders.ContentType, "application/json")
            setBody("""{"transactionId": "tx_999"}""")
        }
        assertEquals(HttpStatusCode.OK, response.status)
    }

    @Test
    fun `cancel payment returns 403 for non-owner`() = with(setupTestApplication("alice")) {
        val token = createToken("bob")
        val response = client.post("/api/payments/pay_test_123/cancel") {
            header(HttpHeaders.Authorization, "Bearer $token")
        }
        assertEquals(HttpStatusCode.Forbidden, response.status)
    }

    @Test
    fun `refund payment returns 403 for non-owner`() = with(setupTestApplication("alice")) {
        val token = createToken("bob")
        val response = client.post("/api/payments/pay_test_123/refund") {
            header(HttpHeaders.Authorization, "Bearer $token")
            header(HttpHeaders.ContentType, "application/json")
            setBody("""{"amount": 50.0}""")
        }
        assertEquals(HttpStatusCode.Forbidden, response.status)
    }
}
