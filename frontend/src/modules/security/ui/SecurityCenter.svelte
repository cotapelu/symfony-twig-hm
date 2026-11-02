<script lang="ts">
import { onMount } from 'svelte';
import { securityStore, loadSecurityData, enable2FA, disable2FA, revokeSession } from '../state/securityStore';
import type { Session, AuditLog } from '../contracts/securityTypes';

  let twoFactorEnabled = false;
  let sessions: Session[] = [];
  let auditLogs: AuditLog[] = [];
  let isLoading = false;
  let errorMessage: string | null = null;

  securityStore.subscribe(state => {
    twoFactorEnabled = state.twoFactorEnabled;
    sessions = state.sessions;
    auditLogs = state.auditLogs;
    isLoading = state.loading;
    errorMessage = state.error;
  });

  onMount(() => {
    loadSecurityData();
  });

  async function toggle2FA() {
    try {
      if (twoFactorEnabled) {
        if (confirm('Bạn có chắc muốn tắt xác thực 2 yếu tố?')) {
          await disable2FA();
          alert('Đã tắt xác thực 2 yếu tố');
        }
      } else {
        const code = prompt('Nhập mã xác thực từ ứng dụng:');
        if (code) {
          await enable2FA(code);
          alert('Đã bật xác thực 2 yếu tố');
        }
      }
} catch (e) {
  alert((e as Error).message);
}
  }

  async function handleRevokeSession(sessionId: string) {
    if (confirm('Bạn có chắc muốn thu hồi phiên đăng nhập này?')) {
      try {
        await revokeSession(sessionId);
        alert('Đã thu hồi phiên đăng nhập');
} catch (e) {
    alert((e as Error).message);
  }
}
  }
</script>

<div class="security">
  <h1>🔒 Trung tâm bảo mật</h1>
  
  {#if isLoading}
    <div class="loading">Đang tải...</div>
  {/if}

  {#if errorMessage}
    <div class="error">{errorMessage}</div>
  {/if}

  <div class="security-section">
    <h2>Xác thực 2 yếu tố (2FA)</h2>
    <label class="toggle">
      <input type="checkbox" bind:checked={twoFactorEnabled} on:change={toggle2FA} disabled={isLoading} />
      <span class="slider"></span>
      {twoFactorEnabled ? 'Đã bật' : 'Đã tắt'}
    </label>
  </div>

  <div class="security-section">
    <h2>Phiên đăng nhập</h2>
    {#each sessions as session}
      <div class="session-item">
        <div>
          <p><strong>{session.device}</strong></p>
          <p class="text-sm">📍 {session.location} • {session.lastActive}</p>
        </div>
        {#if !session.current}
          <button class="btn-danger" on:click={() => handleRevokeSession(session.id)} disabled={isLoading}>Thu hồi</button>
        {:else}
          <span class="current-badge">Hiện tại</span>
        {/if}
      </div>
    {/each}

    {#if sessions.length === 0}
      <p class="empty">Không có phiên đăng nhập</p>
    {/if}
  </div>

  <div class="security-section">
    <h2>Nhật ký hoạt động</h2>
    <table>
      <thead>
        <tr><th>Hành động</th><th>Thời gian</th><th>IP</th></tr>
      </thead>
      <tbody>
        {#each auditLogs as log}
          <tr>
            <td>{log.action}</td>
            <td>{log.timestamp}</td>
            <td>{log.ip}</td>
          </tr>
        {/each}
      </tbody>
    </table>

    {#if auditLogs.length === 0}
      <p class="empty">Không có nhật ký hoạt động</p>
    {/if}
  </div>
</div>

<style>
  .security { padding: 20px; max-width: 800px; margin: 0 auto; }
  .security-section { background: white; padding: 20px; margin: 16px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .toggle { display: flex; align-items: center; gap: 12px; cursor: pointer; }
  .session-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #e5e7eb; }
  .btn-danger { background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
  .btn-danger:disabled { background: #9ca3af; }
  .current-badge { background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 12px; font-size: 12px; }
  table { width: 100%; }
  th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
  .text-sm { font-size: 14px; color: #6b7280; }
  .loading, .error, .empty { padding: 12px; text-align: center; }
  .error { color: #ef4444; }
</style>