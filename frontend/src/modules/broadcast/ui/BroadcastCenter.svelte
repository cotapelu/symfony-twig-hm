<script>
  let campaigns = [
    { id: 1, name: 'Khuyến mãi Tết', type: 'email', recipients: 5000, sent: 4500, status: 'completed' },
    { id: 2, name: 'Thông báo bảo trì', type: 'push', recipients: 10000, sent: 9800, status: 'sending' }
  ];
  
  let scheduled = [
    { id: 3, name: 'Newsletter tháng 2', type: 'email', scheduledFor: '2024-02-01 09:00' }
  ];
</script>

<div class="broadcast">
  <h1>📢 Trung tâm phát sóng</h1>
  
  <div class="campaigns">
    <h2>Chiến dịch</h2>
    {#each campaigns as campaign}
      <div class="campaign-card">
        <div class="campaign-header">
          <h3>{campaign.name}</h3>
          <span class="type-badge">{campaign.type}</span>
        </div>
        <div class="progress-bar">
          <div class="progress" style="width: {(campaign.sent/campaign.recipients)*100}%"></div>
        </div>
        <p>{campaign.sent.toLocaleString()}/{campaign.recipients.toLocaleString()} đã gửi</p>
        <span class="status {campaign.status}">{campaign.status}</span>
      </div>
    {/each}
  </div>
  
  <div class="scheduled">
    <h2>⏰ Lên lịch</h2>
    {#each scheduled as item}
      <div class="scheduled-item">
        <h4>{item.name}</h4>
        <p>📅 {item.scheduledFor}</p>
      </div>
    {/each}
  </div>
</div>

<style>
  .broadcast { padding: 20px; }
  .campaign-card { background: white; padding: 16px; margin: 12px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .campaign-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
  .type-badge { background: #8b5cf6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
  .progress-bar { background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden; }
  .progress { background: #3b82f6; height: 100%; transition: width 0.3s; }
  .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
  .status.completed { background: #d1fae5; color: #065f46; }
  .status.sending { background: #dbeafe; color: #1e40af; }
  .scheduled-item { background: #fef3c7; padding: 12px; margin: 8px 0; border-radius: 6px; }
</style>
