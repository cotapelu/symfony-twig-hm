<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import { storiesStore } from '$lib/stores/stories';

  interface StoryItem {
    id: string;
    type: 'image' | 'video';
    url: string;
    text?: string;
    duration?: number;
  }

  interface Story {
    id: string;
    userName: string;
    userAvatar?: string;
    createdAt: string;
    items: StoryItem[];
  }

  export let stories: Story[] = [];
  export let currentIndex = 0;
  export let onClose: () => void = () => {};
  export let onNextReaction: (data: { storyId: string; emoji: string; itemId?: string }) => void = () => {};

  let autoPlayTimer: ReturnType<typeof setInterval> | undefined;
  let progress = 0;
  let viewingIndex = currentIndex;
  let container: HTMLDivElement | undefined;

  $: currentStory = stories[viewingIndex] || null;
  $: storyItems = currentStory?.items || [];
  $: currentItemIndex = 0;
  $: currentItem = storyItems[currentItemIndex] || null;

  function nextStory() {
    if (viewingIndex < stories.length - 1) {
      viewingIndex++;
      currentItemIndex = 0;
      progress = 0;
    } else {
      onClose();
    }
  }

  function prevStory() {
    if (viewingIndex > 0) {
      viewingIndex--;
      currentItemIndex = 0;
      progress = 0;
    }
  }

  function nextItem() {
    if (currentItemIndex < storyItems.length - 1) {
      currentItemIndex++;
      progress = 0;
    } else {
      nextStory();
    }
  }

  function prevItem() {
    if (currentItemIndex > 0) {
      currentItemIndex--;
      progress = 0;
    }
  }

  function handleReaction(emoji: string) {
    if (onNextReaction) {
      onNextReaction({
        storyId: currentStory?.id || '',
        emoji,
        itemId: currentItem?.id
      });
    }
  }

  function startAutoPlay() {
    clearInterval(autoPlayTimer);
    progress = 0;
    autoPlayTimer = setInterval(() => {
      progress += 100 / (currentItem?.duration || 5) / 10;
      if (progress >= 100) {
        nextItem();
      }
    }, 100);
  }

  function stopAutoPlay() {
    clearInterval(autoPlayTimer);
  }

  onMount(() => {
    startAutoPlay();
    container?.focus();

    return () => {
      stopAutoPlay();
    };
  });

  onDestroy(() => {
    stopAutoPlay();
  });

  function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'ArrowRight') nextStory();
    if (e.key === 'ArrowLeft') prevStory();
    if (e.key === 'ArrowUp') nextItem();
    if (e.key === 'ArrowDown') prevItem();
    if (e.key === 'Escape') onClose();
  }
</script>

<svelte:window onkeydown={handleKeyDown} />

<div 
  class="story-viewer"
  bind:this={container}
  tabindex="-1"
  onmouseenter={stopAutoPlay}
  onmouseleave={startAutoPlay}
>
  <div class="story-header">
    <div class="story-progress">
      {#each storyItems as _, index}
        <div 
          class="progress-bar {index <= currentItemIndex ? 'active' : ''} {index < currentItemIndex ? 'completed' : ''}"
          style="flex: 1"
        >
          <div 
            class="progress-fill"
            style="width: {index === currentItemIndex ? progress + '%' : '100%'}"
          ></div>
        </div>
      {/each}
    </div>

    <div class="story-user-info">
      <img 
        src={currentStory?.userAvatar || '/default-avatar.png'} 
        alt={currentStory?.userName}
        class="user-avatar"
      />
      <div class="user-details">
        <span class="user-name">{currentStory?.userName}</span>
        <span class="story-time">
          {new Date(currentStory?.createdAt).toLocaleTimeString()}
        </span>
      </div>
      <button 
        class="close-btn"
        onclick={onClose}
        aria-label="Close story"
      >
        ✕
      </button>
    </div>
  </div>

  <div class="story-content">
    {#if currentItem}
      {#if currentItem.type === 'image'}
        <img 
          src={currentItem.url} 
          alt="Story content"
          class="story-media"
        />
      {:else if currentItem.type === 'video'}
        <video 
          src={currentItem.url}
          autoplay
          muted
          loop
          class="story-media"
        ></video>
      {/if}

      {#if currentItem.text}
        <div class="story-text">
          {currentItem.text}
        </div>
      {/if}
    {/if}
  </div>

  <div class="story-navigation">
    <button 
      class="nav-button left"
      onclick={prevStory}
      aria-label="Previous story"
    >
      ◀
    </button>
    <button 
      class="nav-button right"
      onclick={nextStory}
      aria-label="Next story"
    >
      ▶
    </button>
  </div>

  <div class="story-reactions">
    {#each ['❤️', '😂', '😮', '😢', '😡'] as emoji}
      <button 
        class="reaction-button"
        onclick={() => handleReaction(emoji)}
        aria-label={`React with ${emoji}`}
      >
        {emoji}
      </button>
    {/each}
  </div>

  <div class="story-actions">
    <button 
      class="action-button"
      aria-label="Reply to story"
    >
      💬 Reply
    </button>
    <button 
      class="action-button"
      aria-label="Share story"
    >
      📤 Share
    </button>
    <button 
      class="action-button"
      aria-label="More options"
    >
      ⋯
    </button>
  </div>
</div>

<style>
  .story-viewer {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.95);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    outline: none;
  }

  .story-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    background: linear-gradient(to bottom, rgba(0,0,0,0.5), transparent);
    z-index: 10;
  }

  .story-progress {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 1rem;
  }

  .progress-bar {
    height: 3px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 1.5px;
    overflow: hidden;
  }

  .progress-bar.active .progress-fill {
    background: white;
    transition: width 0.1s linear;
  }

  .progress-bar.completed {
    background: rgba(255, 255, 255, 0.5);
  }

  .progress-bar.completed .progress-fill {
    width: 100% !important;
  }

  .story-user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
  }

  .user-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .user-name {
    font-weight: 600;
    color: white;
    font-size: 1rem;
  }

  .story-time {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
  }

  .close-btn {
    margin-left: auto;
    background: rgba(0, 0, 0, 0.3);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .story-content {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4rem;
    position: relative;
  }

  .story-media {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 0.5rem;
  }

  .story-text {
    position: absolute;
    bottom: 6rem;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 1rem;
    border-radius: 0.5rem;
    max-width: 80%;
    text-align: center;
  }

  .story-navigation {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    pointer-events: none;
  }

  .nav-button {
    flex: 1;
    background: none;
    border: none;
    color: white;
    font-size: 2rem;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: auto;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .story-viewer:hover .nav-button {
    opacity: 0.7;
  }

  .nav-button:hover {
    opacity: 1 !important;
  }

  .nav-button.left {
    padding-left: 1rem;
  }

  .nav-button.right {
    padding-right: 1rem;
  }

  .story-reactions {
    position: absolute;
    bottom: 4rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.5rem;
    background: rgba(0, 0, 0, 0.8);
    padding: 0.5rem;
    border-radius: 2rem;
  }

  .reaction-button {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    transition: transform 0.2s ease;
    padding: 0.25rem;
  }

  .reaction-button:hover {
    transform: scale(1.3);
  }

  .story-actions {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    display: flex;
    justify-content: center;
    gap: 1rem;
    background: linear-gradient(to top, rgba(0,0,0,0.5), transparent);
  }

  .action-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    background: rgba(0, 0, 0, 0.5);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-size: 1.25rem;
    transition: background 0.3s ease;
  }

  .action-button:hover {
    background: rgba(0, 0, 0, 0.7);
  }

  @media (max-width: 768px) {
    .story-content {
      padding: 1rem;
    }

    .story-text {
      bottom: 5rem;
      max-width: 90%;
    }

    .story-reactions {
      bottom: 3.5rem;
    }

    .reaction-button {
      font-size: 1.5rem;
    }

    .action-button {
      font-size: 1rem;
      padding: 0.5rem;
    }
  }
</style>
