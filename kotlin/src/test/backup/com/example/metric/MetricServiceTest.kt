package com.example.metric

import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import java.time.Instant

class MetricServiceTest {

    private val service = MetricService()

    @Test
    fun `recordMetric should add metric point`() {
        service.recordMetric("test_metric", 100.0, mapOf("tag" to "value"))
        val points = service.getMetric("test_metric")
        assertEquals(1, points.size)
        assertEquals(100.0, points[0].value)
        assertEquals(mapOf("tag" to "value"), points[0].tags)
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
    fun `getMetricsByTag should filter by tag`() {
        service.recordMetric("m1", 1.0, mapOf("env" to "prod"))
        service.recordMetric("m2", 2.0, mapOf("env" to "dev"))
        val prodMetrics = service.getMetricsByTag("env", "prod")
        assertEquals(1, prodMetrics.size)
        assertEquals("m1", prodMetrics[0].name)
    }

    @Test
    fun `getAggregatedMetric should return aggregated value by tag`() {
        service.recordMetric("cpu", 50.0, mapOf("host" to "server1"))
        service.recordMetric("cpu", 70.0, mapOf("host" to "server1"))
        val agg = service.getAggregatedMetric("cpu", mapOf("host" to "server1"), MetricService.Aggregation.SUM)
        assertEquals(120.0, agg)
    }

    @Test
    fun `getTopN should return top n metrics by value`() {
        service.recordMetric("top", 10.0)
        service.recordMetric("top", 5.0)
        service.recordMetric("top", 20.0)
        val top2 = service.getTopN("top", 2, MetricService.SortOrder.DESC)
        assertEquals(2, top2.size)
        assertEquals(20.0, top2[0].value)
        assertEquals(10.0, top2[1].value)
    }

    @Test
    fun `resetAll should clear all metrics`() {
        service.recordMetric("reset_test", 100.0)
        service.incrementCounter("cnt")
        service.resetAll()
        assertTrue(service.getMetric("reset_test").isEmpty())
        assertEquals(0L, service.getCounter("cnt"))
    }
}
