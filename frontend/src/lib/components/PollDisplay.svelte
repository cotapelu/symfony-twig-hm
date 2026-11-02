<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  interface PollOption {
    id: string;
    text: string;
    votes?: number;
  }

  interface Poll {
    id: string;
    question: string;
    options: PollOption[];
    expiresAt?: string;
    allowMultiple?: boolean;
    userVotes?: Record<string, number[]>;
    isAnonymous?: boolean;
  }

  export let poll: Poll | null = null;
  export let currentUserId = '';
  export let hasVoted = false;
  export let showResults = false;

  const dispatch = createEventDispatcher();

  $: totalVotes = poll ? poll.options.reduce((sum, opt) => sum + (opt.votes || 0), 0) : 0;
  $: userVotes = poll ? poll.userVotes?.[currentUserId] || [] : [];
  $: isExpired = poll?.expiresAt ? new Date(poll.expiresAt) < new Date() : false;

  function vote(optionIndex) {
    if (!poll || isExpired) return;

    if (poll.allowMultiple) {
      // Toggle vote for multiple choice
      const newUserVotes = [...userVotes];
      const voteIndex = newUserVotes.indexOf(optionIndex);

      if (voteIndex > -1) {
        newUserVotes.splice(voteIndex, 1);
      } else {
        newUserVotes.push(optionIndex);
      }

      dispatch('vote', {
        pollId: poll.id,
        optionIndices: newUserVotes
      });
    } else {
      // Single choice
      dispatch('vote', {
        pollId: poll.id,
        optionIndices: [optionIndex]
      });
    }
  }

  function toggleResults() {
    showResults = !showResults;
  }

  function getPercentage(votes) {
    return totalVotes > 0 ? Math.round((votes / totalVotes) * 100) : 0;
  }

  function hasUserVoted(optionIndex) {
    return userVotes.includes(optionIndex);
  }

  function formatTimeRemaining() {
    if (!poll?.expiresAt) return '';

    const now = new Date();
    const expiry = new Date(poll.expiresAt);
    const diff = Number(expiry) - Number(now);

    if (diff <= 0) return 'Expired';

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    if (hours > 0) {
      return `${hours}h ${minutes}m remaining`;
    }
    return `${minutes}m remaining`;
  }
</script>

{#if poll}
  <div class="poll-display" class:expired={isExpired}>
    <!-- Poll Header -->
    <div class="poll-header">
      <div class="poll-question">
        <h4>{poll.question}</h4>
        {#if poll.expiresAt}
          <div class="poll-expiry" class:expired={isExpired}>
            {formatTimeRemaining()}
          </div>
        {/if}
      </div>

      {#if totalVotes > 0}
        <button
          class="results-toggle"
          onclick={toggleResults}
          aria-expanded={showResults}
        >
          {showResults ? 'Hide' : 'Show'} Results
        </button>
      {/if}
    </div>

    <!-- Poll Options -->
    <div class="poll-options" role="radiogroup" aria-label="Poll options">
      {#each poll.options as option, index}
        {@const percentage = getPercentage(option.votes || 0)}
        {@const userVoted = hasUserVoted(index)}

        <div
          class="poll-option"
          class:selected={userVoted}
          class:disabled={isExpired}
          onclick={() => !isExpired && vote(index)}
          role="radio"
          aria-checked={userVoted}
          aria-disabled={isExpired}
          tabindex="0"
          onkeydown={(e) => {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              !isExpired && vote(index);
            }
          }}
        >
          <!-- Option Content -->
          <div class="option-content">
            <div class="option-text">{option.text}</div>

            {#if showResults && totalVotes > 0}
              <div class="option-stats">
                <span class="vote-count">
                  {option.votes || 0} vote{(option.votes || 0) !== 1 ? 's' : ''}
                </span>
                <span class="percentage">{percentage}%</span>
              </div>
            {/if}
          </div>

          <!-- Vote Indicator -->
          <div class="vote-indicator">
            {#if userVoted}
              <div class="vote-checkmark">✓</div>
            {:else if !poll.allowMultiple}
              <div class="vote-radio"></div>
            {:else}
              <div class="vote-checkbox"></div>
            {/if}
          </div>

          <!-- Progress Bar (when showing results) -->
          {#if showResults && totalVotes > 0}
            <div class="progress-bar">
              <div
                class="progress-fill"
                style="width: {percentage}%"
              ></div>
            </div>
          {/if}
        </div>
      {/each}
    </div>

    <!-- Poll Footer -->
    <div class="poll-footer">
      <div class="poll-meta">
        {#if poll.allowMultiple}
          <span class="poll-type">Multiple choice</span>
        {/if}
        {#if poll.isAnonymous}
          <span class="poll-anonymous">Anonymous</span>
        {/if}
        {#if totalVotes > 0}
          <span class="total-votes">{totalVotes} total vote{totalVotes !== 1 ? 's' : ''}</span>
        {/if}
      </div>

      {#if hasVoted && !isExpired}
        <div class="vote-status">
          ✅ Your vote{userVotes.length > 1 ? 's' : ''} submitted
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  .poll-display {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    max-width: 500px;
    margin: 1rem 0;
  }

  .poll-display.expired {
    opacity: 0.8;
  }

  .poll-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
  }

  .poll-question h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    line-height: 1.4;
  }

  .poll-expiry {
    font-size: 0.75rem;
    color: #6b7280;
  }

  .poll-expiry.expired {
    color: #ef4444;
    font-weight: 500;
  }

  .results-toggle {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    color: #374151;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .results-toggle:hover {
    background: #e5e7eb;
  }

  .poll-options {
    margin-bottom: 1rem;
  }

  .poll-option {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 0.5rem;
  }

  .poll-option:hover:not(.disabled) {
    border-color: #3b82f6;
    background: #f0f9ff;
  }

  .poll-option.selected {
    border-color: #3b82f6;
    background: #eff6ff;
  }

  .poll-option.disabled {
    cursor: not-allowed;
    opacity: 0.6;
  }

  .option-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex: 1;
  }

  .option-text {
    font-size: 1rem;
    color: #374151;
    flex: 1;
  }

  .option-stats {
    display: flex;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
    margin-left: 1rem;
  }

  .vote-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
  }

  .vote-radio {
    width: 16px;
    height: 16px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
  }

  .vote-checkbox {
    width: 16px;
    height: 16px;
    border: 2px solid #d1d5db;
    border-radius: 0.25rem;
  }

  .vote-checkmark {
    width: 16px;
    height: 16px;
    background: #3b82f6;
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
  }

  .progress-bar {
    width: 100%;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    border-radius: 2px;
    transition: width 0.3s ease;
  }

  .poll-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
  }

  .poll-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: #6b7280;
  }

  .poll-type,
  .poll-anonymous {
    background: #f3f4f6;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
  }

  .total-votes {
    font-weight: 500;
  }

  .vote-status {
    font-size: 0.875rem;
    color: #10b981;
    font-weight: 500;
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .poll-display {
      background: #1f2937;
      border-color: #374151;
    }

    .poll-question h4 {
      color: #f9fafb;
    }

    .poll-expiry {
      color: #9ca3af;
    }

    .poll-expiry.expired {
      color: #fca5a5;
    }

    .results-toggle {
      background: #374151;
      border-color: #4b5563;
      color: #f9fafb;
    }

    .results-toggle:hover {
      background: #4b5563;
    }

    .poll-option {
      border-color: #374151;
      background: #1f2937;
    }

    .poll-option:hover:not(.disabled) {
      border-color: #3b82f6;
      background: #1e3a8a;
    }

    .poll-option.selected {
      border-color: #3b82f6;
      background: #1e3a8a;
    }

    .option-text {
      color: #f9fafb;
    }

    .option-stats {
      color: #9ca3af;
    }

    .vote-radio,
    .vote-checkbox {
      border-color: #4b5563;
    }

    .poll-footer {
      border-color: #374151;
    }

    .poll-meta {
      color: #9ca3af;
    }

    .poll-type,
    .poll-anonymous {
      background: #374151;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .poll-display {
      margin: 0.5rem;
      padding: 1rem;
    }

    .poll-header {
      flex-direction: column;
      gap: 0.5rem;
      align-items: stretch;
    }

    .poll-question h4 {
      font-size: 1rem;
    }

    .poll-option {
      padding: 0.75rem;
    }

    .option-content {
      flex-direction: column;
      gap: 0.25rem;
    }

    .option-stats {
      margin-left: 0;
    }

    .poll-footer {
      flex-direction: column;
      gap: 0.5rem;
      align-items: stretch;
    }
  }

  /* Focus styles */
  .poll-option:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
  }

  .results-toggle:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
  }
</style>
