<script>
  let voicemails = [
    { id: 1, from: 'Nguyễn Văn A', duration: '2:30', date: '2024-01-15', listened: false },
    { id: 2, from: 'Trần Thị B', duration: '1:45', date: '2024-01-14', listened: true },
    { id: 3, from: 'Lê Văn C', duration: '3:20', date: '2024-01-13', listened: true }
  ];
  
  let currentPlaying = null;
  
  function playVoicemail(id) {
    currentPlaying = id;
    voicemails = voicemails.map(v => v.id === id ? {...v, listened: true} : v);
  }
  
  function deleteVoicemail(id) {
    voicemails = voicemails.filter(v => v.id !== id);
  }
</script>

<div class="voicemail">
  <h1>📮 Hộp thư thoại</h1>
  
  <div class="voicemail-list">
    {#each voicemails as vm}
      <div class="voicemail-item {vm.listened ? 'listened' : 'unread'}">
        <div class="vm-icon">{vm.listened ? '📼' : '🔴'}</div>
        <div class="vm-info">
          <h4>{vm.from}</h4>
          <p>⏱️ {vm.duration} • 📅 {vm.date}</p>
        </div>
        <div class="vm-actions">
          <button on:click={() => playVoicemail(vm.id)}>
            {currentPlaying === vm.id ? '⏸️' : '▶️'}
          </button>
          <button on:click={() => deleteVoicemail(vm.id)}>🗑️</button>
        </div>
      </div>
    {/each}
  </div>
  
  {#if voicemails.length === 0}
    <div class="empty">Không có tin nhắn thoại nào</div>
  {/if}
</div>

<style>
  .voicemail { padding: 20px; max-width: 600px; margin: 0 auto; }
  .voicemail-item { display: flex; align-items: center; gap: 16px; padding: 16px; background: white; margin: 8px 0; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .voicemail-item.unread { background: #eff6ff; border-left: 4px solid #3b82f6; }
  .vm-icon { font-size: 32px; }
  .vm-info { flex: 1; }
  .vm-actions { display: flex; gap: 8px; }
  .vm-actions button { padding: 8px 12px; border: none; background: #f3f4f6; border-radius: 6px; cursor: pointer; }
  .empty { text-align: center; padding: 40px; color: #6b7280; }
</style>
