package com.example.queue

import java.time.Instant
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap
import java.util.concurrent.ConcurrentLinkedQueue
import java.util.concurrent.atomic.AtomicLong

/**
 * Queue Service - Manages message queues for async processing
 */
class QueueService {
    // Map from queue name to the actual message queue
    private val messageQueues = ConcurrentHashMap<String, ConcurrentLinkedQueue<QueueMessage>>()
    private val queueInfoMap = ConcurrentHashMap<String, QueueInfo>()
    private val processedMessages = ConcurrentHashMap<String, ProcessedMessage>()

    data class QueueMessage(
        val id: String = UUID.randomUUID().toString(),
        val payload: Map<String, Any>,
        val priority: Int = 0,
        val metadata: Map<String, String> = emptyMap(),
        val enqueuedAt: Instant = Instant.now(),
        val scheduledAt: Instant? = null,
        val attempts: Int = 0,
        val maxAttempts: Int = 3
    )

    data class ProcessedMessage(
        val messageId: String,
        val queueName: String,
        val status: ProcessingStatus,
        val processedAt: Instant,
        val processingTimeMs: Long,
        val error: String? = null
    )



    enum class ProcessingStatus {
        SUCCESS, FAILED, TIMEOUT, CANCELLED
    }

    /**
     * Create a new queue
     */
    fun createQueue(
        name: String,
        description: String = "",
        maxSize: Int = 10000,
        messageTtlSeconds: Long = 3600
    ): QueueInfo {
        require(name.isNotBlank()) { "Queue name cannot be empty" }
        require(name.matches(Regex("^[a-zA-Z0-9_-]+$"))) { "Queue name must be alphanumeric with - or _" }

        val queueInfo = QueueInfo(
            name = name,
            maxSize = maxSize,
            messageTtlSeconds = messageTtlSeconds,
            description = description,
            createdAt = Instant.now()
        )

        messageQueues[name] = ConcurrentLinkedQueue()
        queueInfoMap[name] = queueInfo
        return queueInfo
    }

    /**
     * Get queue info
     */
    fun getQueue(name: String): QueueInfo? {
        return queueInfoMap[name]
    }

    /**
     * List all queues
     */
    fun listQueues(): List<String> {
        return messageQueues.keys.toList()
    }

    /**
     * Delete a queue
     */
    fun deleteQueue(name: String): Boolean {
        messageQueues.remove(name)
        queueInfoMap.remove(name)
        return true
    }

    /**
     * Enqueue a message
     */
    fun enqueue(
        queueName: String,
        payload: Map<String, Any>,
        priority: Int = 0,
        metadata: Map<String, String> = emptyMap(),
        scheduledAt: Instant? = null,
        maxAttempts: Int = 3
    ): QueueMessage? {
        val queue = messageQueues[queueName] ?: return null

        val message = QueueMessage(
            payload = payload,
            priority = priority,
            metadata = metadata,
            scheduledAt = scheduledAt,
            maxAttempts = maxAttempts
        )

        // Check if queue is full (use maxSize from queueInfo)
        val info = queueInfoMap[queueName]
        if (info != null && queue.size >= info.maxSize) {
            return null
        }

        queue.add(message)
        return message
    }

    /**
     * Dequeue a message (FIFO)
     */
    fun dequeue(queueName: String): QueueMessage? {
        val queue = messageQueues[queueName] ?: return null
        return queue.poll()
    }

    /**
     * Peek at next message without removing
     */
    fun peek(queueName: String): QueueMessage? {
        val queue = messageQueues[queueName] ?: return null
        return queue.peek()
    }

    /**
     * Get queue size
     */
    fun getQueueSize(queueName: String): Int {
        return messageQueues[queueName]?.size ?: 0
    }

    /**
     * Mark message as processed
     */
    fun markProcessed(
        messageId: String,
        queueName: String,
        status: ProcessingStatus,
        processingTimeMs: Long,
        error: String? = null
    ) {
        processedMessages[messageId] = ProcessedMessage(
            messageId = messageId,
            queueName = queueName,
            status = status,
            processedAt = Instant.now(),
            processingTimeMs = processingTimeMs,
            error = error
        )
    }

    /**
     * Requeue failed message
     */
    fun requeue(message: QueueMessage, queueName: String): QueueMessage? {
        if (message.attempts >= message.maxAttempts) {
            return null  // Max attempts reached
        }

        val retryMessage = message.copy(
            attempts = message.attempts + 1
        )

        messageQueues[queueName]?.add(retryMessage)
        return retryMessage
    }

    /**
     * Get message by ID
     */
    fun getMessage(messageId: String): ProcessedMessage? {
        return processedMessages[messageId]
    }

    /**
     * Get processing history for a queue
     */
    fun getProcessingHistory(
        queueName: String,
        limit: Int = 50
    ): List<ProcessedMessage> {
        return processedMessages.values
            .filter { it.queueName == queueName }
            .sortedByDescending { it.processedAt }
            .take(limit)
    }

    /**
     * Purge old processed messages
     */
    fun purgeOldMessages(olderThanHours: Int = 24): Int {
        val cutoff = Instant.now().minusSeconds(olderThanHours * 3600L)
        var purged = 0

        processedMessages.entries.removeIf { (_, message) ->
            if (message.processedAt.isBefore(cutoff)) {
                purged++
                true
            } else {
                false
            }
        }

        return purged
    }

    /**
     * Get queue statistics
     */
    fun getQueueStatistics(): QueueStatistics {
        val totalMessages = messageQueues.values.sumOf { it.size }
        val totalProcessed = processedMessages.size

        val byQueue = queueInfoMap.mapValues { messageQueues[it.key]?.size ?: 0 }

        val successCount = processedMessages.values.count { it.status == ProcessingStatus.SUCCESS }
        val failedCount = processedMessages.values.count { it.status == ProcessingStatus.FAILED }

        val avgProcessingTime = if (processedMessages.isNotEmpty()) {
            processedMessages.values.map { it.processingTimeMs }.average()
        } else 0.0

        return QueueStatistics(
            totalQueues = messageQueues.size,
            totalMessagesInQueues = totalMessages,
            totalProcessedMessages = totalProcessed,
            messagesByQueue = byQueue,
            successRate = if (totalProcessed > 0) successCount.toDouble() / totalProcessed * 100 else 0.0,
            failedCount = failedCount,
            averageProcessingTimeMs = avgProcessingTime
        )
    }

    data class QueueInfo(
        val name: String,
        val maxSize: Int,
        val messageTtlSeconds: Long,
        val description: String,
        val createdAt: Instant
    )

    data class QueueStatistics(
        val totalQueues: Int,
        val totalMessagesInQueues: Int,
        val totalProcessedMessages: Int,
        val messagesByQueue: Map<String, Int>,
        val successRate: Double,
        val failedCount: Int,
        val averageProcessingTimeMs: Double
    )
}