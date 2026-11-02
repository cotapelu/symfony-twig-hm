package parking

import org.jetbrains.exposed.dao.id.LongIdTable
import org.jetbrains.exposed.sql.javatime.datetime

object ParkingSpotsTable : LongIdTable("parking_spots") {
    val name = varchar("name", 255)
    val address = text("address")
    val latitude = double("latitude")
    val longitude = double("longitude")
    val totalSpots = integer("total_spots")
    val availableSpots = integer("available_spots")
    val hourlyRate = double("hourly_rate")
    val currency = varchar("currency", 3).default("VND")
    val openingTime = varchar("opening_time", 5).default("00:00")
    val closingTime = varchar("closing_time", 5).default("23:59")
    val isCovered = bool("is_covered").default(true)
    val hasEVCharging = bool("has_ev_charging").default(false)
    val securityFeatures = text("security_features").default("[]")
    val rating = double("rating").default(0.0)
    val status = varchar("status", 20).default("available")
    val createdAt = datetime("created_at")
}

object ParkingReservationsTable : LongIdTable("parking_reservations") {
    val spotId = long("spot_id").references(ParkingSpotsTable.id)
    val userId = varchar("user_id", 36)
    val startTime = datetime("start_time")
    val endTime = datetime("end_time")
    val totalAmount = double("total_amount")
    val status = varchar("status", 20).default("pending")
    val licensePlate = varchar("license_plate", 20).default("")
    val createdAt = datetime("created_at")
}

object ParkingSessionsTable : LongIdTable("parking_sessions") {
    val spotId = long("spot_id").references(ParkingSpotsTable.id)
    val userId = varchar("user_id", 36)
    val entryTime = datetime("entry_time")
    val exitTime = datetime("exit_time").nullable()
    val actualAmount = double("actual_amount").default(0.0)
    val status = varchar("status", 20).default("active")
    val licensePlate = varchar("license_plate", 20).default("")
    val createdAt = datetime("created_at")
}
