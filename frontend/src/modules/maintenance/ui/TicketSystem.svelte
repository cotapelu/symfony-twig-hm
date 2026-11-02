<script>
  let tickets = [
    { id: 'TKT-001', title: 'Lỗi đăng nhập', status: 'open', priority: 'high', created: '2024-01-15' },
    { id: 'TKT-002', title: 'Yêu cầu tính năng mới', status: 'in-progress', priority: 'medium', created: '2024-01-14' },
    { id: 'TKT-003', title: 'Cập nhật giao diện', status: 'closed', priority: 'low', created: '2024-01-13' },
    { id: 'TKT-004', title: 'Vấn đề khác', status: 'open', priority: 'critical', created: '2024-01-12' }
  ];
  
  function getStatusColor(status) {
    const colors = { open: '#ef4444', 'in-progress': '#f59e0b', closed: '#10b981' };
    return colors[status] || '#6b7280';
  }
</script>

<div class="maintenance">
  <h1>🔧 Hệ thống bảo trì</h1>
  
  <div class="stats">
    <div class="stat-box">
      <h3>{tickets.filter(t => t.status === 'open').length}</h3>
      <p>Đang mở</p>
    </div>
    <div class="stat-box">
      <h3>{tickets.filter(t => t.status === 'in-progress').length}</h3>
      <p>Đang xử lý</p>
    </div>
    <div class="stat-box">
      <h3>{tickets.filter(t => t.status === 'closed').length}</h3>
      <p>Đã đóng</p>
    </div>
  </div>
  
  <div class="tickets-list">
    {#each tickets as ticket}
      <div class="ticket-card">
        <div class="ticket-header">
          <span class="ticket-id">{ticket.id}</span>
          <span class="priority">{ticket.priority}</span>
        </div>
        <h4>{ticket.title}</h4>
        <div class="ticket-footer">
          <span class="status" style="background: {getStatusColor(ticket.status)}">{ticket.status}</span>
          <span class="date">{ticket.created}</span>
        </div>
      </div>
    {/each}
  </div>
</div>

<style>
  .maintenance { padding: 20px; }
  .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
  .stat-box { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .stat-box h3 { font-size: 32px; margin: 0; color: #3b82f6; }
  .ticket-card { background: white; padding: 16px; margin: 12px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .ticket-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
  .ticket-id { color: #6b7280; font-size: 14px; }
  .priority { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
  .ticket-footer { display: flex; justify-content: space-between; margin-top: 12px; }
  .status { color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; }
</style>
