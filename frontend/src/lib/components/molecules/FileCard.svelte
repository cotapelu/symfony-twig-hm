<script lang="ts">
  import { Card, Typography, Space } from '$lib/components';

  export let file: any = null;
   
  // eslint-disable-next-line no-unused-vars
export let onDownload: ((_f: any) => void) | null = null;
  // eslint-disable-next-line no-unused-vars
  export let onDelete: ((_f: any) => void) | null = null;

  function formatSize(bytes: number | undefined): string {
    if (!bytes) return 'Unknown size';
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
  }

  function formatDate(dateString: string | undefined): string {
    if (!dateString) return 'Unknown date';
    return new Date(dateString).toLocaleDateString();
  }

  $: icon = file?.type?.startsWith('image/') ? '📷' : file?.type?.startsWith('video/') ? '🎥' : file?.type?.startsWith('audio/') ? '🎵' : '📄';
</script>

{#if file}
  <Card hoverable>
    <Space>
      <Typography variant="h6">{icon}</Typography>
      <div class="file-info">
        <div id="file-{file.id || file.name}"><Typography variant="h6">{file.name}</Typography></div>
        <Typography variant="caption">{formatSize(file.size)}</Typography>
        <Typography variant="caption">{formatDate(file.uploaded || file.created_at)}</Typography>
      </div>
      <Space>
        <button onclick={() => onDownload?.(file)} aria-label="Download {file.name}">⬇️</button>
        {#if onDelete}
          <button onclick={() => onDelete?.(file)} aria-label="Delete {file.name}" class="delete-btn">🗑️</button>
        {/if}
      </Space>
    </Space>
  </Card>
{/if}

<style>
  .file-info {
    flex: 1;
  }

  .delete-btn {
    background: rgba(255, 0, 255, 0.1);
    border: 1px solid #ff00ff;
    color: #ff00ff;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
  }

  .delete-btn:hover {
    background: rgba(255, 0, 255, 0.2);
    transform: scale(1.05);
  }

  /* Mobile responsive */
  @media (max-width: 768px) {


    .delete-btn {
      padding: 0.25rem 0.5rem;
      font-size: 0.8rem;
    }
  }
</style>