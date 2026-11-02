package com.example.queue

import com.example.queue.QueueService.ProcessingStatus
import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import java.time.Instant

class QueueServiceTest {

    private val service = QueueService()

    @Test
    fun `createQueue should create a new queue`() {
        val queue = service.createQueue("test-queue", "Test queue", 100, 3600)
        assertEquals("test-queue", queue.name)
        assertEquals(100, queue.maxSize)
        assertEquals(3600, queue.messageTtlSeconds)
    }

    @Test
    fun `createQueue should throw for invalid name`() {
        assertThrows(IllegalArgumentException::class.java) {
            service.createQueue("", "Invalid", 100, 3600)
        }
        assertThrows(IllegalArgumentException::class.java) {
            service.createQueue("invalid name", "Invalid", 100, 3600)
        }
    }

    @Test
    fun `enqueue should add message to queue`() {
        service.createQueue("q1")
        val message = service.enqueue(
            queueName = "q1",
            payload = mapOf("key" to "value"),
            priority = 1
        )
        assertNotNull(message!!.id)
        assertEquals(mapOf("key" to "value"), message.payload)
        assertEquals(1, message.priority)
        assertEquals(0, message.attempts)
    }

    @Test
    fun `dequeue should return message from queue`() {
        service.createQueue("q2")
        service.enqueue("q2", mapOf("data" to 1))

        val msg = service.dequeue("q2")
        assertNotNull(msg)
        assertEquals(1, msg!!.payload["data"])
    }

    @Test
    fun `dequeue should return null when queue empty`() {
        service.createQueue("q3")
        val msg = service.dequeue("q3")
        assertNull(msg)
    }

    @Test
    fun `acknowledgeMessage should move to processed with success status`() {
        service.createQueue("q4")
        val msg = service.enqueue("q4", mapOf("x" to 1))
        service.markProcessed(msg!!.id, "q4", ProcessingStatus.SUCCESS, 10)

        val processed = service.getMessage(msg.id)
        assertNotNull(processed)
        assertEquals(ProcessingStatus.SUCCESS, processed!!.status)
    }

    @Test
    fun `retryMessage should increment attempts and requeue`() {
        service.createQueue("q5")
        val msg = service.enqueue("q5", mapOf("x" to 1))

        val retry = service.requeue(msg!!, "q5")
        assertNotNull(retry)
        assertEquals(1, retry!!.attempts)
    }

    @Test
    fun `purgeQueue should remove old messages`() {
        service.createQueue("q6")
        val msg1 = service.enqueue("q6", mapOf("a" to 1))
        val msg2 = service.enqueue("q6", mapOf("b" to 2))
        service.markProcessed(msg1!!.id, "q6", ProcessingStatus.SUCCESS, 10)
        service.markProcessed(msg2!!.id, "q6", ProcessingStatus.SUCCESS, 10)

        val count = service.purgeOldMessages(0)
        assertEquals(2, count)
        assertNull(service.getMessage(msg1.id))
        assertNull(service.getMessage(msg2.id))
    }

    @Test
    fun `listQueues should return all queues`() {
        service.createQueue("queue1")
        service.createQueue("queue2")
        val queues = service.listQueues()
        assertTrue(queues.containsAll(listOf("queue1", "queue2")))
    }

    @Test
    fun `deleteQueue should remove queue`() {
        service.createQueue("q7")
        val deleted = service.deleteQueue("q7")
        assertTrue(deleted)
        assertNull(service.getQueue("q7"))
    }
}
