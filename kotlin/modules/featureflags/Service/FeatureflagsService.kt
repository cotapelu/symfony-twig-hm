package featureflags

import featureflags.FeatureflagsRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class FeatureflagsService(private val repository: FeatureflagsRepository) {
    suspend fun process(data: Map<String, Any>) = withContext(Dispatchers.IO) {
        repository.findAll()
    }

    suspend fun isFlagEnabled(flagKey: String, userId: String): Boolean {
        return true
    }

    suspend fun createFlag(key: String, enabled: Boolean): String {
        return "flag_created"
    }
}
