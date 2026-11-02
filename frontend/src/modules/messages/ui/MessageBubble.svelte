<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import MessageReactions from '$lib/components/organisms/MessageReactions.svelte';
  import MessageThread from '$lib/components/organisms/MessageThread.svelte';
  import { markMessageRead } from '$lib/api';

  /** @type {import('$lib/types').Message} */
  export let message;

  /** @type {string} */
  export let conversationId = '';

  const dispatch = createEventDispatcher();

  /**
   * @param {string | Date} timestamp
   * @returns {string}
   */
  function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function markAsRead() {
    if (message.id) {
      markMessageRead(message.id);
    }
  }
</script>

<div class="message-bubble" class:own={message.senderId === 'currentUser'}>
  <div class="message-content">
    <p>{message.content}</p>
  </div>
  <div class="message-meta">
    <span class="timestamp">{formatTime(message.createdAt)}</span>
    {#if message.senderId !== 'currentUser'}
      <span class="sender">{message.senderId}</span>
    {/if}
  </div>

  <!-- Message Reactions -->
  <MessageReactions
    messageId={message.id}
    currentReactions={message.reactions || {}}
    currentUserId="currentUser"
    on:reactionUpdate={(e) => dispatch('reactionUpdate', e.detail)}
  />
</div>

<style>
  .message-bubble {
    display: flex;
    flex-direction: column;
    margin-bottom: 1rem;
    max-width: 70%;
  }

  .message-bubble.own {
    align-self: flex-end;
    align-items: flex-end;
  }

  .message-content {
    background: #e3f2fd;
    padding: 0.75rem 1rem;
    border-radius: 18px;
    border-bottom-left-radius: 4px;
  }

  .message-bubble.own .message-content {
    background: #1976d2;
    color: white;
    border-bottom-left-radius: 18px;
    border-bottom-right-radius: 4px;
  }

  .message-meta {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.25rem;
    font-size: 0.8rem;
    color: #666;
  }

  .message-bubble.own .message-meta {
    justify-content: flex-end;
  }

  .timestamp {
    opacity: 0.7;
  }

  .sender {
    font-weight: 500;
  }

  .message-actions {
    margin-top: 0.5rem;
    cursor: pointer;
  }

  .unread-indicator {
    color: #00ffff;
    font-size: 0.8rem;
  }
</style>