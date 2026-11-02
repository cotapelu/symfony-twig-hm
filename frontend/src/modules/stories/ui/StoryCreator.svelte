<script lang="ts">
  import { storiesStore } from '$lib/stores/stories';

  export let onClose: () => void;
  export let onStoryCreated: (story: any) => void = () => {};

  let files: File[] = [];
  let uploading = false;
  let uploadingProgress = 0;
  let error: string | null = null;
  let dropZone: HTMLDivElement;
  let isDragging = false;
  let storyText = '';

  function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    const selectedFiles = Array.from(target.files || []);
    files = [...files, ...selectedFiles];
  }

  function handleDrop(event: DragEvent) {
    event.preventDefault();
    isDragging = false;
    const droppedFiles = Array.from(event.dataTransfer?.files || []);
    files = [...files, ...droppedFiles];
  }

  function handleDragOver(event: DragEvent) {
    event.preventDefault();
    isDragging = true;
  }

  function handleDragLeave(event: DragEvent) {
    event.preventDefault();
    isDragging = false;
  }

  function removeFile(index: number) {
    files = files.filter((_, i) => i !== index);
  }

  async function createStory() {
    if (files.length === 0) {
      error = 'Please select at least one file';
      return;
    }

    uploading = true;
    uploadingProgress = 0;
    error = null;

    try {
      const result = await storiesStore.createStory({
        files,
        text: storyText
      });
      files = [];
      storyText = '';
      onStoryCreated(result);
    } catch (err) {
      error = (err instanceof Error ? err.message : 'Failed to create story');
    } finally {
      uploading = false;
      uploadingProgress = 0;
    }
  }
</script>

<div class="story-creator">
  <div class="creator-header">
    <h2>Create New Story</h2>
    <button 
      class="close-btn"
      onclick={onClose}
      aria-label="Close"
    >
      ✕
    </button>
  </div>

  <div 
    class="drop-zone {isDragging ? 'dragging' : ''}"
    bind:this={dropZone}
    ondrop={handleDrop}
    ondragover={handleDragOver}
    ondragleave={handleDragLeave}
  >
    <div class="drop-zone-content">
      <span class="drop-icon">📷</span>
      <p>Drag and drop files here</p>
      <p class="drop-hint">or click to browse</p>
      <input 
        type="file" 
        accept="image/*,video/*"
        multiple
        onchange={handleFileSelect}
        class="file-input"
        aria-label="Select files"
      />
    </div>
  </div>

  {#if files.length > 0}
    <div class="files-preview">
      <h3>Selected Files ({files.length})</h3>
      <div class="files-grid">
        {#each files as file, index}
          <div class="file-preview-card">
            {#if file.type.startsWith('image/')}
              <img 
                src={URL.createObjectURL(file)} 
                alt={file.name}
                class="file-preview"
              />
            {:else if file.type.startsWith('video/')}
              <video 
                src={URL.createObjectURL(file)} 
                class="file-preview"
                muted
              ></video>
            {/if}
            <button 
              class="remove-file-btn"
              onclick={() => removeFile(index)}
              aria-label="Remove file"
            >
              ✕
            </button>
            <span class="file-name">{file.name}</span>
          </div>
        {/each}
      </div>
    </div>
  {/if}

  <div class="story-text-section">
    <label for="story-text">Add caption (optional)</label>
    <textarea 
      id="story-text"
      bind:value={storyText}
      placeholder="Write a caption for your story..."
      rows="3"
      maxlength="2000"
    ></textarea>
    <div class="char-count">{storyText.length}/2000</div>
  </div>

  {#if error}
    <div class="error-message">
      {error}
    </div>
  {/if}

  <div class="creator-actions">
    <button 
      class="btn-secondary"
      onclick={onClose}
      disabled={uploading}
    >
      Cancel
    </button>
    <button 
      class="btn-primary create-story-btn"
      onclick={createStory}
      disabled={uploading || files.length === 0}
    >
      {#if uploading}
        <span class="uploading-spinner"></span>
        Uploading... {uploadingProgress}%
      {:else}
        Share Story
      {/if}
    </button>
  </div>
</div>

<style>
  .story-creator {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--surface-primary);
    border-radius: 1rem;
    padding: 2rem;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    z-index: 1001;
  }

  .creator-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }

  .creator-header h2 {
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

  .drop-zone {
    border: 2px dashed var(--gray-300);
    border-radius: 0.75rem;
    padding: 3rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
  }

  .drop-zone.dragging {
    border-color: var(--primary-600);
    background: var(--primary-50);
  }

  .drop-zone:hover {
    border-color: var(--primary-500);
  }

  .drop-zone-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
  }

  .drop-icon {
    font-size: 3rem;
    margin-bottom: 0.5rem;
  }

  .drop-hint {
    color: var(--text-secondary);
    font-size: 0.875rem;
  }

  .file-input {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0;
    cursor: pointer;
  }

  .files-preview {
    margin: 2rem 0;
  }

  .files-preview h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
  }

  .files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
  }

  .file-preview-card {
    position: relative;
    aspect-ratio: 1;
    border-radius: 0.5rem;
    overflow: hidden;
    background: var(--surface-secondary);
  }

  .file-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .remove-file-btn {
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

  .file-name {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 0.625rem;
    padding: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .story-text-section {
    margin-bottom: 1.5rem;
  }

  .story-text-section label {
    display: block;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
  }

  .story-text-section textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.5rem;
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.3s ease;
  }

  .story-text-section textarea:focus {
    outline: none;
    border-color: var(--primary-600);
  }

  .char-count {
    text-align: right;
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
  }

  .error-message {
    background: var(--error-50);
    color: var(--error-600);
    padding: 0.75rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid var(--error-500);
  }

  .creator-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
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

  .btn-primary:hover:not(:disabled) {
    background: var(--primary-700);
  }

  .btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .btn-secondary {
    background: var(--gray-200);
    color: var(--text-primary);
  }

  .btn-secondary:hover:not(:disabled) {
    background: var(--gray-300);
  }

  .create-story-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .uploading-spinner {
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
    .story-creator {
      padding: 1.5rem;
      width: 95%;
    }

    .drop-zone {
      padding: 2rem;
    }

    .files-grid {
      grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    }
  }
</style>
