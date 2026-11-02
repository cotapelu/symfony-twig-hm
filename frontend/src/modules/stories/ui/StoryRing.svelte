<script lang="ts">
  interface Story {
    viewedBy?: string[];
    currentUser?: { id: string };
    expiresAt: string;
    userName: string;
    userAvatar?: string;
  }
  export let story: Story;
  export let onClick: () => void;

  const viewed = story.viewedBy && story.currentUser && story.viewedBy.includes(story.currentUser.id);
  const expiresAt = story.expiresAt ? new Date(story.expiresAt) : new Date();
  const timeLeft = expiresAt.getTime() - Date.now();
  const hoursLeft = Math.max(0, Math.floor(timeLeft / (1000 * 60 * 60)));

  let container: HTMLDivElement;
</script>

<div 
  class="story-ring {viewed ? 'viewed' : 'new'}"
  onclick={onClick}
  role="button"
  tabindex="0"
  aria-label={`View story by ${story.userName}`}
  bind:this={container}
>
  <div class="story-avatar-container">
    <img 
      src={story.userAvatar || '/default-avatar.png'} 
      alt={story.userName}
      class="story-avatar"
    />
    {#if !viewed}
      <span class="story-badge">NEW</span>
    {/if}
  </div>
  <div class="story-info">
    <span class="story-user-name">{story.userName}</span>
    <span class="story-time">
      {hoursLeft > 0 ? `${hoursLeft}h left` : 'Expires soon'}
    </span>
  </div>
</div>

<style>
  .story-ring {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: transform 0.2s ease;
    border-radius: 0.5rem;
    padding: 0.5rem;
  }

  .story-ring:hover {
    transform: scale(1.05);
  }

  .story-ring.new .story-avatar-container {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    padding: 2px;
    border-radius: 50%;
  }

  .story-ring.viewed .story-avatar-container {
    background: var(--gray-300);
    padding: 2px;
    border-radius: 50%;
  }

  .story-avatar-container {
    position: relative;
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .story-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid white;
  }

  .story-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    background: var(--error-500);
    color: white;
    font-size: 0.625rem;
    font-weight: 700;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    border: 2px solid white;
  }

  .story-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.125rem;
    text-align: center;
  }

  .story-user-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100px;
  }

  .story-time {
    font-size: 0.625rem;
    color: var(--text-secondary);
  }
</style>
