<script lang="ts">
  import { socialStore } from '$lib/stores/social';

  let { onClose, onPostCreated } = $props<{
    onClose: () => void;
    onPostCreated?: () => void;
  }>();

  let text = $state('');
  let media: File[] = $state([]);
  let location: boolean = $state(false);
  let privacy = $state('public');
  let isPosting = $state(false);
  let error: string | null = $state(null);

  let charCount = $derived(text.length);

  const privacyOptions = [
    { value: 'public', label: 'Public' },
    { value: 'friends', label: 'Friends Only' },
    { value: 'private', label: 'Only Me' }
  ];

  function removeMedia(index: number) {
    media = media.filter((_, i) => i !== index);
  }

  function handleFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files) {
      media = [...media, ...Array.from(input.files)];
    }
  }

  async function createPost() {
    if (!text.trim() && media.length === 0) return;
    
    isPosting = true;
    error = null;

    try {
      await socialStore.createPost({
        text: text.trim(),
        media: media,
        privacy
      });
      
      text = '';
      media = [];
      location = false;
      onPostCreated?.();
      onClose();
    } catch (e) {
      error = e instanceof Error ? e.message : 'Failed to create post';
    } finally {
      isPosting = false;
    }
  }
</script>

<div class="post-creator-overlay" onclick={onClose}>
  <div class="post-creator" onclick={(e) => e.stopPropagation()}>
    <div class="creator-header">
      <h2>Create Post</h2>
      <button class="close-btn" onclick={onClose} aria-label="Close">✕</button>
    </div>

    <div class="creator-content">
      <textarea 
        bind:value={text}
        placeholder="What's on your mind?"
        rows="5"
        maxlength="280"
        class="post-textarea"
        aria-label="Post text"
      ></textarea>

      <div class="char-count {charCount > 250 ? 'warning' : ''}">
        {charCount}/280
      </div>

      {#if media.length > 0}
        <div class="media-preview">
          <h3>Attached Media ({media.length})</h3>
          <div class="media-grid">
            {#each media as item, index}
              <div class="media-item">
                {#if item.type.startsWith('image/')}
                  <img src={URL.createObjectURL(item)} alt="Preview" />
                {:else if item.type.startsWith('video/')}
                  <video src={URL.createObjectURL(item)} muted></video>
                {/if}
                <button class="remove-media-btn" onclick={() => removeMedia(index)} aria-label="Remove">
                  ✕
                </button>
              </div>
            {/each}
          </div>
        </div>
      {/if}

      <div class="post-settings">
        <label class="setting-label">
          <span>Privacy</span>
          <select bind:value={privacy} class="privacy-select">
            {#each privacyOptions as option}
              <option value={option.value}>{option.label}</option>
            {/each}
          </select>
        </label>

        <label class="setting-label">
          <span>Add Location</span>
          <input 
            type="checkbox" 
            bind:checked={location}
            class="location-checkbox"
          />
        </label>
      </div>

      {#if error}
        <div class="error-message">{error}</div>
      {/if}
    </div>

    <div class="creator-actions">
      <div class="action-buttons">
        <label class="attach-btn">
          📷
          <input type="file" accept="image/*,video/*" multiple onchange={handleFileSelect} />
        </label>
        <label class="attach-btn">
          📍
          <input type="checkbox" bind:checked={location} />
        </label>
        <button class="attach-btn">😊</button>
        <button class="attach-btn">🏷️</button>
      </div>

      <button 
        class="post-btn {isPosting ? 'posting' : ''}"
        onclick={createPost}
        disabled={isPosting || (!text.trim() && media.length === 0)}
      >
        {#if isPosting}
          <span class="spinner"></span>
          Posting...
        {:else}
          Post
        {/if}
      </button>
    </div>
  </div>
</div>

<style>
  .post-creator-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .post-creator {
    background: var(--surface-primary);
    border-radius: 1rem;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  }

  .creator-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .creator-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
  }

  .close-btn {
    background: var(--gray-200);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
  }

  .close-btn:hover {
    background: var(--gray-300);
  }

  .creator-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .post-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.5rem;
    font-family: inherit;
    font-size: 1rem;
    resize: none;
  }

  .post-textarea:focus {
    outline: none;
    border-color: var(--primary-600);
  }

  .char-count {
    text-align: right;
    font-size: 0.875rem;
    color: var(--text-secondary);
  }

  .char-count.warning {
    color: var(--warning-500);
  }

  .media-preview {
    padding: 1rem;
    background: var(--surface-secondary);
    border-radius: 0.5rem;
  }

  .media-preview h3 {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
  }

  .media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
  }

  .media-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .media-item img,
  .media-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .remove-media-btn {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .post-settings {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .setting-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-primary);
  }

  .privacy-select {
    padding: 0.375rem 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-family: inherit;
    cursor: pointer;
  }

  .location-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .error-message {
    background: var(--error-50);
    color: var(--error-600);
    padding: 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    border-left: 4px solid var(--error-500);
  }

  .creator-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--gray-200);
    gap: 1rem;
  }

  .action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .attach-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--surface-secondary);
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  .attach-btn input[type="file"],
  .attach-btn input[type="checkbox"] {
    display: none;
  }

  .attach-btn:hover {
    background: var(--gray-300);
  }

  .post-btn {
    padding: 0.75rem 2rem;
    border-radius: 2rem;
    background: var(--primary-600);
    color: white;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .post-btn:hover:not(:disabled) {
    background: var(--primary-700);
    transform: translateY(-1px);
  }

  .post-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  @media (max-width: 768px) {
    .post-creator {
      height: 100%;
      max-height: 100%;
      border-radius: 0;
    }

    .creator-actions {
      flex-direction: column;
    }

    .action-buttons,
    .post-btn {
      width: 100%;
    }

    .post-btn {
      padding: 1rem;
    }
  }
</style>
