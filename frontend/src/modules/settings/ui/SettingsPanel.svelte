<script>
  let activeTab = 'general';
  let settings = {
    language: 'vi',
    theme: 'light',
    notifications: true,
    emailUpdates: true
  };
  
  const tabs = [
    { id: 'general', label: 'Chung', icon: '⚙️' },
    { id: 'account', label: 'Tài khoản', icon: '👤' },
    { id: 'privacy', label: 'Riêng tư', icon: '🔒' },
    { id: 'notifications', label: 'Thông báo', icon: '🔔' }
  ];
</script>

<div class="settings">
  <h1>⚙️ Cài đặt</h1>
  
  <div class="settings-layout">
    <div class="sidebar">
      {#each tabs as tab}
        <button 
          class="tab-btn {activeTab === tab.id ? 'active' : ''}"
          on:click={() => activeTab = tab.id}
        >
          <span>{tab.icon}</span>
          {tab.label}
        </button>
      {/each}
    </div>
    
    <div class="content">
      {#if activeTab === 'general'}
        <h2>Cài đặt chung</h2>
        <div class="setting-item">
          <label>Ngôn ngữ</label>
          <select bind:value={settings.language}>
            <option value="vi">Tiếng Việt</option>
            <option value="en">English</option>
          </select>
        </div>
        <div class="setting-item">
          <label>Giao diện</label>
          <select bind:value={settings.theme}>
            <option value="light">Sáng</option>
            <option value="dark">Tối</option>
          </select>
        </div>
      {:else if activeTab === 'notifications'}
        <h2>Thông báo</h2>
        <div class="setting-item">
          <label>
            <input type="checkbox" bind:checked={settings.notifications} />
            Bật thông báo
          </label>
        </div>
        <div class="setting-item">
          <label>
            <input type="checkbox" bind:checked={settings.emailUpdates} />
            Nhận cập nhật qua email
          </label>
        </div>
      {:else}
        <h2>Đang phát triển</h2>
        <p>Tính năng này sẽ sớm ra mắt!</p>
      {/if}
    </div>
  </div>
</div>

<style>
  .settings { padding: 20px; }
  .settings-layout { display: grid; grid-template-columns: 250px 1fr; gap: 24px; margin-top: 24px; }
  .sidebar { display: flex; flex-direction: column; gap: 8px; }
  .tab-btn { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border: none; background: transparent; border-radius: 8px; cursor: pointer; text-align: left; }
  .tab-btn.active { background: #eff6ff; color: #3b82f6; }
  .tab-btn span { font-size: 20px; }
  .content { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .setting-item { margin: 16px 0; }
  .setting-item label { display: block; margin-bottom: 8px; font-weight: 500; }
  select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; width: 100%; }
</style>
