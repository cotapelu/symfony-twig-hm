<script lang="ts">
  let registrations = [
    { id: 'HS001', type: 'birth', name: 'Nguyễn Văn Con', date: '2026-01-15', status: 'completed', applicant: 'Nguyễn Văn Cha' },
    { id: 'HS002', type: 'death', name: 'Trần Thị Mẹ', date: '2026-02-01', status: 'processing', applicant: 'Trần Văn Con' },
    { id: 'HS003', type: 'marriage', name: 'Lê Văn A & Trần Thị B', date: '2026-02-10', status: 'pending', applicant: 'Lê Văn A' }
  ];

  function getTypeIcon(type: string) {
    return type === 'birth' ? '👶' : type === 'death' ? '🕯️' : '💑';
  }

  function getTypeName(type: string) {
    return type === 'birth' ? 'Khai sinh' : type === 'death' ? 'Khai tử' : 'Kết hôn';
  }

  function getStatusColor(status: string) {
    return status === 'completed' ? '#10b981' : status === 'processing' ? '#3b82f6' : '#f59e0b';
  }
</script>

<div class="civil-page">
  <header><h1>📋 Hộ Tịch</h1></header>

  <div class="quick-actions">
    <button class="action-card birth"><span>👶</span><span>Khai sinh</span></button>
    <button class="action-card death"><span>🕯️</span><span>Khai tử</span></button>
    <button class="action-card marriage"><span>💑</span><span>Kết hôn</span></button>
  </div>

  <h3>Lịch sử đăng ký</h3>

  <div class="registrations-list">
    {#each registrations as reg}
      <div class="reg-card">
        <div class="reg-icon">{getTypeIcon(reg.type)}</div>
        <div class="reg-info">
          <div class="reg-name">{reg.name}</div>
          <div class="reg-meta">
            <span>{getTypeName(reg.type)}</span>
            <span>•</span>
            <span>{reg.date}</span>
          </div>
          <div class="reg-applicant">Người đăng ký: {reg.applicant}</div>
        </div>
        <span class="reg-status" style="background: {getStatusColor(reg.status)}">
          {reg.status === 'completed' ? 'Hoàn thành' : reg.status === 'processing' ? 'Đang xử lý' : 'Chờ duyệt'}
        </span>
      </div>
    {/each}
  </div>
</div>

<style>
  .civil-page { padding: 20px; max-width: 600px; margin: 0 auto; background: #f5f5f5; min-height: 100vh; }
  header h1 { font-size: 22px; font-weight: 700; margin: 0 0 16px 0; }
  h3 { font-size: 16px; font-weight: 600; margin: 20px 0 12px 0; }
  .quick-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
  .action-card { background: white; border: none; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .action-card span:first-child { font-size: 32px; }
  .action-card span:last-child { font-size: 13px; font-weight: 600; color: #1f2937; }
  .action-card.birth { background: #dbeafe; }
  .action-card.death { background: #f3f4f6; }
  .action-card.marriage { background: #fce7f3; }
  .registrations-list { display: flex; flex-direction: column; gap: 12px; }
  .reg-card { background: white; border-radius: 12px; padding: 16px; display: flex; gap: 12px; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .reg-icon { font-size: 32px; }
  .reg-info { flex: 1; }
  .reg-name { font-weight: 600; color: #1f2937; margin-bottom: 4px; }
  .reg-meta { font-size: 13px; color: #6b7280; display: flex; gap: 6px; margin-bottom: 4px; }
  .reg-applicant { font-size: 12px; color: #9ca3af; }
  .reg-status { padding: 4px 10px; border-radius: 12px; color: white; font-size: 11px; font-weight: 500; }
</style>