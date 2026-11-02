package com.example.metric

import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test

class MetricServiceTest {

    private val service = MetricService()

    @Test
    fun `recordMetric should add metric point`() {
        service.recordMetric("test_metric", 100.0, mapOf("tag" to "value"))
        assertTrue(service.getMetricNames().contains("test_metric"))
    }

    @Test
    fun `incrementCounter should increase counter`() {
        service.incrementCounter("counter1")
        service.incrementCounter("counter1", 5)
        assertEquals(6L, service.getCounter("counter1"))
    }

    @Test
    fun `setGauge should set gauge value`() {
        service.setGauge("gauge1", 42.5)
        assertEquals(42.5, service.getGauge("gauge1"))
    }

    @Test
    fun `recordHistogram should add value to histogram`() {
        service.recordHistogram("hist1", 10.0)
        service.recordHistogram("hist1", 20.0)
        val stats = service.getHistogramStats("hist1")
        assertNotNull(stats)
        assertEquals(2, stats!!.count)
        assertEquals(15.0, stats.avg, 0.001)
        assertEquals(10.0, stats.min)
        assertEquals(20.0, stats.max)
    }

    @Test
    fun `resetCounter should clear counter`() {
        service.incrementCounter("cnt")
        service.incrementCounter("cnt", 5)
        val previous = service.resetCounter("cnt")
        assertEquals(6L, previous)
        assertEquals(0L, service.getCounter("cnt"))
    }

    @Test
    fun `getGaugeNames should return registered gauges`() {
        service.setGauge("g1", 1.0)
        service.setGauge("g2", 2.0)
        val names = service.getGaugeNames()
        assertTrue(names.containsAll(listOf("g1", "g2")))
    }
}
