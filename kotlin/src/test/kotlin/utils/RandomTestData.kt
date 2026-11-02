package utils

import java.util.UUID
import kotlin.random.Random

object RandomTestData {
    private val random = Random

    fun randomString(length: Int = 8): String {
        val chars = "abcdefghijklmnopqrstuvwxyz0123456789"
        return (1..length)
            .map { chars.random() }
            .joinToString("")
    }

    fun randomEmail(prefix: String = "test"): String {
        return "$prefix@example.com"
    }

    fun randomInt(min: Int = 0, max: Int = 1000): Int {
        return random.nextInt(min, max + 1)
    }

    fun randomLong(min: Long = 0, max: Long = 1000000): Long {
        return random.nextLong(min, max + 1)
    }

    fun randomUuid(): String {
        return UUID.randomUUID().toString()
    }

    fun <T> randomItem(vararg items: T): T {
        return items.random()
    }
}
