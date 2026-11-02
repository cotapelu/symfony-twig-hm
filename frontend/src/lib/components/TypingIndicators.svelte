<script lang="ts">
   import { createEventDispatcher } from 'svelte';
   import { fade, slide, scale } from 'svelte/transition';

  export let typingUsers = []; // Array of {userId, name, avatar, timestamp}
  export let maxDisplay = 3; // Max users to show
  export let showPresence = true;
  export let conversationId = null;

  const dispatch = createEventDispatcher();

  // Clean up old typing users (older than 10 seconds)
  $: if (typingUsers.length > 0) {
    const now = Date.now();
    const cleanedUsers = typingUsers.filter(user =>
      now - (user.timestamp || 0) < 10000 // 10 seconds
    );

    if (cleanedUsers.length !== typingUsers.length) {
      typingUsers = cleanedUsers;
      dispatch('usersUpdated', { typingUsers: cleanedUsers });
    }
  }

  // Format display text
  $: displayText = (() => {
    if (typingUsers.length === 0) return '';

    const names = typingUsers.slice(0, maxDisplay).map(user => user.name || user.userId);
    let text = '';

    if (names.length === 1) {
      text = `${names[0]} is typing`;
    } else if (names.length === 2) {
      text = `${names[0]} and ${names[1]} are typing`;
    } else if (names.length === 3) {
      text = `${names[0]}, ${names[1]} and ${names[2]} are typing`;
    } else {
      text = `${names[0]}, ${names[1]} and ${names.length - 2} others are typing`;
    }

    // Add animated dots
    return text + '...';
  })();

  // Get presence status for users
  function getPresenceStatus(user) {
    // Mock presence - in real app, this would come from BE
    return user.presence || 'online';
  }

  function getPresenceColor(status) {
    switch (status) {
      case 'online': return '#10b981';
      case 'away': return '#f59e0b';
      case 'busy': return '#ef4444';
      case 'offline': return '#6b7280';
      default: return '#6b7280';
    }
  }

  function getPresenceIcon(status) {
    switch (status) {
      case 'online': return '🟢';
      case 'away': return '🟡';
      case 'busy': return '🔴';
      case 'offline': return '⚫';
      default: return '⚫';
    }
  }
</script>

{#if typingUsers.length > 0}
  <div class="typing-indicators" in:slide={{ duration: 300 }} out:fade={{ duration: 200 }}>
    <div class="typing-avatars">
      {#each typingUsers.slice(0, maxDisplay) as user, index}
        <div
          class="typing-avatar"
          style="z-index: {maxDisplay - index}"
          in:scale={{ duration: 200, delay: index * 50 }}
        >
          {#if user.avatar}
            <img src={user.avatar} alt="{user.name || user.userId}'s avatar" />
          {:else}
            <div class="avatar-placeholder">
              {(user.name || user.userId).charAt(0).toUpperCase()}
            </div>
          {/if}

          {#if showPresence}
            <div
              class="presence-indicator"
              style="background: {getPresenceColor(getPresenceStatus(user))}"
              title="{user.name || user.userId} is {getPresenceStatus(user)}"
            >
              {getPresenceIcon(getPresenceStatus(user))}
            </div>
          {/if}

          <!-- Typing animation dots -->
          <div class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
      {/each}

      {#if typingUsers.length > maxDisplay}
        <div class="overflow-indicator" in:scale={{ duration: 200, delay: 200 }}>
          +{typingUsers.length - maxDisplay}
        </div>
      {/if}
    </div>

    <div class="typing-text">
      <span class="typing-message">{displayText}</span>
      <div class="typing-pulse"></div>
    </div>
  </div>
{/if}

<style>
  .typing-indicators {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    margin: 0.5rem 0;
    background: rgba(20, 20, 20, 0.8);
    border-radius: 20px;
    border: 1px solid rgba(0, 255, 255, 0.2);
    max-width: fit-content;
    animation: typingGlow 2s ease-in-out infinite alternate;
  }

  @keyframes typingGlow {
    0% { box-shadow: 0 0 5px rgba(0, 255, 255, 0.1); }
    100% { box-shadow: 0 0 15px rgba(0, 255, 255, 0.3); }
  }

  .typing-avatars {
    display: flex;
    align-items: center;
    position: relative;
  }

  .typing-avatar {
    position: relative;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid var(--bg-card, #1e293b);
    overflow: hidden;
    margin-left: -8px;
    transition: transform 0.2s ease;
  }

  .typing-avatar:first-child {
    margin-left: 0;
  }

  .typing-avatar:hover {
    transform: scale(1.1);
    z-index: 10 !important;
  }

  .typing-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .avatar-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--accent-primary, #3b82f6), var(--accent-secondary, #6366f1));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
  }

  .presence-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid var(--bg-card, #1e293b);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
  }

  .typing-dots {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 2px;
  }

  .typing-dots span {
    width: 4px;
    height: 4px;
    background: var(--accent-primary, #3b82f6);
    border-radius: 50%;
    animation: typingDot 1.4s ease-in-out infinite;
  }

  .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
  .typing-dots span:nth-child(2) { animation-delay: -0.16s; }

  @keyframes typingDot {
    0%, 80%, 100% {
      transform: scale(0.8);
      opacity: 0.6;
    }
    40% {
      transform: scale(1);
      opacity: 1;
    }
  }

  .overflow-indicator {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bg-secondary, #374151);
    border: 2px solid var(--bg-card, #1e293b);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-primary, #ffffff);
    font-size: 12px;
    font-weight: bold;
    margin-left: -8px;
  }

  .typing-text {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .typing-message {
    color: var(--text-primary, #ffffff);
    font-size: 0.9rem;
    font-style: italic;
    opacity: 0.8;
  }

  .typing-pulse {
    width: 8px;
    height: 8px;
    background: var(--accent-primary, #3b82f6);
    border-radius: 50%;
    animation: typingPulse 2s ease-in-out infinite;
  }

  @keyframes typingPulse {
    0%, 100% {
      opacity: 0.4;
      transform: scale(0.8);
    }
    50% {
      opacity: 1;
      transform: scale(1.2);
    }
  }

  /* Cyberpunk theme special effects */
  :global([data-theme="cyberpunk"]) .typing-indicators {
    border-color: rgba(0, 255, 255, 0.4);
    background: rgba(20, 20, 20, 0.9);
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
  }

  :global([data-theme="cyberpunk"]) .avatar-placeholder {
    background: linear-gradient(135deg, #00ffff, #ff00ff);
  }

  :global([data-theme="cyberpunk"]) .typing-dots span {
    background: #00ffff;
  }

  :global([data-theme="cyberpunk"]) .typing-pulse {
    background: #00ffff;
    box-shadow: 0 0 8px rgba(0, 255, 255, 0.6);
  }

  /* Dark theme adjustments */
  :global([data-theme="dark"]) .typing-indicators {
    background: rgba(30, 41, 59, 0.8);
    border-color: rgba(255, 255, 255, 0.1);
  }

  /* Mobile responsive */
  @media (max-width: 480px) {
    .typing-indicators {
      padding: 0.4rem 0.8rem;
      gap: 0.5rem;
    }

    .typing-avatar {
      width: 28px;
      height: 28px;
    }

    .overflow-indicator {
      width: 28px;
      height: 28px;
      font-size: 10px;
    }

    .typing-message {
      font-size: 0.85rem;
    }
  }

  /* Reduced motion support */
  @media (prefers-reduced-motion: reduce) {
    .typing-indicators {
      animation: none;
    }

    .typing-avatar {
      transition: none;
    }

    .typing-dots span {
      animation: none;
    }

    .typing-pulse {
      animation: none;
    }
  }

  /* High contrast mode */
  @media (prefers-contrast: high) {
    .typing-indicators {
      border-width: 2px;
    }

    .typing-avatar {
      border-width: 3px;
    }

    .presence-indicator {
      border-width: 3px;
    }
  }
</style>