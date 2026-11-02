package samplemodule.infrastructure

import org.junit.Assert.*
import org.junit.Test

class SampleServiceTest {

    @Test
    fun `processAndNotify returns uppercase file path`() {
        val service = SampleService()
        val result = service.processAndNotify("/data/file.txt")
        assertEquals("/DATA/FILE.TXT", result)
    }

    @Test
    fun `processAndNotify handles simple string`() {
        val service = SampleService()
        val result = service.processAndNotify("hello")
        assertEquals("HELLO", result)
    }

    @Test
    fun `processAndNotify empty string`() {
        val service = SampleService()
        val result = service.processAndNotify("")
        assertEquals("", result)
    }
}
