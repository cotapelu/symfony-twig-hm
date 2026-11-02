<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { subscribeToTopic, PHP } from '$lib/api';
  import { showToast } from '$lib/stores/global';
  import { Button, Input, Typography, Radio } from '$lib/components';

  export let conversationId = '';

  let showPollCreator = false;
  interface Poll {
    id: string;
    question: string;
    options: string[];
    votes: number[];
    created_by: string;
  }

  let pollQuestion = '';
  let pollOptions = ['', ''];
  let polls: Poll[] = [];

  const dispatch = createEventDispatcher();

  // Subscribe to poll updates
  subscribeToTopic('polls', (data) => {
    if (data.type === 'poll_created') {
      polls = [...polls, data.poll];
    }
  });

  function togglePollCreator() {
    showPollCreator = !showPollCreator;
  }

  function addPollOption() {
    pollOptions = [...pollOptions, ''];
  }

  function removePollOption(index: number) {
    if (pollOptions.length > 2) {
      pollOptions = pollOptions.filter((_, i) => i !== index);
    }
  }

  function updateOption(index, value) {
    pollOptions[index] = value;
    pollOptions = [...pollOptions]; // Trigger reactivity
  }

  async function createPollHandler() {
    if (!pollQuestion.trim()) return;
    if (pollOptions.filter(opt => opt.trim()).length < 2) return;

    try {
      const pollData = {
        question: pollQuestion.trim(),
        options: pollOptions.filter(opt => opt.trim()),
        conversationId: conversationId
      };

      await PHP.createPoll(pollData);

      // Reset form
      pollQuestion = '';
      pollOptions = ['', ''];
      showPollCreator = false;

      showToast('Poll created successfully!', 'success');
    } catch (error) {
      console.error('Failed to create poll:', error);
      showToast('Failed to create poll', 'error');
    }
  }

  function voteOnPoll(pollId, optionIndex) {
    // Mock voting - in real app, this would call an API
    showToast(`Voted on poll option ${optionIndex + 1}`, 'info');
  }
</script>

<div class="poll-system">
  <Button className="create-poll-btn" onclick={togglePollCreator}>
    📊 Create Poll
  </Button>

  {#if showPollCreator}
    <div class="poll-creator">
      <Typography variant="h4">Create a Poll</Typography>

      <div class="form-group">
        <Typography variant="caption">Question:</Typography>
        <Input
          bind:value={pollQuestion}
          placeholder="What's your question?"
        />
      </div>

      <div class="options-section">
        <Typography variant="caption">Options:</Typography>
        {#each pollOptions as option, index}
            <div class="option-input">
              <Input
                bind:value={option}
                placeholder="Option {index + 1}"
              />
            {#if pollOptions.length > 2}
              <Button
                className="remove-option"
                onclick={() => removePollOption(index)}
              >
                ×
              </Button>
            {/if}
          </div>
        {/each}

        <Button className="add-option" onclick={addPollOption}>
          + Add Option
        </Button>
      </div>

      <div class="creator-actions">
        <Button onclick={() => showPollCreator = false}>Cancel</Button>
        <Button onclick={createPollHandler} disabled={!pollQuestion.trim()}>
          Create Poll
        </Button>
      </div>
    </div>
  {/if}

  {#if polls.length > 0}
    <div class="polls-list">
      {#each polls as poll}
        <div class="poll-item">
          <Typography variant="h4">{poll.question}</Typography>
          <div class="poll-options">
            {#each poll.options as option, index}
              <Button
                class="poll-option"
                onclick={() => voteOnPoll(poll.id, index)}
              >
                {option} ({poll.votes[index] || 0} votes)
              </Button>
            {/each}
          </div>
          <Typography variant="caption">Created by {poll.created_by}</Typography>
        </div>
      {/each}
    </div>
  {/if}
</div>

<style>
  .poll-system {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(20, 20, 20, 0.8);
    border-radius: 12px;
    border: 1px solid rgba(0, 255, 255, 0.3);
  }

  .create-poll-btn {
    background: rgba(255, 0, 255, 0.1);
    border: 1px solid #ff00ff;
    border-radius: 20px;
    padding: 0.5rem 1rem;
    color: #ff00ff;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .create-poll-btn:hover {
    background: rgba(255, 0, 255, 0.2);
    box-shadow: 0 0 10px rgba(255, 0, 255, 0.5);
  }

  .poll-creator {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 8px;
  }

  .poll-creator h4 {
    margin: 0 0 1rem 0;
    color: #ff00ff;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #ffffff;
    font-weight: 500;
  }

  .form-group input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
  }

  .options-section {
    margin-bottom: 1rem;
  }

  .option-input {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
  }

  .option-input input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
  }

  .remove-option {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-weight: bold;
  }

  .add-option {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.5rem 1rem;
    cursor: pointer;
    font-size: 0.9rem;
  }

  .creator-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
  }

  .creator-actions button {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
  }

  .creator-actions button:first-child {
    background: #6c757d;
    color: white;
  }

  .creator-actions button:last-child {
    background: #007bff;
    color: white;
  }

  .creator-actions button:last-child:disabled {
    background: #6c757d;
    cursor: not-allowed;
  }

  .polls-list {
    margin-top: 1rem;
  }

  .poll-item {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 0, 255, 0.3);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
  }

  .poll-item h4 {
    margin: 0 0 1rem 0;
    color: #ff00ff;
  }

  .poll-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
  }

  .poll-option {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid #00ffff;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    color: #00ffff;
    cursor: pointer;
    text-align: left;
    transition: all 0.3s ease;
  }

  .poll-option:hover {
    background: rgba(0, 255, 255, 0.2);
  }

  .poll-item small {
    color: #cccccc;
    font-size: 0.8rem;
  }
</style>