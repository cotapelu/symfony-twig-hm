package identitymanagement

import io.ktor.server.application.*
import io.ktor.server.routing.*
import io.ktor.server.auth.*

fun Application.configureIdentitymanagementRoutes(handler: IdentitymanagementHandler) {
    routing {
        route("/api/auth") {
            post("/register") {
                handler.register(call)
            }

            post("/login") {
                handler.login(call)
            }

            authenticate("auth-jwt") {
                get("/me") {
                    handler.getCurrentUser(call)
                }

                get("/users") {
                    handler.getUsers(call)
                }
            }
        }
    }
}