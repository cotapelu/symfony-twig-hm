<script lang="ts">
  let campaigns = [
    { id: 1, name: 'Hiến máu tình nguyện', date: '2026-02-20', location: 'Trường THPT', status: 'upcoming', participants: 45 },
    { id: 2, name: 'Dọn dẹp môi trường', date: '2026-02-15', location: 'Khu vực 3', status: 'upcoming', participants: 120 },
    { id: 3, name: 'Tặng quà Tết', date: '2026-01-25', location: 'UBND Phường', status: 'completed', participants: 80 }
  ];

  function getStatusColor(status: string) {
    return status === 'upcoming' ? '#3b82f6' : '#10b981';
  }
</script>

<div class="campaign-page">
  <header><h1>🤝 Hoạt Động Cộng Đồng</h1></header>

  <div class="stats">
    <div class="stat"><strong>12</strong><span>Hoạt động</span></div>
    <div class="stat"><strong>245</strong><span>Người tham gia</span></div>
    <div class="stat"><strong>8</strong><span>Tháng này</span></div>
  </div>

  <h3>Hoạt động sắp tới</h3>

  <div class="campaign-list">
    {#each campaigns.filter(c => c.status === 'upcoming') as camp}
      <div class="campaign-card">
        <div class="camp-icon">🎯</div>
        <div class="camp-info">
          <div class="camp-name">{camp.name}</div>
          <div class="camp-meta">📅 {camp.date} • 📍 {camp.location}</div>
          <div class="camp-participants">👥 {camp.participants} người tham gia</div>
        </div>
        <button class="join-btn">Đăng ký</button>
      </div>
    {/each}
  </div>

  <h3>Hoạt động đã qua</h3>

  <div class="campaign-list">
    {#each campaigns.filter(c => c.status === 'completed') as camp}
      <div class="campaign-card completed">
        <div class="camp-icon">✅</div>
        <div class="camp-info">
          <div class="camp-name">{camp.name}</div>
          <div class="camp-meta">{camp.date} • {camp.location}</div>
          <div class="camp-participants">👥 {camp.participants} người tham gia</div>
        </div>
        <span class="done">Hoàn thành</span>
      </div>
    {/each}
  </div>
</div>

<style>
  .campaign-page { padding: 20px; max-width: 600px; margin: 0 auto; background: #f5f5f5; min-height: 100vh; }
  header h1 { font-size: 22px; font-weight: 700; margin: 0 0 16px 0; }
  h3 { font-size: 16px; font-weight: 600; margin: 20px 0 12px 0; }
  .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
  .stat { background: white; padding: 16px; border-radius: 12px; text-align: center; }
  .stat strong { display: block; font-size: 24px; color: #3b82f6; }
  .stat span { font-size: 12px; color: #6b7280; }
  .campaign-list { display: flex; flex-direction: column; gap: 12px; }
  .campaign-card { background: white; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .campaign-card.completed { opacity: 0.7; }
  .camp-icon { font-size: 32px; }
  .camp-info { flex: 1; }
  .camp-name { font-weight: 600; color: #1f2937; margin-bottom: 4px; }
  .camp-meta { font-size: 13px; color: #6b7280; margin-bottom: 4px; }
  .camp-participants { font-size: 12px; color: #9ca3af; }
  .join-btn { padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
  .done { padding: 6px 12px; background: #d1fae5; color: #059669; border-radius: 12px; font-size: 12px; }
</style>