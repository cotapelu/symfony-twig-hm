package objectstorage

import java.time.LocalDateTime
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

class FileRepository {
    private val files = ConcurrentHashMap<String, File>()

    fun save(file: File): File {
        files[file.id] = file
        return file
    }

    fun findById(id: String): File? {
        return files[id]
    }

    fun findByUserId(userId: String): List<File> {
        return files.values.filter { it.userId == userId }.sortedByDescending { it.createdAt }
    }

    fun findByFolderId(folderId: String): List<File> {
        return files.values.filter { it.folderId == folderId }
    }

    fun findByType(type: String): List<File> {
        return files.values.filter { it.type == type }
    }

    fun findByName(name: String): List<File> {
        return files.values.filter { it.name.contains(name, ignoreCase = true) }
    }

    fun updateStatus(id: String, status: String): File? {
        val file = files[id]
        if (file != null) {
            file.status = status
            file.updatedAt = LocalDateTime.now()
            files[id] = file
        }
        return file
    }

    fun delete(id: String) {
        files.remove(id)
    }

    fun getFileStats(userId: String): Map<String, Any> {
        val userFiles = files.values.filter { it.userId == userId }
        return mapOf(
            "totalFiles" to userFiles.size,
            "totalSize" to userFiles.sumOf { it.size },
            "activeFiles" to userFiles.count { it.status == "active" },
            "deletedFiles" to userFiles.count { it.status == "deleted" }
        )
    }
}

class FolderRepository {
    private val folders = ConcurrentHashMap<String, Folder>()

    fun save(folder: Folder): Folder {
        folders[folder.id] = folder
        return folder
    }

    fun findById(id: String): Folder? {
        return folders[id]
    }

    fun findByUserId(userId: String): List<Folder> {
        return folders.values.filter { it.userId == userId }.sortedBy { it.name }
    }

    fun findByParentId(parentId: String?): List<Folder> {
        return folders.values.filter { it.parentId == parentId }.sortedBy { it.name }
    }

    fun findByName(name: String): List<Folder> {
        return folders.values.filter { it.name.contains(name, ignoreCase = true) }
    }

    fun updateStatus(id: String, status: String): Folder? {
        val folder = folders[id]
        if (folder != null) {
            folder.status = status
            folder.updatedAt = LocalDateTime.now()
            folders[id] = folder
        }
        return folder
    }

    fun delete(id: String) {
        folders.remove(id)
    }
}

class StorageQuotaRepository {
    private val quotas = ConcurrentHashMap<String, StorageQuota>()

    fun save(quota: StorageQuota): StorageQuota {
        quotas[quota.id] = quota
        return quota
    }

    fun findById(id: String): StorageQuota? {
        return quotas[id]
    }

    fun findByUserId(userId: String): StorageQuota? {
        return quotas.values.find { it.userId == userId }
    }

    fun updateUsage(userId: String, usedSpace: Long): StorageQuota? {
        val quota = quotas.values.find { it.userId == userId }
        if (quota != null) {
            quota.usedSpace = usedSpace
            quota.updatedAt = LocalDateTime.now()
            quotas[quota.id] = quota
        }
        return quota
    }

    fun checkQuota(userId: String, fileSize: Long): Boolean {
        val quota = quotas.values.find { it.userId == userId }
        return quota != null && (quota.usedSpace + fileSize) <= quota.totalSpace
    }

    fun getQuotaStats(userId: String): Map<String, Any> {
        val quota = quotas.values.find { it.userId == userId }
        return if (quota != null) {
            mapOf(
                "totalSpace" to quota.totalSpace,
                "usedSpace" to quota.usedSpace,
                "availableSpace" to (quota.totalSpace - quota.usedSpace),
                "usagePercentage" to ((quota.usedSpace.toDouble() / quota.totalSpace) * 100).toInt()
            )
        } else {
            emptyMap()
        }
    }
}