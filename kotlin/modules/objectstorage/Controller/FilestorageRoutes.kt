package objectstorage

import objectstorage.FilestorageHandler
import io.ktor.server.application.*
import io.ktor.server.routing.*

fun Application.configureFilestorageRoutes(handler: FilestorageHandler) {
    routing {
        route("/api/filestorage") {
            get("/files") {
                handler.getFiles()
            }
            get("/files/{id}") {
                handler.getFile()
            }
            post("/upload") {
                handler.uploadFile()
            }
            delete("/files/{id}") {
                handler.deleteFile()
            }
        }
    }
}
