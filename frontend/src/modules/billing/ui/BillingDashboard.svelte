<script>
  let invoices = [
    { id: 'INV-001', customer: 'ABC Corp', amount: 1500000, status: 'paid', date: '2024-01-15' },
    { id: 'INV-002', customer: 'XYZ Ltd', amount: 2800000, status: 'pending', date: '2024-01-14' },
    { id: 'INV-003', customer: '123 Inc', amount: 950000, status: 'overdue', date: '2024-01-10' }
  ];
  
  let subscriptions = [
    { id: 1, name: 'Premium Plan', price: 500000, interval: 'monthly', status: 'active' },
    { id: 2, name: 'Enterprise', price: 2000000, interval: 'monthly', status: 'active' }
  ];
  
  $: totalRevenue = invoices.reduce((sum, inv) => sum + inv.amount, 0);
  $: pendingAmount = invoices.filter(i => i.status === 'pending').reduce((sum, inv) => sum + inv.amount, 0);
</script>

<div class="billing">
  <h1>💰 Quản lý thanh toán</h1>
  
  <div class="stats">
    <div class="stat-card">
      <h3>Tổng doanh thu</h3>
      <p class="amount">{totalRevenue.toLocaleString()} VND</p>
    </div>
    <div class="stat-card">
      <h3>Chờ thanh toán</h3>
      <p class="amount pending">{pendingAmount.toLocaleString()} VND</p>
    </div>
    <div class="stat-card">
      <h3>Gói đăng ký</h3>
      <p class="amount">{subscriptions.length} gói</p>
    </div>
  </div>
  
  <div class="section">
    <h2>📄 Hóa đơn</h2>
    <table>
      <thead>
        <tr><th>Mã</th><th>Khách hàng</th><th>Số tiền</th><th>Trạng thái</th><th>Ngày</th></tr>
      </thead>
      <tbody>
        {#each invoices as inv}
          <tr>
            <td>{inv.id}</td>
            <td>{inv.customer}</td>
            <td>{inv.amount.toLocaleString()} VND</td>
            <td class={inv.status}>{inv.status}</td>
            <td>{inv.date}</td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>

<style>
  .billing { padding: 20px; }
  .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
  .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .amount { font-size: 24px; font-weight: bold; color: #10b981; }
  .pending { color: #f59e0b; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
  .paid { color: #10b981; }
  .pending { color: #f59e0b; }
  .overdue { color: #ef4444; }
</style>
