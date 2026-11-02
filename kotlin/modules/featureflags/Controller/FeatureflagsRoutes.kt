package featureflags

import featureflags.FeatureflagsHandler
import io.ktor.server.application.*
import io.ktor.server.routing.*

fun Application.configureFeatureflagsRoutes(handler: FeatureflagsHandler) {
    routing {
        route("/api/featureflags") {
            get {
                handler.getFlags()
            }
            post {
                handler.createFlag()
            }
            get("/evaluate") {
                handler.evaluateFlag()
            }
        }
    }
}
