package samplemodule.domain.model

import org.junit.jupiter.api.Test
import kotlin.test.assertEquals
import kotlin.test.assertFailsWith

class SampleEntityTest {

    @Test
    fun `create SampleEntity with valid parameters`() {
        val entity = SampleEntity(id = 1, name = "Test", value = 10, description = "Desc")
        assertEquals(1, entity.id)
        assertEquals("Test", entity.name)
        assertEquals(10, entity.value)
        assertEquals("Desc", entity.description)
    }



    @Test
    fun `throw IllegalArgumentException for blank name`() {
        val ex = assertFailsWith<IllegalArgumentException> {
            SampleEntity(id = 1, name = "   ", value = 10)
        }
        assert(ex.message?.contains("Name cannot be empty") == true)
    }

    @Test
    fun `throw IllegalArgumentException for negative value`() {
        val ex = assertFailsWith<IllegalArgumentException> {
            SampleEntity(id = 1, name = "Test", value = -5)
        }
        assert(ex.message?.contains("Value must be non-negative") == true)
    }
}