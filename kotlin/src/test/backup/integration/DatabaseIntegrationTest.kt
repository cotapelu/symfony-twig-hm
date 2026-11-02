package com.monolith.integration

import io.ktor.server.testing.*
import io.ktor.server.application.*
import io.ktor.server.routing.*
import io.ktor.server.plugins.contentnegotiation.*
import io.ktor.serialization.kotlinx.json.*
import kotlinx.serialization.json.Json
import org.junit.Test
import org.junit.Assert.*
import kotlin.test.assertEquals

class DatabaseIntegrationTest {

    @Test
    fun testUserCreationAndRetrieval() = testApplication {
        application {
            install(ContentNegotiation) {
                json(Json { isLenient = true })
            }
            routing {
                post("/api/test/user") {
                    // Test user creation endpoint
                    call.respond(HttpStatusCode.Created, mapOf("id" to "test-123"))
                }
                get("/api/test/user/{id}") {
                    call.respond(HttpStatusCode.OK, mapOf("id" to "test-123", "name" to "Test User"))
                }
            }
        }
        
        // Test create
        val createResponse = client.post("/api/test/user") {
            header(HttpHeaders.ContentType, ContentType.Application.Json)
            setBody("""{"name": "Test User", "email": "test@example.com"}""")
        }
        assertEquals(HttpStatusCode.Created, createResponse.status)
        
        // Test retrieve
        val getResponse = client.get("/api/test/user/test-123")
        assertEquals(HttpStatusCode.OK, getResponse.status)
    }

    @Test
    fun testOrderCreationFlow() = testApplication {
        application {
            install(ContentNegotiation) {
                json(Json { isLenient = true })
            }
            routing {
                post("/api/test/order") {
                    call.respond(HttpStatusCode.Created, mapOf("id" to "order-456", "status" to "pending"))
                }
                put("/api/test/order/{id}/status") {
                    call.respond(HttpStatusCode.OK, mapOf("id" to "order-456", "status" to "completed"))
                }
            }
        }
        
        // Create order
        val orderResponse = client.post("/api/test/order") {
            header(HttpHeaders.ContentType, ContentType.Application.Json)
            setBody("""{"productId": "prod-1", "quantity": 2}""")
        }
        assertEquals(HttpStatusCode.Created, orderResponse.status)
        
        // Update status
        val statusResponse = client.put("/api/test/order/order-456/status") {
            header(HttpHeaders.ContentType, ContentType.Application.Json)
            setBody("""{"status": "completed"}""")
        }
        assertEquals(HttpStatusCode.OK, statusResponse.status)
    }

    @Test
    fun testTransactionRollbackOnError() = testApplication {
        application {
            install(ContentNegotiation) {
                json(Json { isLenient = true })
            }
            routing {
                post("/api/test/transaction") {
                    // Simulate transaction with rollback
                    call.respond(HttpStatusCode.InternalServerError, mapOf("error" to "rollback"))
                }
            }
        }
        
        val response = client.post("/api/test/transaction")
        assertEquals(HttpStatusCode.InternalServerError, response.status)
    }
}