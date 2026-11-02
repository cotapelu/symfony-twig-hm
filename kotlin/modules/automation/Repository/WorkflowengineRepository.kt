package automation

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import automation.*

class WorkflowengineRepository {
    suspend fun findAll() = withContext(Dispatchers.IO) { emptyList<Any>() }
    suspend fun findById(id: String) = withContext(Dispatchers.IO) { null }
    suspend fun saveWorkflow(name: String, definition: String) = withContext(Dispatchers.IO) { name }
    suspend fun getAllWorkflows() = withContext(Dispatchers.IO) { listOf("workflow1", "workflow2") }
    suspend fun saveExecution(execution: Any) = withContext(Dispatchers.IO) { execution }
}
