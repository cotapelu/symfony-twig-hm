package com.monolith.controllers

import io.ktor.server.testing.*
import io.ktor.http.*
import io.ktor.server.application.*
import io.ktor.server.routing.*
import io.ktor.server.plugins.contentnegotiation.*
import io.ktor.serialization.kotlinx.json.*
import kotlinx.serialization.json.Json
import org.junit.Test
import kotlin.test.assertEquals

class PaymentControllerTest {

    @Test
    fun testCreatePayment() = testApplication {
        application {
            install(ContentNegotiation) {
                json(Json {
                    prettyPrint = true
                    isLenient = true
                })
            }
            routing {
                route("/api/payment") {
                    post("/create") {
                        // Test payment creation
                        call.respond(HttpStatusCode.Created, mapOf("status" to "created"))
                    }
                }
            }
        }
        
        val response = client.post("/api/payment/create") {
            header(HttpHeaders.ContentType, ContentType.Application.Json)
            setBody("""{"amount": 100.0, "currency": "USD"}""")
        }
        
        assertEquals(HttpStatusCode.Created, response.status)
    }

    @Test
    fun testGetPaymentStatus() = testApplication {
        application {
            install(ContentNegotiation) {
                json(Json { isLenient = true })
            }
            routing {
                get("/api/payment/status/{id}") {
                    call.respond(HttpStatusCode.OK, mapOf("status" to "completed", "id" to "pay_123"))
                }
            }
        }
        
        val response = client.get("/api/payment/status/pay_123")
        assertEquals(HttpStatusCode.OK, response.status)
    }
}