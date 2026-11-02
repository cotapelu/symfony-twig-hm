package automation

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object Workflows : IntIdTable("workflows") {
    val name = varchar("name", 255)
    val description = text("description").nullable()
    val definition = text("definition")
    val version = integer("version").default(1)
    val status = varchar("status", 20)
    val createdBy = varchar("created_by", 255)
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object WorkflowInstances : IntIdTable("workflow_instances") {
    val workflowId = reference("workflow_id", Workflows)
    val name = varchar("name", 255)
    val status = varchar("status", 20)
    val input = text("input").nullable()
    val output = text("output").nullable()
    val startedAt = datetime("started_at")
    val completedAt = datetime("completed_at").nullable()
    val failedAt = datetime("failed_at").nullable()
    val error = text("error").nullable()
    val currentStep = varchar("current_step", 255).nullable()
    val userId = varchar("user_id", 255).nullable()
    val createdAt = datetime("created_at")
}

object WorkflowSteps : IntIdTable("workflow_steps") {
    val workflowId = reference("workflow_id", Workflows)
    val name = varchar("name", 255)
    val type = varchar("type", 50)
    val config = text("config").nullable()
    val order = integer("order")
    val createdAt = datetime("created_at")
}
