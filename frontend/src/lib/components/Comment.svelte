<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import DOMPurify from 'dompurify';

  const dispatch = createEventDispatcher();

  export let comments: CommentItem[] = [];
  export let maxDepth: number =3;
  export let showActions: boolean = true;
  export let currentUserId: string | number = '';
  export let className: string = '';
  export let allowHtml: boolean = false;

  interface CommentItem {
    id: string | number;
    author: string;
    avatar?: string;
    content: string;
    timestamp: string | Date;
    likes?: number;
    replies?: CommentItem[];
    parentId?: string | number;
    depth?: number;
  }

  let newComment = '';
  let replyingTo: string | number | null = null;
  let editingComment: string | number | null = null;
  let editContent = '';

  $: classes = [
    'comment-system',
    className
  ].filter(Boolean).join(' ');

  function formatTimestamp(timestamp: string | Date): string {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
  }

  function handleReply(commentId: string | number) {
    replyingTo = commentId;
    newComment = '';
  }

  function handleEdit(commentId: string | number, content: string) {
    editingComment = commentId;
    editContent = content;
  }

  function cancelReply() {
    replyingTo = null;
    newComment = '';
  }

  function cancelEdit() {
    editingComment = null;
    editContent = '';
  }

  function submitComment(parentId?: string | number) {
    if (!newComment.trim()) return;

    const commentData = {
      content: newComment.trim(),
      parentId,
      timestamp: new Date()
    };

    dispatch('submit', commentData);
    newComment = '';
    replyingTo = null;
  }

  function submitEdit(commentId: string | number) {
    if (!editContent.trim()) return;

    dispatch('edit', { id: commentId, content: editContent.trim() });
    editingComment = null;
    editContent = '';
  }

  function handleLike(commentId: string | number) {
    dispatch('like', { id: commentId });
  }

  function handleDelete(commentId: string | number) {
    if (confirm('Are you sure you want to delete this comment?')) {
      dispatch('delete', { id: commentId });
    }
  }

  function renderComment(comment: CommentItem, depth = 0): any {
    const isOwner = currentUserId && comment.author === currentUserId;
    const canReply = depth < maxDepth;

    return `
      <div class="comment" data-depth="${depth}">
        <div class="comment-header">
          ${comment.avatar ? `<img class="comment-avatar" src="${comment.avatar}" alt="${comment.author}" />` : '<div class="comment-avatar-placeholder"></div>'}
          <div class="comment-meta">
            <span class="comment-author">${comment.author}</span>
            <span class="comment-timestamp">${formatTimestamp(comment.timestamp)}</span>
          </div>
        </div>
        <div class="comment-content">
          ${editingComment === comment.id ?
            `<div class="comment-edit">
              <textarea class="comment-edit-input" bind:value={editContent} placeholder="Edit comment..."></textarea>
              <div class="comment-edit-actions">
                <button class="comment-btn comment-btn-primary" onclick={() => submitEdit(comment.id)}>Save</button>
                <button class="comment-btn comment-btn-secondary" onclick={cancelEdit}>Cancel</button>
              </div>
            </div>` :
            `<p>${comment.content}</p>`
          }
        </div>
        ${showActions && editingComment !== comment.id ? `
          <div class="comment-actions">
            ${comment.likes !== undefined ? `<button class="comment-action" onclick={() => handleLike(comment.id)}>
              <span class="comment-action-icon">👍</span>
              ${comment.likes || 0}
            </button>` : ''}
            ${canReply ? `<button class="comment-action" onclick={() => handleReply(comment.id)}>
              <span class="comment-action-icon">💬</span> Reply
            </button>` : ''}
            ${isOwner ? `
              <button class="comment-action" onclick={() => handleEdit(comment.id, comment.content)}>
                <span class="comment-action-icon">✏️</span> Edit
              </button>
              <button class="comment-action comment-action-delete" onclick={() => handleDelete(comment.id)}>
                <span class="comment-action-icon">🗑️</span> Delete
              </button>
            ` : ''}
          </div>
        ` : ''}
        ${comment.replies && comment.replies.length > 0 ? `
          <div class="comment-replies">
            ${comment.replies.map(reply => renderComment(reply, depth + 1)).join('')}
          </div>
        ` : ''}
      </div>
    `;
  }
</script>

<div class={classes}>
  <div class="comment-input">
    <textarea
      class="comment-input-field"
      bind:value={newComment}
      placeholder="Write a comment..."
      rows="3"
    ></textarea>
    <div class="comment-input-actions">
      <button
        class="comment-btn comment-btn-primary"
        disabled={!newComment.trim()}
        onclick={() => submitComment()}
      >
        Post Comment
      </button>
    </div>
  </div>

  <div class="comments-list">
    {#each comments.filter(c => !c.parentId) as comment (comment.id)}
      {#if allowHtml === true}
        {@html renderComment(comment)}
      {:else}
        {@html DOMPurify.sanitize(renderComment(comment))}
      {/if}
    {/each}
  </div>

  {#if replyingTo}
    <div class="comment-reply-input">
      <div class="comment-reply-header">
        <span>Replying to comment</span>
        <button class="comment-reply-cancel" onclick={cancelReply}>×</button>
      </div>
      <textarea
        class="comment-input-field"
        bind:value={newComment}
        placeholder="Write a reply..."
        rows="2"
      ></textarea>
      <div class="comment-input-actions">
        <button
          class="comment-btn comment-btn-primary"
          disabled={!newComment.trim()}
          onclick={() => replyingTo && submitComment(replyingTo)}
        >
          Post Reply
        </button>
        <button class="comment-btn comment-btn-secondary" onclick={cancelReply}>
          Cancel
        </button>
      </div>
    </div>
  {/if}
</div>

<style>
  .comment-system {
    width: 100%;
    max-width: 600px;
  }

  .comment-input, .comment-reply-input {
    margin-bottom: 1.5rem;
    padding: 1rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    background: var(--background, #ffffff);
  }

  .comment-input-field {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.25rem;
    font-family: inherit;
    font-size: 0.875rem;
    line-height: 1.5;
    resize: vertical;
    background: var(--background, #ffffff);
    color: var(--text-primary, #111827);
  }

  .comment-input-field:focus {
    outline: none;
    border-color: var(--focus-ring, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .comment-input-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 0.75rem;
  }

  .comment-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .comment-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .comment-btn-primary {
    background: var(--primary-500, #3b82f6);
    color: white;
  }

  .comment-btn-primary:hover:not(:disabled) {
    background: var(--primary-600, #2563eb);
  }

  .comment-btn-secondary {
    background: var(--gray-200, #e5e7eb);
    color: var(--text-primary, #111827);
  }

  .comment-btn-secondary:hover {
    background: var(--gray-300, #d1d5db);
  }

  .comment {
    margin-bottom: 1rem;
    padding: 1rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    background: var(--background, #ffffff);
  }

  .comment[data-depth="1"] {
    margin-left: 2rem;
    border-left: 3px solid var(--primary-500, #3b82f6);
  }

  .comment[data-depth="2"] {
    margin-left: 4rem;
    border-left: 3px solid var(--primary-600, #2563eb);
  }

  .comment-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
  }

  .comment-avatar, .comment-avatar-placeholder {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    flex-shrink: 0;
  }

  .comment-avatar {
    object-fit: cover;
  }

  .comment-avatar-placeholder {
    background: var(--gray-300, #d1d5db);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-600, #6b7280);
    font-weight: 500;
  }

  .comment-meta {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
  }

  .comment-author {
    font-weight: 500;
    color: var(--text-primary, #111827);
  }

  .comment-timestamp {
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
  }

  .comment-content p {
    margin: 0;
    line-height: 1.5;
    color: var(--text-primary, #111827);
  }

  .comment-edit {
    margin-top: 0.5rem;
  }

  .comment-edit-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.25rem;
    font-family: inherit;
    font-size: 0.875rem;
    resize: vertical;
  }

  .comment-edit-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
  }

  .comment-actions {
    display: flex;
    gap: 1rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color, #d1d5db);
  }

  .comment-action {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background: none;
    border: none;
    color: var(--text-secondary, #6b7280);
    font-size: 0.875rem;
    cursor: pointer;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
  }

  .comment-action:hover {
    background: var(--gray-100, #f3f4f6);
    color: var(--text-primary, #111827);
  }

  .comment-action-delete:hover {
    background: var(--error-50, #fef2f2);
    color: var(--error-600, #dc2626);
  }

  .comment-action-icon {
    font-size: 0.75rem;
  }

  .comment-replies {
    margin-top: 1rem;
  }

  .comment-reply-input {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    background: var(--background, #ffffff);
  }

  .comment-reply-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
  }

  .comment-reply-cancel {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    color: var(--text-secondary, #6b7280);
  }

  .comment-reply-cancel:hover {
    color: var(--text-primary, #111827);
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .comment-input, .comment-reply-input, .comment {
      background: var(--background-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
    }

    .comment-input-field {
      background: var(--background-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
      color: var(--text-primary-dark, #f9fafb);
    }

    .comment-btn-secondary {
      background: var(--gray-700-dark, #374151);
      color: var(--text-primary-dark, #f9fafb);
    }

    .comment-btn-secondary:hover {
      background: var(--gray-600-dark, #4b5563);
    }

    .comment-author, .comment-content p {
      color: var(--text-primary-dark, #f9fafb);
    }

    .comment-timestamp {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .comment-action {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .comment-action:hover {
      background: var(--gray-700-dark, #374151);
      color: var(--text-primary-dark, #f9fafb);
    }

    .comment-avatar-placeholder {
      background: var(--gray-600-dark, #4b5563);
      color: var(--gray-400-dark, #9ca3af);
    }
  }

  /* Accessibility */
  .comment-input-field:focus,
  .comment-edit-input:focus,
  .comment-reply-cancel:focus,
  .comment-btn:focus,
  .comment-action:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .comment-btn, .comment-action {
      transition: none;
    }
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .comment[data-depth="1"] {
      margin-left: 1rem;
    }

    .comment[data-depth="2"] {
      margin-left: 2rem;
    }

    .comment-actions {
      flex-wrap: wrap;
    }

    .comment-action {
      padding: 0.375rem 0.5rem;
      font-size: 0.8125rem;
    }
  }
</style>