<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { storiesStore } from '$lib/stores/stories';
  import StoryRing from './StoryRing.svelte';
  import StoryViewer from './StoryViewer.svelte';
  import StoryCreator from './StoryCreator.svelte';
  import LoadSpinner from '$lib/components/Spin.svelte';

  interface Story {
    id: string;
    name: string;
    avatar?: string;
    viewedAt?: string;
  }

  onMount(async () => {
    await storiesStore.loadStories();
    await storiesStore.loadUserStories();
    await storiesStore.loadPublicStories();
  });

  let viewingStory = false;
  let creatingStory = false;
  let currentStoryIndex = 0;

  function handleViewStory(story: any, index: number) {
    currentStoryIndex = index;
    viewingStory = true;
    storiesStore.viewStory(story.id);
  }

  function handleCloseViewer() {
    viewingStory = false;
  }

  function handleCreateStory() {
    creatingStory = true;
  }

  async function handleStoryCreated(_story: unknown) {
    await storiesStore.loadStories();
    await storiesStore.loadUserStories();
    creatingStory = false;
  }
</script>

<div class="stories-page">
  <div class="stories-header">
    <h1>Stories</h1>
    <button 
      class="btn-primary create-story-btn"
      onclick={handleCreateStory}
      aria-label="Create new story"
    >
      <span class="icon">➕</span>
      Create Story
    </button>
  </div>

  {#if $storiesStore.loading}
    <div class="loading-container">
      <LoadSpinner />
      <p>Loading stories...</p>
    </div>
  {:else if $storiesStore.error}
    <div class="error-container">
      <p class="error">{$storiesStore.error}</p>
      <button 
        class="btn-secondary"
        onclick={() => storiesStore.loadStories()}
      >
        Retry
      </button>
    </div>
  {:else}
    <div class="stories-content">
      <section class="my-stories-section">
        <h2>My Stories</h2>
        {#if $storiesStore.userStories.length > 0}
          <div class="stories-grid">
            {#each $storiesStore.userStories as story, index}
              <StoryRing 
                story={story as any}
                onClick={() => handleViewStory(story as any, index)}
              />
            {/each}
          </div>
        {:else}
          <div class="empty-state">
            <p>You don't have any stories yet.</p>
            <button 
              class="btn-primary"
              onclick={handleCreateStory}
            >
              Create Your First Story
            </button>
          </div>
        {/if}
      </section>

      <section class="public-stories-section">
        <h2>Discover Stories</h2>
        {#if $storiesStore.publicStories.length > 0}
          <div class="stories-grid">
            {#each $storiesStore.publicStories as story, index}
              <StoryRing 
                story={story as any}
                onClick={() => handleViewStory(story as any, index)}
              />
            {/each}
          </div>
        {:else}
          <div class="empty-state">
            <p>No public stories available.</p>
          </div>
        {/if}
      </section>

      <section class="story-viewers-section">
        <h2>Story Viewers</h2>
        {#if $storiesStore.storyViewers.length > 0}
          <div class="viewers-list">
            {#each $storiesStore.storyViewers as viewer}
              <div class="viewer-item">
                <img 
                  src="/default-avatar.png" 
                  alt="Viewer"
                  class="viewer-avatar"
                />
                <div class="viewer-info">
                  <span class="viewer-name">User</span>
                  <span class="viewer-time">
                    {new Date().toLocaleString()}
                  </span>
                </div>
              </div>
            {/each}
          </div>
        {:else}
          <div class="empty-state">
            <p>No views on your stories yet.</p>
          </div>
        {/if}
      </section>
    </div>
  {/if}

  {#if viewingStory}
    <StoryViewer 
      stories={$storiesStore.stories as any}
      currentIndex={currentStoryIndex}
      onClose={handleCloseViewer}
      onNextReaction={(reaction) => storiesStore.addReaction({ ...reaction, itemId: reaction.itemId || '' })}
    />
  {/if}

  {#if creatingStory}
    <StoryCreator 
      onClose={() => creatingStory = false}
      onStoryCreated={handleStoryCreated}
    />
  {/if}
</div>

<style>
  .stories-page {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
  }

  .stories-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }

  .stories-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
  }

  .create-story-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .create-story-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .create-story-btn .icon {
    font-size: 1.25rem;
  }

  .stories-content {
    display: flex;
    flex-direction: column;
    gap: 3rem;
  }

  .my-stories-section,
  .public-stories-section,
  .story-viewers-section {
    background: var(--surface-primary);
    border-radius: 1rem;
    padding: 1.5rem;
  }

  .stories-content h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
  }

  .stories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
  }

  .loading-container,
  .error-container,
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    gap: 1rem;
  }

  .error {
    color: var(--error-500);
    font-weight: 500;
  }

  .btn-primary,
  .btn-secondary {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .btn-primary {
    background: var(--primary-600);
    color: white;
  }

  .btn-primary:hover {
    background: var(--primary-700);
  }

  .btn-secondary {
    background: var(--gray-200);
    color: var(--text-primary);
  }

  .btn-secondary:hover {
    background: var(--gray-300);
  }

  .viewers-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .viewer-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--surface-secondary);
    border-radius: 0.5rem;
  }

  .viewer-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
  }

  .viewer-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .viewer-name {
    font-weight: 600;
    color: var(--text-primary);
  }

  .viewer-time {
    font-size: 0.875rem;
    color: var(--text-secondary);
  }

  @media (max-width: 768px) {
    .stories-page {
      padding: 1rem;
    }

    .stories-header h1 {
      font-size: 1.5rem;
    }

    .stories-grid {
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }

    .stories-content section {
      padding: 1rem;
    }
  }
</style>
