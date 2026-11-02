package com.phuongxa.modules.education

import io.ktor.http.*
import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json

@Serializable
data class School(
    val id: String,
    val name: String,
    val type: String,
    val address: String,
    val students: Int,
    val teachers: Int
)

@Serializable
data class Course(
    val id: String,
    val name: String,
    val category: String,
    val duration: String,
    val participants: Int,
    val status: String
)

@Serializable
data class TrainingRecord(
    val id: String,
    val citizen: String,
    val course: String,
    val date: String,
    val status: String,
    val certificate: String
)

val schools = mutableListOf(
    School("1", "Trường Tiểu học Nguyễn Trãi", "Primary", "123 Nguyễn Trãi", 850, 45),
    School("2", "Trường THCS Lý Thường Kiệt", "Secondary", "456 Lý Thường Kiệt", 1200, 65),
    School("3", "Trường Mầm non Hoa Hồng", "Kindergarten", "789 Hoàng Quốc Việt", 320, 25)
)

val courses = mutableListOf(
    Course("1", "Nghề phổ thông", "Vocational", "3 tháng", 45, "Đang mở"),
    Course("2", "Tin học cơ bản", "IT", "2 tháng", 60, "Đang mở"),
    Course("3", "Ngoại ngữ (Anh)", "Language", "6 tháng", 80, "Sắp mở"),
    Course("4", "Kỹ năng mềm", "Soft Skills", "1 tháng", 35, "Đang mở")
)

val trainingRecords = mutableListOf(
    TrainingRecord("1", "Nguyễn Văn A", "Nghề phổ thông", "2026-01-15", "Hoàn thành", "Có"),
    TrainingRecord("2", "Trần Thị B", "Tin học cơ bản", "2026-02-01", "Đang học", "Chưa"),
    TrainingRecord("3", "Lê Văn C", "Ngoại ngữ (Anh)", "2026-01-20", "Hoàn thành", "Có"),
    TrainingRecord("4", "Phạm Thị D", "Kỹ năng mềm", "2026-02-10", "Đang học", "Chưa")
)

var idCounter = 5

fun Route.educationRoutes() {
    route("/api/education") {
        get("/schools") {
            call.respond(mapOf(
                "items" to schools,
                "total" to schools.size
            ))
        }

        get("/schools/{id}") {
            val id = call.parameters["id"]
            val school = schools.find { it.id == id }
            if (school != null) {
                call.respond(school)
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "School not found"))
            }
        }

        post("/schools") {
            val school = call.receive<School>()
            school.let {
                val newSchool = it.copy(id = idCounter++.toString())
                schools.add(newSchool)
                call.respond(HttpStatusCode.Created, newSchool)
            }
        }

        put("/schools/{id}") {
            val id = call.parameters["id"]
            val school = call.receive<School>()
            val index = schools.indexOfFirst { it.id == id }
            if (index >= 0) {
                schools[index] = school.copy(id = id)
                call.respond(schools[index])
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "School not found"))
            }
        }

        delete("/schools/{id}") {
            val id = call.parameters["id"]
            val removed = schools.removeIf { it.id == id }
            if (removed) {
                call.respond(mapOf("message" to "Deleted successfully"))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "School not found"))
            }
        }

        get("/courses") {
            val status = call.request.queryParameters["status"]
            val filteredCourses = if (status != null) {
                courses.filter { it.status == status }
            } else {
                courses
            }
            call.respond(mapOf(
                "items" to filteredCourses,
                "total" to filteredCourses.size
            ))
        }

        get("/courses/{id}") {
            val id = call.parameters["id"]
            val course = courses.find { it.id == id }
            if (course != null) {
                call.respond(course)
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Course not found"))
            }
        }

        post("/courses") {
            val course = call.receive<Course>()
            val newCourse = course.copy(id = idCounter++.toString())
            courses.add(newCourse)
            call.respond(HttpStatusCode.Created, newCourse)
        }

        get("/training") {
            call.respond(mapOf(
                "items" to trainingRecords,
                "total" to trainingRecords.size
            ))
        }

        get("/training/{id}") {
            val id = call.parameters["id"]
            val record = trainingRecords.find { it.id == id }
            if (record != null) {
                call.respond(record)
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Record not found"))
            }
        }

        post("/training") {
            val record = call.receive<TrainingRecord>()
            val newRecord = record.copy(id = idCounter++.toString())
            trainingRecords.add(newRecord)
            call.respond(HttpStatusCode.Created, newRecord)
        }

        get("/statistics") {
            call.respond(mapOf(
                "totalSchools" to schools.size,
                "totalStudents" to schools.sumOf { it.students },
                "totalTeachers" to schools.sumOf { it.teachers },
                "trainingCourses" to courses.size,
                "certificatesIssued" to trainingRecords.count { it.certificate == "Có" },
                "inTraining" to trainingRecords.count { it.status == "Đang học" }
            ))
        }
    }
}
