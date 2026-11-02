<script lang="ts">
  import { socialStore } from '$lib/stores/social';

  interface MediaItem {
    id: string;
    type: 'image' | 'video';
    url: string;
  }

  interface Location {
    name: string;
  }

  interface Reaction {
    emoji: string;
    count: number;
  }

  interface Comment {
    id: string;
    userName: string;
    text: string;
    createdAt: string | number | Date;
    userAvatar?: string;
    likes: number;
  }

  interface Post {
    id: string;
    userId: string;
    userName: string;
    userAvatar?: string;
    text: string;
    media?: string[];
    location?: { lat: number; lng: number; name?: string };
    privacy: "public" | "friends" | "private";
    createdAt: Date;
    likes: number;
    isLiked: boolean;
    comments: Comment[];
    shares: number;
    reactions?: Array<{ emoji: string }>;
  }
  export let post: Post;

  let showComments = false;
  let showReactions = false;
  let reactionPickerVisible = false;
  let commentInput = '';
  let isLiking = false;

  const reactions = ['❤️', '😂', '😮', '😢', '😡', '👍', '👎', '🎉', '🔥', '❤️‍🔥'];

  function handleLike() {
    if (isLiking) return;
    isLiking = true;

    if (post.isLiked) {
      socialStore.unlikePost(post.id);
    } else {
      socialStore.likePost(post.id);
    }

    setTimeout(() => {
      isLiking = false;
    }, 500);
  }

  function handleReaction(emoji: string) {
    socialStore.reactToPost(post.id, emoji);
    reactionPickerVisible = false;
  }

  async function handleAddComment() {
    if (!commentInput.trim()) return;

    try {
      await socialStore.commentOnPost(post.id, commentInput);
      commentInput = '';
    } catch (error) {
      console.error('Failed to add comment:', error);
    }
  }

  function handleShare() {
    socialStore.sharePost(post.id);
  }

  function toggleComments() {
    showComments = !showComments;
    if (showComments) {
      socialStore.loadComments(post.id);
    }
  }

  function toggleReactions() {
    showReactions = !showReactions;
    if (showReactions) {
      socialStore.loadReactions(post.id);
    }
  }

  function formatTime(timestamp: string | number | Date) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Number(now) - Number(date);

    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
    return date.toLocaleDateString();
  }

  function formatCount(count: number) {
    if (count >= 1000000) return `${(count / 1000000).toFixed(1)}M`;
    if (count >= 1000) return `${(count / 1000).toFixed(1)}K`;
    return count.toString();
  }
</script>

<article class="post-card">
  <div class="post-header">
    <img 
      src={post.userAvatar || '/default-avatar.png'} 
      alt={post.userName}
      class="author-avatar"
    />
    <div class="post-header">
      <span class="author-name">{post.userName}</span>
      <span class="post-time">{formatTime(post.createdAt)}</span>
    </div>
    <button class="more-btn" aria-label="More options">⋯</button>
  </div>

  <div class="post-content">
    {#if post.text}
      <p class="post-text">{post.text}</p>
    {/if}

    {#if post.media && post.media.length > 0}
      <div class="post-media">
        {#each post.media as mediaUrl, index}
          <img 
            src={mediaUrl} 
            alt="Post media"
            class="media-image"
            loading="lazy"
          />
        {/each}
      </div>
    {/if}

    {#if post.location}
      <div class="post-location">
        <span class="location-icon">📍</span>
        <span class="location-text">{post.location.name}</span>
      </div>
    {/if}
  </div>

  <div class="post-stats">
    {#if post.reactions && post.reactions.length > 0}
      <div class="reaction-summary" onclick={toggleReactions} role="button" tabindex="0" onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { toggleReactions(); e.preventDefault(); } }}>
        <span class="reaction-icons">{post.reactions.slice(0, 3).map(r => r.emoji).join(' ')}</span>
        <span class="reaction-count">{formatCount(post.reactions.length)}</span>
      </div>
    {/if}

    {#if post.comments && post.comments.length > 0}
      <button class="stats-link" onclick={toggleComments}>
        {post.comments.length} {post.comments.length === 1 ? 'comment' : 'comments'}
      </button>
    {/if}

    {#if post.shares != null && post.shares > 0}
      <span class="stats-text">{post.shares} {post.shares === 1 ? 'share' : 'shares'}</span>
    {/if}
  </div>

  <div class="post-actions">
    <div class="reaction-wrapper">
      <button 
        class="action-btn like-btn {post.isLiked ? 'liked' : ''}"
        onclick={handleLike}
        onmouseenter={() => reactionPickerVisible = true}
        aria-label={post.isLiked ? 'Unlike post' : 'Like post'}
      >
        {#if post.isLiked}
          <span class="liked-heart">❤️</span>
        {:else}
          <span>👍</span>
        {/if}
      </button>

      {#if reactionPickerVisible}
        <div class="reaction-picker">
          {#each reactions as reaction}
            <button 
              class="reaction-option"
              onclick={() => handleReaction(reaction)}
              aria-label={`React with ${reaction}`}
            >
              {reaction}
            </button>
          {/each}
        </div>
      {/if}
    </div>

    <button 
      class="action-btn"
      onclick={toggleComments}
      aria-label="Comment on post"
    >
      💬 Comment
    </button>

    <button 
      class="action-btn"
      onclick={handleShare}
      aria-label="Share post"
    >
      📤 Share
    </button>

    <button 
      class="action-btn"
      aria-label="Save post"
    >
      🔖 Save
    </button>
  </div>

      {#if $socialStore.reactions[post.id]}
        <div class="reactions-list">
          <h4>Reactions</h4>
          {#each ($socialStore.reactions[post.id] || []) as reaction (reaction.id)}
            <div class="reaction-item">
              <img 
                src={reaction.userAvatar || '/default-avatar.png'} 
                alt={reaction.userName}
                class="reaction-user-avatar"
              />
              <span class="reaction-user-name">{reaction.userName}</span>
              <span class="reaction-emoji">{reaction.emoji}</span>
            </div>
          {/each}
        </div>
      {/if}

      {#if showComments}
        <div class="comments-section">
          <h4>Comments</h4>

          <div class="add-comment">
            <img 
              src="/default-avatar.png" 
              alt="Your avatar"
              class="comment-avatar"
            />
            <div class="comment-input-wrapper">
              <textarea 
                bind:value={commentInput}
                placeholder="Write a comment..."
                rows="2"
                class="comment-input"
                aria-label="Add a comment"
              ></textarea>
              <button 
                class="send-btn"
                onclick={handleAddComment}
                disabled={!commentInput.trim()}
                aria-label="Send comment"
              >
                ➤
              </button>
            </div>
          </div>

          <div class="comments-list">
            {#each ($socialStore.comments[post.id] || []) as comment (comment.id)}
              <div class="comment-item">
                <img 
                  src={comment.userAvatar || '/default-avatar.png'} 
                  alt={comment.userName}
                  class="comment-avatar"
                />
                <div class="comment-content">
                  <div class="comment-header">
                    <span class="comment-author">{comment.userName}</span>
                    <span class="comment-time">{formatTime(comment.createdAt)}</span>
                  </div>
                  <p class="comment-text">{comment.text}</p>

                  <div class="comment-actions">
                    <button 
                      class="comment-action-btn"
                      onclick={() => socialStore.likeComment(post.id, comment.id)}
                      aria-label="Like comment"
                    >
                      👍 {comment.likes > 0 ? comment.likes : ''}
                    </button>
                    <button 
                      class="comment-action-btn"
                      aria-label="Reply to comment"
                    >
                      💬 Reply
                    </button>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        </div>
      {/if}
</article>

<style>
  .post-card {
    background: var(--surface-primary);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .post-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .author-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
  }

  .author-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    flex: 1;
  }

  .author-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 1rem;
  }

  .post-time {
    font-size: 0.875rem;
    color: var(--text-secondary);
  }

  .more-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
  }

  .more-btn:hover {
    background: var(--surface-secondary);
  }

  .post-content {
    margin-bottom: 1rem;
  }

  .post-text {
    color: var(--text-primary);
    line-height: 1.6;
    margin-bottom: 1rem;
  }

  .post-media {
    display: grid;
    gap: 0.5rem;
    border-radius: 0.75rem;
    overflow: hidden;
    margin-bottom: 1rem;
  }

  .post-media:has(:nth-child(1)):not(:has(:nth-child(2))) .media-image,
  .post-media:has(:nth-child(1)):not(:has(:nth-child(2))) .media-video {
    border-radius: 0.75rem;
    width: 100%;
    height: auto;
  }

  .post-media:has(:nth-child(2)) .media-image,
  .post-media:has(:nth-child(2)) .media-video {
    width: 100%;
    height: 400px;
    object-fit: cover;
  }

  .post-media:has(:nth-child(n+3)) .media-image,
  .post-media:has(:nth-child(n+3)) .media-video {
    height: 300px;
  }

  .media-image,
  .media-video {
    width: 100%;
    height: auto;
    display: block;
  }

  .post-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: var(--surface-secondary);
    border-radius: 0.5rem;
    margin-bottom: 1rem;
  }

  .location-icon {
    font-size: 1.25rem;
  }

  .location-text {
    font-size: 0.875rem;
    color: var(--text-secondary);
  }

  .post-stats {
    display: flex;
    gap: 1.5rem;
    padding: 1rem 0;
    border-top: 1px solid var(--gray-200);
    border-bottom: 1px solid var(--gray-200);
    font-size: 0.875rem;
    color: var(--text-secondary);
  }

  .reaction-summary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    transition: background 0.2s ease;
  }

  .reaction-summary:hover {
    background: var(--surface-secondary);
  }

  .reaction-icons {
    font-size: 1rem;
  }

  .stats-link {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    transition: background 0.2s ease;
  }

  .stats-link:hover {
    background: var(--surface-secondary);
    color: var(--text-primary);
  }

  .post-actions {
    display: flex;
    gap: 0.5rem;
    padding: 1rem 0;
  }

  .reaction-wrapper {
    position: relative;
  }

  .action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: var(--surface-secondary);
    border: none;
    border-radius: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
  }

  .action-btn:hover {
    background: var(--gray-300);
    color: var(--text-primary);
  }

  .action-btn.liked {
    background: var(--error-50);
    color: var(--error-600);
  }

  .liked-heart {
    animation: like-bounce 0.3s ease;
  }

  @keyframes like-bounce {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
  }

  .reaction-picker {
    position: absolute;
    bottom: calc(100% + 0.5rem);
    left: 0;
    display: flex;
    gap: 0.25rem;
    background: white;
    padding: 0.5rem;
    border-radius: 2rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    z-index: 10;
  }

  .reaction-option {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 50%;
    transition: transform 0.2s ease;
  }

  .reaction-option:hover {
    transform: scale(1.3);
  }

  .reactions-list {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--surface-secondary);
    border-radius: 0.5rem;
  }

  .reactions-list h4 {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
  }

  .reaction-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
  }

  .reaction-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
  }

  .reaction-user-name {
    flex: 1;
    font-size: 0.875rem;
    color: var(--text-primary);
  }

  .reaction-emoji {
    font-size: 1.25rem;
  }

  .comments-section {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--surface-secondary);
    border-radius: 0.5rem;
  }

  .comments-section h4 {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
  }

  .add-comment {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .comment-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
  }

  .comment-input-wrapper {
    flex: 1;
    display: flex;
    gap: 0.5rem;
  }

  .comment-input {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.5rem;
    font-family: inherit;
    resize: none;
  }

  .comment-input:focus {
    outline: none;
    border-color: var(--primary-600);
  }

  .send-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--primary-600);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: background 0.3s ease;
  }

  .send-btn:hover:not(:disabled) {
    background: var(--primary-700);
  }

  .send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .comments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .comment-item {
    display: flex;
    gap: 0.75rem;
  }

  .comment-content {
    flex: 1;
  }

  .comment-header {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
  }

  .comment-author {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.875rem;
  }

  .comment-time {
    font-size: 0.75rem;
    color: var(--text-secondary);
  }

  .comment-text {
    margin: 0;
    color: var(--text-primary);
    font-size: 0.875rem;
    line-height: 1.5;
  }

  .comment-actions {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
  }

  .comment-action-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 0.75rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    transition: background 0.2s ease;
  }

  .comment-action-btn:hover {
    background: var(--surface-tertiary);
    color: var(--text-primary);
  }

  @media (max-width: 768px) {
    .post-card {
      padding: 1rem;
    }

    .post-actions {
      flex-wrap: wrap;
    }

    .action-btn {
      flex: 1 1 calc(50% - 0.25rem);
      min-width: calc(50% - 0.25rem);
    }
  }
</style>
