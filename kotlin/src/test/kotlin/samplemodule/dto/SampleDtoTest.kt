package samplemodule.dto

import org.junit.Assert.*
import org.junit.Test

class SampleDtoTest {

    @Test
    fun `create SampleDto with all fields`() {
        val dto = SampleDto(
            name = "Item",
            value = 42,
            description = "A test item"
        )
        assertEquals("Item", dto.name)
        assertEquals(42, dto.value)
        assertEquals("A test item", dto.description)
    }

    @Test
    fun `create SampleDto without description`() {
        val dto = SampleDto(name = "OnlyName", value = 0)
        assertEquals("OnlyName", dto.name)
        assertEquals(0, dto.value)
        assertNull(dto.description)
    }
}
