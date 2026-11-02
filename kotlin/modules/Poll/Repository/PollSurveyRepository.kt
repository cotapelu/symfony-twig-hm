package poll

import java.time.LocalDateTime
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

class PollSurveyRepository {
    private val polls = ConcurrentHashMap<String, PollSurvey>()

    fun save(poll: PollSurvey): PollSurvey {
        polls[poll.id] = poll
        return poll
    }

    fun findById(id: String): PollSurvey? {
        return polls[id]
    }

    fun findByTitle(title: String): List<PollSurvey> {
        return polls.values.filter { it.title.contains(title, ignoreCase = true) }
    }

    fun findByStatus(status: String): List<PollSurvey> {
        return polls.values.filter { it.status == status }
    }

    fun findByCreatedBy(createdBy: String): List<PollSurvey> {
        return polls.values.filter { it.createdBy == createdBy }
    }

    fun findActivePolls(): List<PollSurvey> {
        return polls.values.filter { it.status == "active" && it.expiresAt > LocalDateTime.now() }
    }

    fun findExpiredPolls(): List<PollSurvey> {
        return polls.values.filter { it.status == "active" && it.expiresAt < LocalDateTime.now() }
    }

    fun updateStatus(id: String, status: String): PollSurvey? {
        val poll = polls[id]
        if (poll != null) {
            poll.status = status
            poll.updatedAt = LocalDateTime.now()
            polls[id] = poll
        }
        return poll
    }

    fun delete(id: String) {
        polls.remove(id)
    }

    fun getPollStats(createdBy: String): Map<String, Any> {
        val userPolls = polls.values.filter { it.createdBy == createdBy }
        return mapOf(
            "totalPolls" to userPolls.size,
            "activePolls" to userPolls.count { it.status == "active" },
            "completedPolls" to userPolls.count { it.status == "completed" },
            "expiredPolls" to userPolls.count { it.status == "expired" }
        )
    }
}

class PollQuestionRepository {
    private val questions = ConcurrentHashMap<String, PollQuestion>()

    fun save(question: PollQuestion): PollQuestion {
        questions[question.id] = question
        return question
    }

    fun findById(id: String): PollQuestion? {
        return questions[id]
    }

    fun findByPollId(pollId: String): List<PollQuestion> {
        return questions.values.filter { it.pollId == pollId }
    }

    fun updateQuestion(id: String, question: String): PollQuestion? {
        val q = questions[id]
        if (q != null) {
            q.question = question
            q.updatedAt = LocalDateTime.now()
            questions[id] = q
        }
        return q
    }

    fun delete(id: String) {
        questions.remove(id)
    }
}

class PollAnswerRepository {
    private val answers = ConcurrentHashMap<String, PollAnswer>()

    fun save(answer: PollAnswer): PollAnswer {
        answers[answer.id] = answer
        return answer
    }

    fun findById(id: String): PollAnswer? {
        return answers[id]
    }

    fun findByQuestionId(questionId: String): List<PollAnswer> {
        return answers.values.filter { it.questionId == questionId }
    }

    fun updateAnswer(id: String, answer: String): PollAnswer? {
        val a = answers[id]
        if (a != null) {
            a.answer = answer
            a.updatedAt = LocalDateTime.now()
            answers[id] = a
        }
        return a
    }

    fun delete(id: String) {
        answers.remove(id)
    }
}

class PollVoteRepository {
    private val votes = ConcurrentHashMap<String, PollVote>()

    fun save(vote: PollVote): PollVote {
        votes[vote.id] = vote
        return vote
    }

    fun findById(id: String): PollVote? {
        return votes[id]
    }

    fun findByUserId(userId: String): List<PollVote> {
        return votes.values.filter { it.userId == userId }
    }

    fun findByPollId(pollId: String): List<PollVote> {
        return votes.values.filter { it.pollId == pollId }
    }

    fun findByQuestionId(questionId: String): List<PollVote> {
        return votes.values.filter { it.questionId == questionId }
    }

    fun findByAnswerId(answerId: String): List<PollVote> {
        return votes.values.filter { it.answerId == answerId }
    }

    fun countVotesByAnswer(answerId: String): Int {
        return votes.values.count { it.answerId == answerId }
    }

    fun countVotesByQuestion(questionId: String): Int {
        return votes.values.count { it.questionId == questionId }
    }

    fun countVotesByPoll(pollId: String): Int {
        return votes.values.count { it.pollId == pollId }
    }

    fun delete(id: String) {
        votes.remove(id)
    }

    fun getVoteStats(pollId: String): Map<String, Any> {
        val pollVotes = votes.values.filter { it.pollId == pollId }
        return mapOf(
            "totalVotes" to pollVotes.size,
            "uniqueVoters" to pollVotes.map { it.userId }.distinct().size
        )
    }
}