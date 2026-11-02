<script lang="ts">
  export let action = '';
  export let accept = '';
  export let multiple = false;
  export let disabled = false;
  export let listType = 'text';
  export let showUploadList = true;
  export let maxCount = 0;
  export let fileList: any[] = [];
  export let beforeUpload: ((files: File[]) => Promise<File[] | false | void>) | null = null;
  export let customRequest: ((args: any) => Promise<void>) | null = null;
  export let className = '';

  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  let dragOver = false;
  let uploading = false;

  $: displayFileList = [...fileList];

  function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    const files = Array.from(target.files || []);
    uploadFiles(files);
  }

  function handleDrop(event: DragEvent) {
    event.preventDefault();
    dragOver = false;
    const files = Array.from(event.dataTransfer?.files || []);
    uploadFiles(files);
  }

  function handleDragOver(event: DragEvent) {
    event.preventDefault();
    dragOver = true;
  }

  function handleDragLeave(event: DragEvent) {
    event.preventDefault();
    dragOver = false;
  }

  async function uploadFiles(files: File[]) {
    if (disabled || uploading) return;

    let filesToUpload = files;

    if (beforeUpload) {
      const result = await beforeUpload(files);
      if (!result) return;
      filesToUpload = result as File[];
    }

    if (maxCount > 0 && displayFileList.length + filesToUpload.length > maxCount) {
      filesToUpload = filesToUpload.slice(0, maxCount - displayFileList.length);
    }

    for (const file of filesToUpload) {
      await uploadFile(file);
    }
  }

  async function uploadFile(file: File) {
    const fileItem = {
      uid: Date.now() + Math.random(),
      name: file.name,
      size: file.size,
      type: file.type,
      status: 'uploading',
      percent: 0,
      originFileObj: file
    };

    displayFileList = [...displayFileList, fileItem];
    fileList = [...displayFileList];
    dispatch('change', { file, fileList });

    try {
      uploading = true;

      if (customRequest) {
        await customRequest({
          file,
          onProgress: (percent: number) => {
            updateFileProgress(fileItem.uid, percent);
          },
          onSuccess: (response: any) => {
            updateFileStatus(fileItem.uid, 'done', response);
          },
          onError: (error: Error) => {
            updateFileStatus(fileItem.uid, 'error', null, error);
          }
        });
      } else {
        const formData = new FormData();
        formData.append('file', file);

        const xhr = new XMLHttpRequest();

        xhr.upload.onprogress = (event) => {
          if (event.lengthComputable) {
            const percent = Math.round((event.loaded / event.total) * 100);
            updateFileProgress(fileItem.uid, percent);
          }
        };

        xhr.onload = () => {
          if (xhr.status >= 200 && xhr.status < 300) {
            updateFileStatus(fileItem.uid, 'done', JSON.parse(xhr.responseText));
          } else {
            updateFileStatus(fileItem.uid, 'error', null, new Error('Upload failed'));
          }
        };

        xhr.onerror = () => {
          updateFileStatus(fileItem.uid, 'error', null, new Error('Network error'));
        };

        xhr.open('POST', action);
        xhr.send(formData);
      }
    } catch (error) {
      updateFileStatus(fileItem.uid, 'error', null, error as Error);
    } finally {
      uploading = false;
    }
  }

  function updateFileProgress(uid: number, percent: number) {
    displayFileList = displayFileList.map(item =>
      item.uid === uid ? { ...item, percent } : item
    );
    fileList = [...displayFileList];
    dispatch('change', { fileList });
  }

  function updateFileStatus(uid: number, status: string, response: any = null, error: Error | null = null) {
    displayFileList = displayFileList.map(item =>
      item.uid === uid ? { ...item, status, response, error } : item
    );
    fileList = [...displayFileList];
    dispatch('change', { fileList });

    if (status === 'done') {
      dispatch('success', { file: displayFileList.find(item => item.uid === uid), response });
    } else if (status === 'error') {
      dispatch('error', { file: displayFileList.find(item => item.uid === uid), error });
    }
  }

  function removeFile(uid: number) {
    displayFileList = displayFileList.filter(item => item.uid !== uid);
    fileList = [...displayFileList];
    dispatch('change', { fileList });
    dispatch('remove', { file: displayFileList.find(item => item.uid === uid) });
  }

  function handleClick() {
    if (!disabled && !uploading) {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = accept;
      input.multiple = multiple;
      input.onchange = handleFileSelect;
      input.click();
    }
  }

  $: classes = [
    'upload',
    `upload-${listType}`,
    disabled ? 'upload-disabled' : '',
    dragOver ? 'upload-drag-over' : '',
    className
  ].filter(Boolean).join(' ');

  $: canUpload = !disabled && !uploading && (maxCount === 0 || displayFileList.length < maxCount);
</script>

<div class={classes}>
  {#if listType === 'picture-card'}
    <div class="upload-picture-card-wrapper">
      {#each displayFileList as file}
        <div class="upload-picture-card" class:error={file.status === 'error'}>
          {#if file.status === 'uploading'}
            <div class="upload-picture-card-uploading">
              <div class="upload-progress">
                <div class="upload-progress-bar" style="width: {file.percent}%"></div>
              </div>
            </div>
          {:else if file.status === 'done'}
            <img src={file.url || file.thumbUrl} alt={file.name} class="upload-picture-card-image" />
          {:else}
            <div class="upload-picture-card-error">
              <span>Upload failed</span>
            </div>
          {/if}
          <div class="upload-picture-card-actions">
            <button type="button" onclick={() => removeFile(file.uid)}>✕</button>
          </div>
        </div>
      {/each}

      {#if canUpload}
        <div class="upload-picture-card upload-picture-card-add" onclick={handleClick}>
          <div class="upload-picture-card-add-content">
            <span>+</span>
            <div>Upload</div>
          </div>
        </div>
      {/if}
    </div>
  {:else}
    {#if showUploadList}
      <div class="upload-list">
        {#each displayFileList as file}
          <div class="upload-list-item upload-list-item-{file.status}">
            <div class="upload-list-item-info">
              <span class="upload-list-item-name">{file.name}</span>
              {#if file.status === 'uploading'}
                <div class="upload-progress">
                  <div class="upload-progress-bar" style="width: {file.percent}%"></div>
                </div>
              {/if}
              {#if file.status === 'error'}
                <span class="upload-list-item-error">Upload failed</span>
              {/if}
            </div>
            <div class="upload-list-item-actions">
              {#if file.status === 'done'}
                <a href={file.url} target="_blank" rel="noopener noreferrer">View</a>
              {/if}
              <button type="button" onclick={() => removeFile(file.uid)}>Remove</button>
            </div>
          </div>
        {/each}
      </div>
    {/if}

    <div
      class="upload-drag-container"
      class:upload-drag-hover={dragOver}
      ondrop={handleDrop}
      ondragover={handleDragOver}
      ondragleave={handleDragLeave}
    >
      <input
        type="file"
        accept={accept}
        multiple={multiple}
        disabled={!canUpload}
        onchange={handleFileSelect}
        style="display: none"
      />

      <div class="upload-select" onclick={handleClick}>
        {#if listType === 'picture'}
          <div class="upload-picture-select">
            <span>+</span>
            <div>Upload</div>
          </div>
        {:else}
          <button type="button" class="upload-select-button" disabled={!canUpload}>
            Choose File{multiple ? 's' : ''}
          </button>
        {/if}
      </div>
    </div>
  {/if}
</div>

<style>
  .upload {
    font-family: inherit;
    font-size: 0.875rem;
  }

  .upload-drag-container {
    border: 2px dashed var(--border-primary, #d1d5db);
    border-radius: 6px;
    padding: 1rem;
    text-align: center;
    transition: all 0.2s ease;
    cursor: pointer;
  }

  .upload-drag-container:hover,
  .upload-drag-hover {
    border-color: var(--primary-500, #3b82f6);
    background: var(--primary-50, #eff6ff);
  }

  .upload-select-button {
    padding: 0.5rem 1rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .upload-select-button:hover:not(:disabled) {
    border-color: var(--primary-500, #3b82f6);
    color: var(--primary-500, #3b82f6);
  }

  .upload-select-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .upload-picture-select {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px dashed var(--border-primary, #d1d5db);
    border-radius: 4px;
    background: var(--gray-50, #f9fafb);
    color: var(--text-secondary, #6b7280);
  }

  .upload-picture-select:hover {
    border-color: var(--primary-500, #3b82f6);
    color: var(--primary-500, #3b82f6);
  }

  .upload-list {
    margin-bottom: 1rem;
  }

  .upload-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    margin-bottom: 0.5rem;
    background: var(--bg-primary, white);
  }

  .upload-list-item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .upload-list-item-name {
    font-weight: 500;
    color: var(--text-primary, #111827);
  }

  .upload-list-item-error {
    color: var(--error-500, #ef4444);
    font-size: 0.75rem;
  }

  .upload-list-item-actions {
    display: flex;
    gap: 0.5rem;
    font-size: 0.75rem;
  }

  .upload-list-item-actions a,
  .upload-list-item-actions button {
    color: var(--primary-500, #3b82f6);
    text-decoration: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
  }

  .upload-list-item-actions button:hover {
    color: var(--primary-600, #2563eb);
  }

  .upload-progress {
    width: 100%;
    height: 4px;
    background: var(--gray-200, #e5e7eb);
    border-radius: 2px;
    overflow: hidden;
  }

  .upload-progress-bar {
    height: 100%;
    background: var(--primary-500, #3b82f6);
    transition: width 0.2s ease;
  }

  .upload-picture-card-wrapper {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
  }

  .upload-picture-card {
    position: relative;
    width: 100px;
    height: 100px;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    overflow: hidden;
    background: var(--bg-primary, white);
  }

  .upload-picture-card-add {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px dashed var(--border-primary, #d1d5db);
    cursor: pointer;
    color: var(--text-secondary, #6b7280);
  }

  .upload-picture-card-add:hover {
    border-color: var(--primary-500, #3b82f6);
    color: var(--primary-500, #3b82f6);
  }

  .upload-picture-card-add-content {
    text-align: center;
    font-size: 1.5rem;
  }

  .upload-picture-card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .upload-picture-card-actions {
    position: absolute;
    top: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    padding: 0.25rem;
    border-radius: 0 0 0 4px;
  }

  .upload-picture-card-actions button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 0.75rem;
  }

  .upload-picture-card-uploading,
  .upload-picture-card-error {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: var(--gray-100, #f3f4f6);
    color: var(--text-secondary, #6b7280);
  }

  .upload-disabled {
    opacity: 0.5;
    pointer-events: none;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .upload-picture-card-wrapper {
      grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    }

    .upload-picture-card {
      width: 80px;
      height: 80px;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .upload-drag-container,
    .upload-list-item,
    .upload-picture-card {
      border-width: 2px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .upload-drag-container,
    .upload-select-button,
    .upload-progress-bar {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .upload-drag-container {
      border-color: var(--gray-600, #4b5563);
    }

    .upload-drag-container:hover,
    .upload-drag-hover {
      border-color: var(--primary-400, #60a5fa);
      background: var(--primary-900, #1e3a8a);
    }

    .upload-select-button {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
      color: var(--text-primary-dark, #f9fafb);
    }

    .upload-select-button:hover:not(:disabled) {
      border-color: var(--primary-400, #60a5fa);
      color: var(--primary-400, #60a5fa);
    }

    .upload-picture-select {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
      color: var(--text-secondary-dark, #9ca3af);
    }

    .upload-picture-select:hover {
      border-color: var(--primary-400, #60a5fa);
      color: var(--primary-400, #60a5fa);
    }

    .upload-list-item {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .upload-list-item-name {
      color: var(--text-primary-dark, #f9fafb);
    }

    .upload-picture-card {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .upload-picture-card-add {
      border-color: var(--gray-600, #4b5563);
    }

    .upload-picture-card-add:hover {
      border-color: var(--primary-400, #60a5fa);
    }
  }
</style>