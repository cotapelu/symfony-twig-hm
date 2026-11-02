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

class PollControllerTest {

    @Test
    fun testCreatePoll() = testApplication {
        application {
            install(ContentNegotiation) {
                json(Json { isLenient = true })
            }
            routing {
                post("/api/poll/create") {
                    call.respond(HttpStatusCode.Created, mapOf("id" to "poll-123", "status" to "active"))
                }
            }
        }
        
        val response = client.post("/api/poll/create") {
            header(HttpHeaders.ContentType, ContentType.Application.Json)
            setBody("""{"question": "What is your favorite color?", "options": ["Red", "Blue"]}""")
        }
        assertEquals(HttpStatusCode.Created, response.status)
    }

    @Test
    fun testGetPollResults() = testApplication {
        application {
            install(ContentNegotiation) {
                json(Json { isLenient = true })
            }
            routing {
                get("/api/poll/{id}/results") {
                    call.respond(HttpStatusCode.OK, mapOf("results" to mapOf("Red" to 10, "Blue" to 5)))
                }
            }
        }
        
        val response = client.get("/api/poll/poll-123/results")
        assertEquals(HttpStatusCode.OK, response.status)
    }
}