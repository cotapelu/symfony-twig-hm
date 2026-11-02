package samplemodule.domain.event

import org.junit.Assert.*
import org.junit.Test
import java.time.Instant

class SampleEventTest {

    @Test
    fun `create SampleEvent with required fields`() {
        val event = SampleEvent(1, "test", 100)
        assertEquals(1, event.entityId)
        assertEquals("test", event.name)
        assertEquals(100, event.value)
        assertNotNull(event.occurredOn)
    }

    @Test
    fun `create SampleEvent with custom occurredOn`() {
        val now = Instant.now()
        val event = SampleEvent(2, "custom", 200, now)
        assertEquals(now, event.occurredOn)
    }

    @Test
    fun `SampleEvent data class equality`() {
        val now = Instant.now()
        val event1 = SampleEvent(3, "same", 300, now)
        val event2 = SampleEvent(3, "same", 300, now)
        assertEquals(event1, event2)
    }
}
