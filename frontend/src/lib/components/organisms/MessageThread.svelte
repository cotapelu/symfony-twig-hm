<script lang="ts">
   import { createEventDispatcher } from 'svelte';
   import { subscribeToTopic, createThread, PHP } from '$lib/api';
   import Button from '../atoms/Button.svelte';
   import Textarea from '../Textarea.svelte';
   import Typography from '../atoms/Typography.svelte';

  export let messageId = '';
  export let conversationId = '';

  let showThreadInput = false;
  let threadContent = '';
  let threads = [];

  const dispatch = createEventDispatcher();

  // Subscribe to thread updates
  subscribeToTopic('thread/' + messageId, (data) => {
    if (data.type === 'thread_created') {
      threads = [...threads, data.threadMessage];
    }
  });

  function toggleThreadInput() {
    showThreadInput = !showThreadInput;
  }

  async function submitThread() {
    if (!threadContent.trim()) return;

    try {
      await createThread(messageId, {
        content: threadContent.trim(),
        conversationId: conversationId
      });

      threadContent = '';
      showThreadInput = false;
    } catch (error) {
      console.error('Failed to create thread:', error);
    }
  }

  function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      submitThread();
    }
  }
</script>

<div class="thread-container">
  <Button className="thread-toggle" onclick={toggleThreadInput}>
    💬 {threads.length} thread{threads.length !== 1 ? 's' : ''}
  </Button>

  {#if showThreadInput}
    <div class="thread-input">
      <Textarea
        bind:value={threadContent}
        placeholder="Reply in thread..."
        on:keypress={handleKeyPress}
      />
      <Button onclick={submitThread}>Reply</Button>
    </div>
  {/if}

  {#if threads.length > 0}
    <div class="threads-list">
      {#each threads as thread}
         <div class="thread-item">
           <Typography variant="body">{thread.content}</Typography>
           <Typography variant="caption">by {thread.senderId} at {thread.created_at}</Typography>
         </div>
      {/each}
    </div>
  {/if}
</div>

<style>
  .thread-container {
    margin-top: 0.5rem;
    border-left: 2px solid #00ffff;
    padding-left: 1rem;
  }

  .thread-toggle {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid #00ffff;
    border-radius: 15px;
    padding: 0.25rem 0.75rem;
    color: #00ffff;
    cursor: pointer;
    font-size: 0.8rem;
  }

  .thread-toggle:hover {
    background: rgba(0, 255, 255, 0.2);
  }

  .thread-input {
    margin-top: 0.5rem;
    display: flex;
    gap: 0.5rem;
  }

  .thread-input textarea {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    resize: vertical;
    min-height: 60px;
  }

  .thread-input button {
    padding: 0.5rem 1rem;
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
  }

  .thread-input button:hover {
    background: #1565c0;
  }

  .threads-list {
    margin-top: 1rem;
    max-height: 200px;
    overflow-y: auto;
  }

  .thread-item {
    background: rgba(20, 20, 20, 0.5);
    border: 1px solid rgba(0, 255, 255, 0.2);
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
  }

  .thread-item p {
    margin: 0 0 0.5rem 0;
    color: #ffffff;
  }

  .thread-item small {
    color: #cccccc;
    font-size: 0.8rem;
  }
</style>