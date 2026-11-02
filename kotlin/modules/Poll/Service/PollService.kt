package com.example.poll

import java.time.Instant
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

/**
 * Poll Service - Manages polls and voting
 */
class PollService {
    private val polls = ConcurrentHashMap<String, Poll>()
    private val votes = ConcurrentHashMap<String, MutableList<Vote>>()

    data class Poll(
        val id: String = UUID.randomUUID().toString(),
        val title: String,
        val description: String,
        val options: List<PollOption>,
        val status: PollStatus = PollStatus.ACTIVE,
        val createdBy: String,
        val createdAt: Instant = Instant.now(),
        val expiresAt: Instant? = null,
        val allowMultipleVotes: Boolean = false,
        val showResultsBeforeEnd: Boolean = true
    )

    data class PollOption(
        val id: String = UUID.randomUUID().toString(),
        val text: String,
        val voteCount: Int = 0
    )

    data class Vote(
        val id: String = UUID.randomUUID().toString(),
        val pollId: String,
        val optionId: String,
        val userId: String,
        val votedAt: Instant = Instant.now()
    )

    enum class PollStatus {
        DRAFT, ACTIVE, CLOSED, ARCHIVED
    }

    /**
     * Create a new poll
     */
    fun createPoll(
        title: String,
        description: String,
        options: List<String>,
        createdBy: String,
        expiresAt: Instant? = null,
        allowMultipleVotes: Boolean = false
    ): Poll {
        require(title.isNotBlank()) { "Title cannot be empty" }
        require(options.size >= 2) { "Poll must have at least 2 options" }
        require(options.distinct().size == options.size) { "Options must be unique" }

        val pollOptions = options.map { PollOption(text = it) }

        val poll = Poll(
            title = title,
            description = description,
            options = pollOptions,
            createdBy = createdBy,
            expiresAt = expiresAt
        )

        polls[poll.id] = poll
        return poll
    }

    /**
     * Get poll by ID
     */
    fun getPoll(pollId: String): Poll? {
        return polls[pollId]
    }

    /**
     * Get all active polls
     */
    fun getActivePolls(limit: Int = 20, offset: Int = 0): List<Poll> {
        return polls.values
            .filter { it.status == PollStatus.ACTIVE }
            .sortedByDescending { it.createdAt }
            .drop(offset)
            .take(limit)
    }

    /**
     * Get polls by user
     */
    fun getUserPolls(userId: String): List<Poll> {
        return polls.values
            .filter { it.createdBy == userId }
            .sortedByDescending { it.createdAt }
    }

    /**
     * Update poll
     */
    fun updatePoll(pollId: String, title: String? = null, description: String? = null): Poll? {
        val poll = polls[pollId] ?: return null

        val updatedPoll = poll.copy(
            title = title ?: poll.title,
            description = description ?: poll.description
        )

        polls[pollId] = updatedPoll
        return updatedPoll
    }

    /**
     * Close a poll
     */
    fun closePoll(pollId: String): Poll? {
        val poll = polls[pollId] ?: return null

        val updatedPoll = poll.copy(status = PollStatus.CLOSED)
        polls[pollId] = updatedPoll
        return updatedPoll
    }

    /**
     * Delete a poll
     */
    fun deletePoll(pollId: String): Boolean {
        return polls.remove(pollId) != null
    }

    /**
     * Vote on a poll
     */
    fun vote(pollId: String, optionId: String, userId: String): Vote? {
        val poll = polls[pollId] ?: return null

        // Check if poll is active
        if (poll.status != PollStatus.ACTIVE) {
            throw IllegalStateException("Poll is not active")
        }

        // Check if poll has expired
        poll.expiresAt?.let { expires ->
            if (Instant.now().isAfter(expires)) {
                throw IllegalStateException("Poll has expired")
            }
        }

        // Check if option exists
        val option = poll.options.find { it.id == optionId }
            ?: return null

        // Check if user already voted (if multiple votes not allowed)
        if (!poll.allowMultipleVotes) {
            val existingVotes = votes[pollId] ?: emptyList()
            if (existingVotes.any { it.userId == userId }) {
                throw IllegalStateException("User has already voted")
            }
        }

        // Create vote
        val vote = Vote(
            pollId = pollId,
            optionId = optionId,
            userId = userId
        )

        // Update vote count for option
        val updatedOptions = poll.options.map { opt ->
            if (opt.id == optionId) {
                opt.copy(voteCount = opt.voteCount + 1)
            } else {
                opt
            }
        }

        // Save vote and update poll
        votes.getOrPut(pollId) { mutableListOf() }.add(vote)
        polls[pollId] = poll.copy(options = updatedOptions)

        return vote
    }

    /**
     * Get vote count for a poll
     */
    fun getVoteCount(pollId: String): Int {
        return votes[pollId]?.size ?: 0
    }

    /**
     * Get poll results
     */
    fun getPollResults(pollId: String): PollResults? {
        val poll = polls[pollId] ?: return null

        val pollVotes = votes[pollId] ?: emptyList()
        val totalVotes = pollVotes.size

        val optionResults = poll.options.map { option ->
            val optionVotes = pollVotes.count { it.optionId == option.id }
            OptionResult(
                optionId = option.id,
                text = option.text,
                voteCount = optionVotes,
                percentage = if (totalVotes > 0) (optionVotes.toDouble() / totalVotes * 100) else 0.0
            )
        }.sortedByDescending { it.voteCount }

        return PollResults(
            pollId = pollId,
            totalVotes = totalVotes,
            options = optionResults
        )
    }

    /**
     * Check if user has voted
     */
    fun hasUserVoted(pollId: String, userId: String): Boolean {
        return votes[pollId]?.any { it.userId == userId } ?: false
    }

    /**
     * Get user's vote
     */
    fun getUserVote(pollId: String, userId: String): Vote? {
        return votes[pollId]?.find { it.userId == userId }
    }

    /**
     * Get poll statistics
     */
    fun getStatistics(): PollStatistics {
        val totalPolls = polls.size
        val activePolls = polls.values.count { it.status == PollStatus.ACTIVE }
        val totalVotes = votes.values.sumOf { it.size }

        return PollStatistics(
            totalPolls = totalPolls,
            activePolls = activePolls,
            totalVotes = totalVotes,
            averageVotesPerPoll = if (totalPolls > 0) totalVotes.toDouble() / totalPolls else 0.0
        )
    }

    data class PollResults(
        val pollId: String,
        val totalVotes: Int,
        val options: List<OptionResult>
    )

    data class OptionResult(
        val optionId: String,
        val text: String,
        val voteCount: Int,
        val percentage: Double
    )

    data class PollStatistics(
        val totalPolls: Int,
        val activePolls: Int,
        val totalVotes: Int,
        val averageVotesPerPoll: Double
    )
}