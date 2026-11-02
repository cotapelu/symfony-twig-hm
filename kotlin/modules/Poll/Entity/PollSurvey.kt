package com.example.app.poll.Entity

import java.time.LocalDateTime
import java.util.UUID

data class PollSurvey(
    val id: String = UUID.randomUUID().toString(),
    val userId: String,
    val title: String,
    val description: String,
    val pollType: PollType = PollType.SURVEY,
    val status: PollStatus = PollStatus.DRAFT,
    val questions: List<PollQuestion>,
    val responses: List<PollResponse> = emptyList(),
    val startDate: LocalDateTime? = null,
    val endDate: LocalDateTime? = null,
    val createdAt: LocalDateTime = LocalDateTime.now(),
    val updatedAt: LocalDateTime = LocalDateTime.now()
)

data class PollQuestion(
    val id: String = UUID.randomUUID().toString(),
    val questionText: String,
    val questionType: QuestionType,
    val options: List<PollOption> = emptyList(),
    val required: Boolean = true
)

data class PollOption(
    val id: String = UUID.randomUUID().toString(),
    val optionText: String,
    val order: Int = 0
)

data class PollResponse(
    val questionId: String,
    val answer: String,
    val metadata: Map<String, Any> = emptyMap()
)

enum class PollType {
    SURVEY,
    POLL,
    QUIZ,
    FEEDBACK
}

enum class PollStatus {
    DRAFT,
    ACTIVE,
    CLOSED,
    ARCHIVED
}

enum class QuestionType {
    SINGLE_CHOICE,
    MULTIPLE_CHOICE,
    TEXT,
    RATING,
    BOOLEAN
}
