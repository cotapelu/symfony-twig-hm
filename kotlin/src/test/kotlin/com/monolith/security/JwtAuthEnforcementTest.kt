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
import org.junit.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

class JwtAuthEnforcementTest {

    private val jwtSecret = "test-secret"
    private fun createToken(subject: String): String {
        return JWT.create()
            .withIssuer("kotlin-backend")
            .withSubject(subject)
            .sign(Algorithm.HMAC256(jwtSecret))
    }

    @Test
    fun `protected route returns 401 without token`() = testApplication {
        application {
            install(Authentication) {
                jwt("auth-jwt") {
                    realm = "Test"
                    verifier(
                        com.auth0.jwt.JWT.require(com.auth0.jwt.algorithms.Algorithm.HMAC256(jwtSecret))
                            .withIssuer("kotlin-backend")
                            .build()
                    )
                    validate { credential ->
                        if (credential.payload.subject != null) {
                            com.auth0.jwt.interfaces.JWTPrincipal(credential.payload)
                        } else {
                            null
                        }
                    }
                }
            }
            routing {
                authenticate("auth-jwt") {
                    get("/protected") {
                        call.respond("OK")
                    }
                }
                get("/public") {
                    call.respond("Public")
                }
            }
        }

        // Request without token
        val responseNoToken = client.get("/protected")
        assertEquals(HttpStatusCode.Unauthorized, responseNoToken.status)

        // Public endpoint should be accessible
        val responsePublic = client.get("/public")
        assertEquals(HttpStatusCode.OK, responsePublic.status)
    }

    @Test
    fun `protected route returns 200 with valid token`() = testApplication {
        application {
            install(Authentication) {
                jwt("auth-jwt") {
                    realm = "Test"
                    verifier(
                        com.auth0.jwt.JWT.require(com.auth0.jwt.algorithms.Algorithm.HMAC256(jwtSecret))
                            .withIssuer("kotlin-backend")
                            .build()
                    )
                    validate { credential ->
                        if (credential.payload.subject != null) {
                            com.auth0.jwt.interfaces.JWTPrincipal(credential.payload)
                        } else {
                            null
                        }
                    }
                }
            }
            routing {
                authenticate("auth-jwt") {
                    get("/protected") {
                        val principal = call.principal<com.auth0.jwt.interfaces.JWTPrincipal>()
                        val userId = principal?.subject
                        call.respond(mapOf("user_id" to userId))
                    }
                }
            }
        }

        val token = createToken("user123")
        val response = client.get("/protected") {
            header(HttpHeaders.Authorization, "Bearer $token")
        }
        assertEquals(HttpStatusCode.OK, response.status)
        val body = response.bodyAsText()
        assertTrue(body.contains("user123"))
    }

    @Test
    fun `principal extraction returns correct subject`() = testApplication {
        application {
            install(Authentication) {
                jwt("auth-jwt") {
                    verifier(
                        com.auth0.jwt.JWT.require(com.auth0.jwt.algorithms.Algorithm.HMAC256(jwtSecret))
                            .withIssuer("kotlin-backend")
                            .build()
                    )
                    validate { credential ->
                        com.auth0.jwt.interfaces.JWTPrincipal(credential.payload)
                    }
                }
            }
            routing {
                authenticate("auth-jwt") {
                    get("/me") {
                        val principal = call.principal<com.auth0.jwt.interfaces.JWTPrincipal>()
                        call.respond(mapOf("subject" to principal?.subject))
                    }
                }
            }
        }

        val token = createToken("alice")
        val response = client.get("/me") {
            header(HttpHeaders.Authorization, "Bearer $token")
        }
        assertEquals(HttpStatusCode.OK, response.status)
        val json = response.bodyAsText()
        assertTrue(json.contains("\"subject\":\"alice\""))
    }
}
