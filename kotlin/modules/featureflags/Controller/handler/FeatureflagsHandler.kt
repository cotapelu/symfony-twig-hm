package featureflags

import featureflags.FeatureflagsService
import io.ktor.server.application.*

class FeatureflagsHandler(private val service: FeatureflagsService) {
    suspend fun getFlags() {}
    suspend fun createFlag() {}
    suspend fun evaluateFlag() {}
}
