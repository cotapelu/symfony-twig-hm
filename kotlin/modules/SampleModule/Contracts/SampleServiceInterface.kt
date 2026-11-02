package samplemodule.contracts

/**
 * Sample Service Interface defining the contract for application services
 */
interface SampleServiceInterface {
    fun processAndNotify(filePath: String): String
}