<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount } from 'svelte';

  interface PollOption {
    id: string;
    text: string;
  }

  interface PollVotes {
    [optionId: string]: number;
  }

  export let pollId: string = '';
  export let question: string = '';
  export let options: PollOption[] = [];
  export let votes: PollVotes = {};
  export let totalVotes = 0;
  export let userVote: string | null = null;
  export let isCreator = false;
  export let isExpired = false;
  export let disabled = false;

  let isVoting = false;
  let selectedOption: string | null = null;

  onMount(() => {
    if (userVote) {
      selectedOption = userVote;
    }
  });

  const dispatch = createEventDispatcher();

  async function handleVote(optionId: string) {
    if (isExpired || userVote || isVoting || disabled) return;

    isVoting = true;
    const previousVote = userVote;

    try {
      selectedOption = optionId;
      userVote = optionId;

      await new Promise<void>(resolve => dispatch('vote', { pollId, optionId, resolve }));

      votes = { ...votes, [optionId]: (votes[optionId] || 0) + 1 };
      totalVotes += 1;

    } catch (error) {
      selectedOption = previousVote;
      userVote = previousVote;
      dispatch('error', { pollId, optionId, error });
    } finally {
      isVoting = false;
    }
  }

  function getVotePercentage(optionId: string) {
    if (totalVotes === 0) return 0;
    return Math.round(((votes[optionId] || 0) / totalVotes) * 100);
  }

  function getOptionVotes(optionId: string) {
    return votes[optionId] || 0;
  }
</script>

<div class="message-poll" class:expired={isExpired}>
  <div class="poll-question">
    <h4>{question}</h4>
    {#if isExpired}
      <span class="expired-badge">Poll Ended</span>
    {:else}
      <span class="active-badge">Active Poll</span>
    {/if}
  </div>

  <div class="poll-options">
    {#each options as option}
      <div
        class="poll-option"
        class:voted={selectedOption === option.id}
        class:disabled={isExpired || userVote || disabled}
        class:voting={isVoting}
        onclick={() => handleVote(option.id)}
        role="button"
        tabindex={isExpired || userVote || disabled ? -1 : 0}
        onkeydown={(e) => {
          if ((e.key === 'Enter' || e.key === ' ') && !isExpired && !userVote && !disabled) {
            handleVote(option.id);
          }
        }}
      >
        <div class="option-content">
          <span class="option-text">{option.text}</span>
          {#if totalVotes > 0}
            <span class="vote-count">{getOptionVotes(option.id)} votes</span>
          {/if}
        </div>

        {#if totalVotes > 0}
          <div class="vote-bar">
            <div
              class="vote-fill"
              style="width: {getVotePercentage(option.id)}%"
            ></div>
          </div>
          <span class="percentage">{getVotePercentage(option.id)}%</span>
        {/if}
      </div>
    {/each}
  </div>

  {#if totalVotes > 0}
    <div class="poll-footer">
      <span class="total-votes">{totalVotes} total votes</span>
      {#if userVote}
        <span class="user-vote">You voted</span>
      {/if}
    </div>
  {/if}

  {#if isCreator && !isExpired}
    <div class="poll-actions">
      <button
        class="end-poll-btn"
        onclick={() => dispatch('endPoll', { pollId })}
      >
        End Poll
      </button>
    </div>
  {/if}
</div>

<style>
  .message-poll {
    border: 1px solid var(--border-primary);
    border-radius: 12px;
    padding: 16px;
    margin: 8px 0;
    background: var(--bg-primary);
    max-width: 400px;
    color: var(--text-primary);
  }

  .message-poll.expired {
    opacity: 0.7;
  }

  .poll-question {
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .poll-question h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
  }

  .expired-badge, .active-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
  }

  .expired-badge {
    background: #f3f4f6;
    color: #6b7280;
  }

  .active-badge {
    background: #dbeafe;
    color: #2563eb;
  }

  .poll-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .poll-option {
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
  }

  .poll-option:hover:not(.disabled) {
    border-color: #3b82f6;
    background: #f8fafc;
  }

  .poll-option.voted {
    border-color: #10b981;
    background: #f0fdf4;
  }

  .poll-option.disabled {
    cursor: not-allowed;
    opacity: 0.6;
  }

  .poll-option.voting {
    opacity: 0.7;
    pointer-events: none;
  }

  .option-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
  }

  .option-text {
    font-weight: 500;
    color: #374151;
  }

  .vote-count {
    font-size: 14px;
    color: #6b7280;
  }

  .vote-bar {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 4px;
  }

  .vote-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    border-radius: 2px;
    transition: width 0.3s ease;
  }

  .poll-option.voted .vote-fill {
    background: linear-gradient(90deg, #10b981, #059669);
  }

  .percentage {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    align-self: flex-end;
  }

  .poll-footer {
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    color: #6b7280;
  }

  .total-votes {
    font-weight: 500;
  }

  .user-vote {
    color: #10b981;
    font-weight: 500;
  }

  .poll-actions {
    margin-top: 12px;
    text-align: center;
  }

  .end-poll-btn {
    background: #ef4444;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .end-poll-btn:hover {
    background: #dc2626;
  }

  /* Dark mode support */
  :global([data-theme="dark"]) .message-poll {
    background: var(--bg-secondary);
    border-color: var(--border-primary);
  }

  :global([data-theme="dark"]) .poll-question h4 {
    color: var(--text-primary);
  }

  :global([data-theme="dark"]) .poll-option {
    border-color: var(--border-secondary);
    background: var(--bg-tertiary);
  }

  :global([data-theme="dark"]) .poll-option:hover:not(.disabled) {
    border-color: var(--accent-primary);
    background: var(--border-primary);
  }

  :global([data-theme="dark"]) .poll-option.voted {
    border-color: var(--accent-success);
    background: rgba(16, 185, 129, 0.1);
  }

  :global([data-theme="dark"]) .option-text {
    color: var(--text-primary);
  }

  :global([data-theme="dark"]) .vote-count {
    color: var(--text-secondary);
  }

  :global([data-theme="dark"]) .vote-bar {
    background: var(--border-secondary);
  }

  :global([data-theme="dark"]) .percentage {
    color: var(--text-secondary);
  }

  :global([data-theme="dark"]) .poll-footer {
    color: var(--text-secondary);
  }

  @keyframes spin {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(360deg); }
  }

  /* Mobile responsive */
  @media (max-width: 480px) {
    .message-poll {
      margin: 4px 0;
      padding: 12px;
    }

    .poll-options {
      gap: 6px;
    }

    .poll-option {
      padding: 10px;
    }

    .option-content {
      flex-direction: column;
      align-items: flex-start;
      gap: 2px;
    }
  }
</style>