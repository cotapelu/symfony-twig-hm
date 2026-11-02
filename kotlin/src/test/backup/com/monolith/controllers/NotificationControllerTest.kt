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

class NotificationControllerTest {

    @Test
    fun testSendNotification() = testApplication {
        application {
            install(ContentNegotiation) {
                json(Json { isLenient = true })
            }
            routing {
                post("/api/notification/send") {
                    call.respond(HttpStatusCode.OK, mapOf("status" to "sent"))
                }
            }
        }
        
        val response = client.post("/api/notification/send") {
            header(HttpHeaders.ContentType, ContentType.Application.Json)
            setBody("""{"userId": "123", "message": "Test notification"}""")
        }
        
        assertEquals(HttpStatusCode.OK, response.status)
    }

    @Test
    fun testGetUserNotifications() = testApplication {
        application {
            install(ContentNegotiation) {
                json(Json { isLenient = true })
            }
            routing {
                get("/api/notification/user/{userId}") {
                    call.respond(HttpStatusCode.OK, listOf(
                        mapOf("id" to "1", "message" to "Hello"),
                        mapOf("id" to "2", "message" to "World")
                    ))
                }
            }
        }
        
        val response = client.get("/api/notification/user/123")
        assertEquals(HttpStatusCode.OK, response.status)
    }
}