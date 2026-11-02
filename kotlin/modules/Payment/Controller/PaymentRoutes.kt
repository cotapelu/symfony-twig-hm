package payment

import io.ktor.server.application.*
import io.ktor.server.routing.*

fun Application.configurePaymentRoutes(handler: PaymentHandler) {
    routing {
        route("/api/payments") {
            // Create payment intent
            post("/intent") {
                handler.createPaymentIntent(call)
            }

            // Get user payments
            get("/my") {
                handler.getUserPayments(call)
            }

            // Get payment stats
            get("/stats") {
                handler.getUserPaymentStats(call)
            }

            // Payment operations by ID
            route("/{id}") {
                // Get payment status
                get("/status") {
                    handler.getPaymentStatus(call)
                }

                // Confirm payment
                post("/confirm") {
                    handler.confirmPayment(call)
                }

                // Cancel payment
                post("/cancel") {
                    handler.cancelPayment(call)
                }

                // Process refund
                post("/refund") {
                    handler.processRefund(call)
                }
            }
        }

        // Stripe webhook endpoint
        post("/webhooks/stripe") {
            handler.webhookStripe(call)
        }
    }
}
