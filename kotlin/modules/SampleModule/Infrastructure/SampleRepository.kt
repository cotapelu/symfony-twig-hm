package samplemodule.infrastructure

import samplemodule.domain.model.SampleEntity

/**
 * Sample Repository handling data persistence
 */
interface SampleRepository {
    fun find(id: Int): SampleEntity?
    fun save(entity: SampleEntity): Unit
}