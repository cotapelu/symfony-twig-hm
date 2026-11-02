package pushnotifications

import pushnotifications.PushNotificationsHandler
import io.ktor.server.application.*
import io.ktor.server.routing.*

fun Application.configurePushNotificationsRoutes(handler: PushNotificationsHandler) {
    routing {
        route("/api/push") {
            post("/register") {
                handler.registerDevice(call)
            }
            
            post("/send") {
                handler.sendNotification(call)
            }
            
            post("/send/{userId}") {
                handler.sendToUser(call)
            }
            
            delete("/devices") {
                handler.deactivateDevice(call)
            }
            
            post("/campaigns") {
                handler.createCampaign(call)
            }
        }
    }
}
