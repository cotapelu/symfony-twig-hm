<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { fade, slide } from 'svelte/transition';

  interface Thread {
    id: string;
    title?: string;
    replies?: Array<{
      sender?: { name?: string };
      createdAt: string;
      content: string;
    }>;
    lastActivity?: string;
  }

  export let thread: Thread | null = null;
  export let isExpanded = false;
  export let onReply: ((thread: Thread) => void) | null = null;
  export let onExpand: ((thread: Thread, expanded: boolean) => void) | null = null;

  const dispatch = createEventDispatcher();

  function handleReply() {
    if (!thread) return;
    dispatch('reply', { threadId: thread.id });
    onReply?.(thread);
  }

  function handleExpand() {
    if (!thread) return;
    dispatch('expand', { threadId: thread.id, expanded: !isExpanded });
    onExpand?.(thread, !isExpanded);
  }

  function formatTimestamp(timestamp: string | null | undefined): string {
    if (!timestamp) return '';
    return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }
</script>

{#if thread}
  <div class="thread-item" class:expanded={isExpanded}>
    <div class="thread-header" onclick={handleExpand} role="button" tabindex="0">
      <div class="thread-info">
        <div class="thread-title">
          <span class="thread-icon">🧵</span>
          {thread.title || 'Thread'}
        </div>
        <div class="thread-meta">
          <span class="thread-replies">{thread.replies?.length || 0} replies</span>
          <span class="thread-last-activity">
            {thread.lastActivity ? formatTimestamp(thread.lastActivity) : ''}
          </span>
        </div>
      </div>
      <div class="thread-toggle">
        {#if isExpanded}
          ▲
        {:else}
          ▼
        {/if}
      </div>
    </div>

    {#if isExpanded}
      <div class="thread-content" transition:slide={{ duration: 300 }}>
        <!-- Thread messages would go here -->
        <div class="thread-messages">
          {#each thread.replies || [] as reply}
            <div class="thread-reply" in:fade={{ delay: 100 }}>
              <div class="reply-avatar">
                {reply.sender?.name?.charAt(0)?.toUpperCase() || '?'}
              </div>
              <div class="reply-content">
                <div class="reply-header">
                  <span class="reply-sender">{reply.sender?.name || 'Unknown'}</span>
                  <span class="reply-time">{formatTimestamp(reply.createdAt)}</span>
                </div>
                <div class="reply-text">{reply.content}</div>
              </div>
            </div>
          {/each}
        </div>

        <!-- Reply form -->
        <div class="thread-reply-form">
          <button class="reply-btn" onclick={handleReply}>
            💬 Reply to thread
          </button>
        </div>
      </div>
    {/if}
  </div>
{/if}

<style>
  .thread-item {
    background: rgba(20, 20, 20, 0.8);
    border: 1px solid rgba(0, 255, 255, 0.3);
    border-radius: 8px;
    margin: 0.5rem 0;
    overflow: hidden;
    transition: all 0.3s ease;
  }

  .thread-item:hover {
    border-color: rgba(0, 255, 255, 0.6);
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
  }

  .thread-item.expanded {
    border-color: #00ffff;
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
  }

  .thread-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .thread-header:hover {
    background: rgba(0, 255, 255, 0.05);
  }

  .thread-info {
    flex: 1;
  }

  .thread-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #00ffff;
    font-weight: 500;
    margin-bottom: 0.25rem;
  }

  .thread-icon {
    font-size: 1rem;
  }

  .thread-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.7);
  }

  .thread-toggle {
    color: #00ffff;
    font-size: 1.2rem;
    transition: transform 0.3s ease;
  }

  .thread-item.expanded .thread-toggle {
    transform: rotate(180deg);
  }

  .thread-content {
    border-top: 1px solid rgba(0, 255, 255, 0.2);
    background: rgba(10, 10, 10, 0.5);
  }

  .thread-messages {
    max-height: 300px;
    overflow-y: auto;
  }

  .thread-reply {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .thread-reply:last-child {
    border-bottom: none;
  }

  .reply-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(45deg, #ff00ff, #00ffff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000000;
    font-weight: bold;
    font-size: 0.8rem;
    flex-shrink: 0;
  }

  .reply-content {
    flex: 1;
    min-width: 0;
  }

  .reply-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
  }

  .reply-sender {
    color: #ffffff;
    font-weight: 500;
    font-size: 0.9rem;
  }

  .reply-time {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
  }

  .reply-text {
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.4;
    word-wrap: break-word;
  }

  .thread-reply-form {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }

  .reply-btn {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid #00ffff;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    color: #00ffff;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    width: 100%;
  }

  .reply-btn:hover {
    background: rgba(0, 255, 255, 0.2);
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .thread-meta {
      flex-direction: column;
      gap: 0.25rem;
      align-items: flex-start;
    }

    .thread-reply {
      padding: 0.5rem 0.75rem;
    }

    .reply-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.25rem;
    }
  }
</style>