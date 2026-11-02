<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import EmojiPicker from '../EmojiPicker.svelte';

  interface Reactions {
    [emoji: string]: string[];
  }

  interface ReactionInfo {
    emoji: string;
    count: number;
    hasReacted: boolean;
  }

  export let messageId = '';
  export let currentReactions: Reactions = {};
  export let currentUserId = '';
  export let showPicker = false;

  const dispatch = createEventDispatcher();

  let recentEmojis = ['👍', '❤️', '😂', '😮', '😢', '😡'];
  const quickReactions = ['👍', '❤️', '😂', '😮', '😢', '😡'];

  function toggleReaction(emoji: string) {
    const userReactions = currentReactions[emoji] || [];
    const hasReacted = userReactions.includes(currentUserId);

    if (hasReacted) {
      const newUserReactions = userReactions.filter(id => id !== currentUserId);
      if (newUserReactions.length === 0) {
        delete currentReactions[emoji];
      } else {
        currentReactions[emoji] = newUserReactions;
      }
    } else {
      currentReactions[emoji] = [...userReactions, currentUserId];
    }

    if (!recentEmojis.includes(emoji)) {
      recentEmojis = [emoji, ...recentEmojis.slice(0, 5)];
    }

    dispatch('reactionUpdate', {
      messageId,
      reactions: { ...currentReactions }
    });
  }

  function handleEmojiSelect(event: CustomEvent<{ emoji: string }>) {
    const { emoji } = event.detail;
    toggleReaction(emoji);
    showPicker = false;
  }

  function getReactionCount(emoji: string) {
    return currentReactions[emoji]?.length || 0;
  }

  function hasUserReacted(emoji: string) {
    return currentReactions[emoji]?.includes(currentUserId) || false;
  }

  function getSortedReactions(): ReactionInfo[] {
    return Object.entries(currentReactions)
      .sort(([, a], [, b]) => b.length - a.length)
      .map(([emoji, users]) => ({ emoji, count: users.length, hasReacted: users.includes(currentUserId) }));
  }
</script>

<div class="message-reactions">
  <!-- Existing reactions -->
  {#if Object.keys(currentReactions).length > 0}
    <div class="reaction-list" role="group" aria-label="Message reactions">
      {#each getSortedReactions() as reaction}
        <button
          class="reaction-btn {reaction.hasReacted ? 'reacted' : ''}"
          onclick={() => toggleReaction(reaction.emoji)}
          aria-label="{reaction.count} {reaction.emoji} reaction{reaction.count !== 1 ? 's' : ''}"
          title="Click to {reaction.hasReacted ? 'remove' : 'add'} reaction"
        >
          <span class="reaction-emoji">{reaction.emoji}</span>
          <span class="reaction-count">{reaction.count}</span>
        </button>
      {/each}
    </div>
  {/if}

  <!-- Add reaction button -->
  <div class="add-reaction">
    <button
      class="add-reaction-btn"
      onclick={() => showPicker = !showPicker}
      aria-label="Add reaction"
      aria-expanded={showPicker}
      title="Add reaction"
    >
      <span class="add-icon">+</span>
    </button>

    <!-- Quick reactions -->
    <div class="quick-reactions">
      {#each quickReactions as emoji}
        <button
          class="quick-reaction-btn {hasUserReacted(emoji) ? 'reacted' : ''}"
          onclick={() => toggleReaction(emoji)}
          aria-label="React with {emoji}"
          title="React with {emoji}"
        >
          {emoji}
        </button>
      {/each}
    </div>
  </div>

  <!-- Emoji picker -->
  {#if showPicker}
    <div class="emoji-picker-container">
      <EmojiPicker
        {recentEmojis}
        on:select={handleEmojiSelect}
        on:close={() => showPicker = false}
      />
    </div>
  {/if}
</div>

<style>
  .message-reactions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    flex-wrap: wrap;
  }

  .reaction-list {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
  }

  .reaction-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    background: white;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.15s ease;
    user-select: none;
  }

  .reaction-btn:hover {
    border-color: #d1d5db;
    background: #f9fafb;
  }

  .reaction-btn.reacted {
    border-color: #3b82f6;
    background: #eff6ff;
  }

  .reaction-btn.reacted:hover {
    border-color: #2563eb;
    background: #dbeafe;
  }

  .reaction-emoji {
    font-size: 1rem;
    line-height: 1;
  }

  .reaction-count {
    color: #374151;
    font-weight: 500;
    min-width: 12px;
    text-align: center;
  }

  .add-reaction {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .add-reaction-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: 1px solid #e5e7eb;
    border-radius: 50%;
    background: white;
    cursor: pointer;
    color: #6b7280;
    font-size: 1.2rem;
    font-weight: 600;
    transition: all 0.15s ease;
  }

  .add-reaction-btn:hover {
    border-color: #3b82f6;
    background: #eff6ff;
    color: #3b82f6;
  }

  .quick-reactions {
    display: flex;
    gap: 0.25rem;
  }

  .quick-reaction-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: 1px solid #e5e7eb;
    border-radius: 50%;
    background: white;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.15s ease;
  }

  .quick-reaction-btn:hover {
    border-color: #d1d5db;
    background: #f9fafb;
    transform: scale(1.1);
  }

  .quick-reaction-btn.reacted {
    border-color: #3b82f6;
    background: #eff6ff;
  }

  .emoji-picker-container {
    position: relative;
    margin-top: 0.5rem;
  }

  /* Animation */
  .reaction-btn {
    animation: popIn 0.2s ease-out;
  }

  @keyframes popIn {
    from {
      opacity: 0;
      transform: scale(0.8);
    }
    to {
      opacity: 1;
      transform: scale(1);
    }
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .reaction-btn {
      border-color: #374151;
      background: #1f2937;
      color: #f9fafb;
    }

    .reaction-btn:hover {
      border-color: #4b5563;
      background: #374151;
    }

    .reaction-btn.reacted {
      border-color: #3b82f6;
      background: #1e3a8a;
    }

    .reaction-btn.reacted:hover {
      border-color: #2563eb;
      background: #1d4ed8;
    }

    .add-reaction-btn {
      border-color: #374151;
      background: #1f2937;
      color: #9ca3af;
    }

    .add-reaction-btn:hover {
      border-color: #3b82f6;
      background: #1e3a8a;
      color: #3b82f6;
    }

    .quick-reaction-btn {
      border-color: #374151;
      background: #1f2937;
    }

    .quick-reaction-btn:hover {
      border-color: #4b5563;
      background: #374151;
    }

    .quick-reaction-btn.reacted {
      border-color: #3b82f6;
      background: #1e3a8a;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .message-reactions {
      gap: 0.25rem;
    }

    .reaction-btn {
      padding: 0.2rem 0.4rem;
      font-size: 0.8rem;
    }

    .reaction-emoji {
      font-size: 0.9rem;
    }

    .add-reaction-btn,
    .quick-reaction-btn {
      width: 24px;
      height: 24px;
      font-size: 0.9rem;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .reaction-btn {
      animation: none;
    }

    .quick-reaction-btn:hover {
      transform: none;
    }
  }
</style>
