package automation

import automation.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class WorkflowengineService(private val repository: WorkflowengineRepository) {
    suspend fun process(data: Map<String, Any>) = withContext(Dispatchers.IO) {
        repository.findAll()
    }

    suspend fun createWorkflow(name: String, definition: String): String {
        repository.saveWorkflow(name, definition)
        return "Workflow created: $name"
    }

    suspend fun executeWorkflow(workflowId: String, input: String): String {
        return "Executing workflow $workflowId with input: $input"
    }

    suspend fun getWorkflows(): List<String> {
        return repository.getAllWorkflows()
    }
}
