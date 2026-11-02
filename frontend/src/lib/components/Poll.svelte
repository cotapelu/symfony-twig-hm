<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { fade } from 'svelte/transition';
  import { Button, Input, Typography, Radio } from '$lib/components';
  import type { Poll } from '$lib/types';

  export let poll: Poll | null = null;
  export let onVote: ((pollId: string, optionIndex: number) => void) | null = null;
  export let onCreate: ((pollData: any) => void) | null = null;
  export let isCreating = false;

  const dispatch = createEventDispatcher();

  let newPollQuestion = '';
  let newPollOptions = ['', ''];
  let newPollType = 'single';

  function addPollOption() {
    newPollOptions = [...newPollOptions, ''];
  }

  function removePollOption(index: number) {
    if (newPollOptions.length > 2) {
      newPollOptions = newPollOptions.filter((_, i) => i !== index);
    }
  }

  function updatePollOption(index: number, value: string) {
    newPollOptions[index] = value;
  }

  function createPoll() {
    if (!newPollQuestion.trim()) {
      alert('Please enter a poll question');
      return;
    }

    const validOptions = newPollOptions.filter(opt => opt.trim());
    if (validOptions.length < 2) {
      alert('Please provide at least 2 options');
      return;
    }

    const pollData = {
      question: newPollQuestion.trim(),
      options: validOptions,
      type: newPollType,
      allowMultiple: newPollType === 'multiple'
    };

    dispatch('create', pollData);
    onCreate?.(pollData);

    newPollQuestion = '';
    newPollOptions = ['', ''];
    newPollType = 'single';
  }

  function vote(optionIndex: number) {
    if (!poll) return;

    dispatch('vote', { pollId: poll.id, optionIndex });
    onVote?.(poll.id, optionIndex);
  }

  function getTotalVotes(p: Poll): number {
    return p.options?.reduce((sum, option) => sum + (option.voteCount || 0), 0) || 0;
  }

  function getVotePercentage(option: { voteCount?: number }, totalVotes: number): number {
    if (totalVotes === 0) return 0;
    return Math.round((option.voteCount || 0) / totalVotes * 100);
  }

  function hasUserVoted(p: Poll): boolean {
    return p.options?.some(opt => opt.isVoted) || false;
  }
</script>

<div class="poll-container">
  {#if isCreating}
    <!-- Poll Creation Form -->
    <div class="poll-create" in:fade>
      <Typography variant="h4">📊 Create Poll</Typography>

      <div class="form-group">
        <Typography variant="caption">Question:</Typography>
        <Input
          name="poll-question"
          bind:value={newPollQuestion}
          placeholder="What's your question?"
        />
      </div>

      <div class="form-group">
        <Typography variant="caption">Poll Type:</Typography>
        <div class="poll-type-selector">
          <Radio bind:group={newPollType} value="single" label="Single Choice" />
          <Radio bind:group={newPollType} value="multiple" label="Multiple Choice" />
        </div>
      </div>

      <div class="form-group">
        <Typography variant="caption">Options:</Typography>
        {#each newPollOptions as option, index}
          <div class="option-input">
            <Input
              bind:value={option}
              placeholder="Option {index + 1}"
            />
            {#if newPollOptions.length > 2}
              <Button
                type="button"
                class="remove-option"
                onclick={() => removePollOption(index)}
                aria-label="Remove option"
              >
                ×
              </Button>
            {/if}
          </div>
        {/each}
        {#if newPollOptions.length < 10}
          <Button type="button" class="add-option" onclick={addPollOption}>
            ➕ Add Option
          </Button>
        {/if}
      </div>

      <div class="poll-actions">
        <Button class="create-poll-btn" onclick={createPoll}>
          📊 Create Poll
        </Button>
      </div>
    </div>
  {:else if poll}
    <!-- Poll Display -->
    <div class="poll-display" in:fade>
      <div class="poll-header">
        <Typography variant="h4">📊 {poll.question}</Typography>
        {#if poll.allowMultiple}
          <Typography className="poll-type">Multiple Choice</Typography>
        {:else}
          <Typography className="poll-type">Single Choice</Typography>
        {/if}
      </div>

        <div class="poll-options">
          {#each poll.options || [] as option, index}
            {@const totalVotes = getTotalVotes(poll)}
            {@const percentage = getVotePercentage(option, totalVotes)}
            {@const hasVoted = hasUserVoted(poll)}

            <div class="poll-option" class:voted={option.isVoted}>
              <Button
                className="option-button"
                onclick={() => vote(index)}
                disabled={hasVoted && !poll.allowMultiple}
              >
                <div class="option-content">
                  <Typography className="option-text">{option.text}</Typography>
                  {#if hasVoted}
                    <Typography className="vote-count">
                      {option.voteCount || 0} votes ({percentage}%)
                    </Typography>
                  {/if}
                </div>
                {#if hasVoted}
                  <div class="vote-bar" style="width: {percentage}%"></div>
                {/if}
              </Button>
            </div>
          {/each}
        </div>

        {#if hasUserVoted(poll)}
          <div class="poll-results">
            <Typography className="total-votes">Total: {getTotalVotes(poll)} votes</Typography>
          </div>
        {/if}
    </div>
  {/if}
</div>

<style>
  .poll-container {
    margin: 1rem 0;
    background: rgba(20, 20, 20, 0.8);
    border: 1px solid rgba(0, 255, 255, 0.3);
    border-radius: 12px;
    overflow: hidden;
  }

  .poll-create, .poll-display {
    padding: 1.5rem;
  }

  .poll-create h4, .poll-display h4 {
    margin: 0 0 1rem 0;
    color: #00ffff;
    font-size: 1.1rem;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #ffffff;
    font-weight: 500;
  }

  input[type="text"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #00ffff;
    border-radius: 6px;
    background: rgba(30, 30, 30, 0.8);
    color: #00ffff;
    font-family: 'Courier New', monospace;
  }

  input[type="text"]:focus {
    outline: none;
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
  }

  .poll-type-selector {
    display: flex;
    gap: 1rem;
  }

  .type-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: #ffffff;
  }

  .type-option input[type="radio"] {
    accent-color: #00ffff;
  }

  .option-input {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
  }

  .option-input input {
    flex: 1;
  }

  .remove-option {
    background: rgba(255, 107, 107, 0.2);
    border: 1px solid #ff6b6b;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ff6b6b;
    cursor: pointer;
    font-size: 1rem;
  }

  .remove-option:hover {
    background: rgba(255, 107, 107, 0.3);
  }

  .add-option {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid #00ffff;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    color: #00ffff;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
  }

  .add-option:hover {
    background: rgba(0, 255, 255, 0.2);
  }

  .poll-actions {
    text-align: center;
    margin-top: 1.5rem;
  }

  .create-poll-btn {
    background: linear-gradient(45deg, #00ffff, #ff00ff);
    color: #000000;
    border: 1px solid #00ffff;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    cursor: pointer;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    transition: all 0.3s ease;
  }

  .create-poll-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
  }

  .poll-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .poll-type {
    background: rgba(0, 255, 255, 0.1);
    color: #00ffff;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
  }

  .poll-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .poll-option {
    position: relative;
  }

  .option-button {
    width: 100%;
    background: rgba(40, 40, 40, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: left;
    position: relative;
    overflow: hidden;
  }

  .option-button:hover:not(:disabled) {
    border-color: rgba(0, 255, 255, 0.5);
    background: rgba(40, 40, 40, 0.9);
  }

  .option-button:disabled {
    cursor: default;
  }

  .poll-option.voted .option-button {
    border-color: #00ffff;
    background: rgba(0, 255, 255, 0.1);
  }

  .option-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 2;
  }

  .option-text {
    color: #ffffff;
    font-weight: 500;
  }

  .vote-count {
    color: #00ffff;
    font-size: 0.9rem;
    font-weight: 500;
  }

  .vote-bar {
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    background: linear-gradient(90deg, rgba(0, 255, 255, 0.3), rgba(255, 0, 255, 0.3));
    transition: width 0.5s ease;
    z-index: 1;
  }

  .poll-results {
    margin-top: 1rem;
    text-align: center;
  }

  .total-votes {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .poll-create, .poll-display {
      padding: 1rem;
    }

    .poll-type-selector {
      flex-direction: column;
      gap: 0.5rem;
    }

    .option-content {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.25rem;
    }
  }
</style>