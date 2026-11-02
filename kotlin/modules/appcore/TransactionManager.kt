package com.app.modules.appcore

import java.sql.Connection
import java.sql.SQLException
import javax.sql.DataSource

class TransactionManager(
    private val dataSource: DataSource
) {
    private var inTransaction = false
    private var nestingLevel = 0
    private val savePoints = mutableMapOf<String, String>()

    fun begin() {
        if (nestingLevel == 0) {
            getConnection().autoCommit = false
            inTransaction = true
        }
        nestingLevel++
    }

    fun commit() {
        if (nestingLevel == 0) {
            return
        }

        nestingLevel--

        if (nestingLevel == 0) {
            try {
                getConnection().commit()
                inTransaction = false
                clearSavePoints()
            } catch (e: SQLException) {
                throw RuntimeException("Failed to commit transaction", e)
            }
        }
    }

    fun rollback() {
        if (nestingLevel == 0) {
            return
        }

        nestingLevel--

        if (nestingLevel == 0) {
            try {
                getConnection().rollback()
                inTransaction = false
                clearSavePoints()
            } catch (e: SQLException) {
                throw RuntimeException("Failed to rollback transaction", e)
            }
        }
    }

    fun rollbackTo(savePointName: String) {
        if (!inTransaction || !savePoints.containsKey(savePointName)) {
            return
        }

        try {
            getConnection().rollback(java.sql.Savepoint { savePoints[savePointName] })
            removeSavePointsAfter(savePointName)
        } catch (e: SQLException) {
            throw RuntimeException("Failed to rollback to savepoint $savePointName", e)
        }
    }

    fun createSavePoint(name: String): String {
        if (!inTransaction) {
            throw RuntimeException("Cannot create save point outside of transaction")
        }

        try {
            val savePoint = getConnection().setSavepoint(name)
            savePoints[name] = savePoint.toString()
            return name
        } catch (e: SQLException) {
            throw RuntimeException("Failed to create savepoint $name", e)
        }
    }

    fun releaseSavePoint(name: String) {
        if (!inTransaction || !savePoints.containsKey(name)) {
            return
        }

        try {
            getConnection().releaseSavepoint(java.sql.Savepoint { savePoints[name] })
            savePoints.remove(name)
        } catch (e: SQLException) {
            throw RuntimeException("Failed to release savepoint $name", e)
        }
    }

    fun <T> execute(operation: () -> T): T {
        return executeWithRetry(operation, 3)
    }

    fun <T> executeWithRetry(operation: () -> T, maxRetries: Int): T {
        var retries = 0
        var lastException: Exception? = null

        while (retries < maxRetries) {
            try {
                begin()
                val result = operation()
                commit()
                return result
            } catch (e: Exception) {
                rollback()
                lastException = e
                retries++

                if (retries < maxRetries) {
                    Thread.sleep(100L * retries)
                }
            }
        }

        throw lastException ?: RuntimeException("Transaction execution failed")
    }

    fun isInTransaction(): Boolean = inTransaction

    fun getNestingLevel(): Int = nestingLevel

    fun getSavePoints(): List<String> = savePoints.keys.toList()

    private fun getConnection(): Connection {
        return dataSource.connection
    }

    private fun clearSavePoints() {
        savePoints.clear()
    }

    private fun removeSavePointsAfter(savePointName: String) {
        var found = false
        val toRemove = mutableListOf<String>()

        savePoints.forEach { (name, _) ->
            if (name == savePointName) {
                found = true
                return@forEach
            }

            if (found) {
                toRemove.add(name)
            }
        }

        toRemove.forEach { savePoints.remove(it) }
    }
}