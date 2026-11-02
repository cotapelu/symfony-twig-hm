package objectstorage

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object StoredFiles : IntIdTable("stored_files") {
    val fileName = varchar("file_name", 255)
    val originalName = varchar("original_name", 255)
    val mimeType = varchar("mime_type", 100)
    val fileSize = long("file_size")
    val storageProvider = varchar("storage_provider", 50)
    val storagePath = varchar("storage_path", 500)
    val storageUrl = varchar("storage_url", 500)
    val md5Hash = varchar("md5_hash", 32)
    val bucket = varchar("bucket", 100).nullable()
    val userId = varchar("user_id", 255).nullable()
    val isPublic = bool("is_public").default(false)
    val expiresAt = datetime("expires_at").nullable()
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object PresignedUrls : IntIdTable("presigned_urls") {
    val fileId = reference("file_id", StoredFiles)
    val url = varchar("url", 500)
    val expiresAt = datetime("expires_at")
    val accessCount = integer("access_count").default(0)
    val maxAccessCount = integer("max_access_count").nullable()
    val createdAt = datetime("created_at")
}
