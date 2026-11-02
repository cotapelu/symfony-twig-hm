package emailservice

import emailservice.EmailserviceHandler
import io.ktor.server.application.*
import io.ktor.server.routing.*

fun Application.configureEmailServiceRoutes(handler: EmailserviceHandler) {
    routing {
        route("/api/email") {
            post("/send") {
                handler.sendEmail()
            }
            get("/templates") {
                handler.getTemplates()
            }
            post("/templates") {
                handler.createTemplate()
            }
            get("/campaigns") {
                handler.getCampaigns()
            }
            post("/campaigns") {
                handler.createCampaign()
            }
        }
    }
}
