<script>
  let files = [
    { id: 1, name: 'document.pdf', size: 2500000, type: 'pdf', date: '2024-01-15' },
    { id: 2, name: 'image.jpg', size: 1500000, type: 'image', date: '2024-01-14' },
    { id: 3, name: 'data.xlsx', size: 500000, type: 'excel', date: '2024-01-13' }
  ];
  
  function formatSize(bytes) {
    if (bytes < 1000) return bytes + ' B';
    if (bytes < 1000 * 1000) return (bytes / 1000).toFixed(1) + ' KB';
    return (bytes / (1000 * 1000)).toFixed(1) + ' MB';
  }
  
  function getIcon(type) {
    const icons = { pdf: '📄', image: '🖼️', excel: '📊', doc: '📝' };
    return icons[type] || '📁';
  }
</script>

<div class="file-manager">
  <h1>📁 Chia sẻ file</h1>
  
  <div class="upload-area">
    <p>Kéo thả file vào đây hoặc</p>
    <button>Chọn file</button>
  </div>
  
  <div class="file-list">
    {#each files as file}
      <div class="file-item">
        <span class="file-icon">{getIcon(file.type)}</span>
        <div class="file-info">
          <h4>{file.name}</h4>
          <p>{formatSize(file.size)} • {file.date}</p>
        </div>
        <div class="file-actions">
          <button>⬇️</button>
          <button>🔗</button>
          <button>🗑️</button>
        </div>
      </div>
    {/each}
  </div>
</div>

<style>
  .file-manager { padding: 20px; }
  .upload-area { border: 2px dashed #d1d5db; border-radius: 12px; padding: 40px; text-align: center; margin-bottom: 24px; }
  .file-item { display: flex; align-items: center; gap: 16px; padding: 12px; background: white; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .file-icon { font-size: 32px; }
  .file-info { flex: 1; }
  .file-actions { display: flex; gap: 8px; }
  .file-actions button { padding: 8px; border: none; background: #f3f4f6; border-radius: 6px; cursor: pointer; }
</style>
