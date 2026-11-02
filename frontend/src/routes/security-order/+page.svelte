<script lang="ts">
  import { goto } from '$app/navigation';

  interface SecurityIncident {
    id: string;
    type: string;
    location: string;
    date: string;
    status: string;
    description: string;
  }

  interface PatrolRecord {
    id: string;
    team: string;
    area: string;
    time: string;
    status: string;
  }

  interface EmergencyContact {
    id: string;
    service: string;
    phone: string;
    available: string;
  }

  const incidents: SecurityIncident[] = [
    { id: '1', type: 'Trộm cắp', location: 'Khu vực chợ', date: '2026-02-13', status: 'Đang điều tra', description: 'Mất xe máy' },
    { id: '2', type: 'Tai nạn giao thông', location: 'Ngã tư Láng - Nguyễn Trãi', date: '2026-02-12', status: 'Đã xử lý', description: 'Va chạm nhẹ' },
    { id: '3', type: 'Hỏa hoạn', location: 'Khu dân cư', date: '2026-02-10', status: 'Đã xử lý', description: 'Cháy nhỏ tại bếp' },
    { id: '4', type: 'Vi phạm trật tự', location: 'Đường Láng', date: '2026-02-11', status: 'Đang xử lý', description: 'Xả rác bừa bãi' }
  ];

  const patrols: PatrolRecord[] = [
    { id: '1', team: 'Đội 1', area: 'Khu A', time: '06:00 - 14:00', status: 'Đang tuần tra' },
    { id: '2', team: 'Đội 2', area: 'Khu B', time: '14:00 - 22:00', status: 'Chuẩn bị' },
    { id: '3', team: 'Đội 3', area: 'Khu C', time: '22:00 - 06:00', status: 'Nghỉ' }
  ];

  const emergencyContacts: EmergencyContact[] = [
    { id: '1', service: 'Công an', phone: '113', available: '24/7' },
    { id: '2', service: 'Cứu hỏa', phone: '114', available: '24/7' },
    { id: '3', service: 'Cấp cứu', phone: '115', available: '24/7' },
    { id: '4', service: 'Bảo vệ phường', phone: '0123 456 789', available: '24/7' }
  ];

  function navigateTo(path: string) {
    goto(path);
  }
</script>

<svelte:head>
  <title>Security & Order - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => navigateTo('/government')}>
        ← Back
      </button>
      <div>
        <h1>🛡️ Security & Order</h1>
        <p class="subtitle">An ninh trật tự - Bảo vệ cộng đồng</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="emergency-section">
      <h2>🆘 Emergency Contacts</h2>
      <div class="emergency-grid">
        {#each emergencyContacts as contact}
          <button class="emergency-card">
            <div class="emergency-icon">📞</div>
            <div class="emergency-info">
              <span class="service-name">{contact.service}</span>
              <span class="phone-number">{contact.phone}</span>
              <span class="availability">{contact.available}</span>
            </div>
          </button>
        {/each}
      </div>
    </section>

    <section class="stats-section">
      <h2>📊 Security Overview</h2>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">🚔</div>
          <div class="stat-info">
            <span class="stat-value">12</span>
            <span class="stat-label">Incidents This Month</span>
          </div>
        </div>
        <div class="stat-card success">
          <div class="stat-icon">✅</div>
          <div class="stat-info">
            <span class="stat-value">8</span>
            <span class="stat-label">Resolved</span>
          </div>
        </div>
        <div class="stat-card warning">
          <div class="stat-icon">⏳</div>
          <div class="stat-info">
            <span class="stat-value">4</span>
            <span class="stat-label">Pending</span>
          </div>
        </div>
        <div class="stat-card primary">
          <div class="stat-icon">👮</div>
          <div class="stat-info">
            <span class="stat-value">3</span>
            <span class="stat-label">Patrol Teams</span>
          </div>
        </div>
      </div>
    </section>

    <section class="patrol-section">
      <div class="section-header">
        <h2>🚨 Patrol Schedule</h2>
        <button class="btn-primary">+ Schedule Patrol</button>
      </div>
      <div class="patrol-grid">
        {#each patrols as patrol}
          <div class="patrol-card">
            <div class="patrol-header">
              <span class="team-name">{patrol.team}</span>
              <span class="patrol-status {patrol.status === 'Đang tuần tra' ? 'active' : patrol.status === 'Chuẩn bị' ? 'pending' : 'inactive'}">{patrol.status}</span>
            </div>
            <div class="patrol-details">
              <p>📍 Khu vực: {patrol.area}</p>
              <p>🕐 Ca trực: {patrol.time}</p>
            </div>
          </div>
        {/each}
      </div>
    </section>

    <section class="incidents-section">
      <div class="section-header">
        <h2>📋 Recent Incidents</h2>
        <button class="btn-primary" on:click={() => navigateTo('/complaints')}>
          + Report Incident
        </button>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Type</th>
              <th>Location</th>
              <th>Date</th>
              <th>Description</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {#each incidents as incident}
              <tr>
                <td>{incident.type}</td>
                <td>{incident.location}</td>
                <td>{incident.date}</td>
                <td>{incident.description}</td>
                <td><span class="status-badge {incident.status === 'Đã xử lý' ? 'active' : 'pending'}">{incident.status}</span></td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>

    <section class="actions-section">
      <h2>⚡ Quick Actions</h2>
      <div class="actions-grid">
        <button class="action-card emergency" on:click={() => navigateTo('/emergency')}>
          <div class="action-icon">🆘</div>
          <span>Emergency Call</span>
        </button>
        <button class="action-card" on:click={() => navigateTo('/complaints')}>
          <div class="action-icon">📝</div>
          <span>Report Incident</span>
        </button>
        <button class="action-card">
          <div class="action-icon">📍</div>
          <span>Safety Map</span>
        </button>
        <button class="action-card">
          <div class="action-icon">👮</div>
          <span>Community Watch</span>
        </button>
      </div>
    </section>
  </div>
</div>

<style>
  .page-container {
    min-height: 100vh;
    background: #f8fafc;
  }

  .page-header {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    color: white;
    padding: 2rem;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .back-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  h1 {
    margin: 0;
    font-size: 1.75rem;
  }

  .subtitle {
    margin: 0.5rem 0 0;
    opacity: 0.9;
  }

  .content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  h2 {
    margin: 0 0 1rem;
    color: #1e293b;
    font-size: 1.25rem;
  }

  .emergency-section {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    color: white;
  }

  .emergency-section h2 {
    color: white;
  }

  .emergency-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .emergency-card {
    background: rgba(255,255,255,0.2);
    border: 2px solid white;
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .emergency-card:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.02);
  }

  .emergency-icon {
    font-size: 2.5rem;
  }

  .emergency-info {
    display: flex;
    flex-direction: column;
  }

  .service-name {
    font-weight: 700;
    font-size: 1.125rem;
  }

  .phone-number {
    font-size: 1.5rem;
    font-weight: 700;
  }

  .availability {
    font-size: 0.75rem;
    opacity: 0.9;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
  }

  .stat-card {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .stat-card.primary {
    background: #fef2f2;
    border: 2px solid #ef4444;
  }

  .stat-card.success {
    background: #ecfdf5;
    border: 2px solid #10b981;
  }

  .stat-card.warning {
    background: #fffbeb;
    border: 2px solid #f59e0b;
  }

  .stat-icon {
    font-size: 2rem;
  }

  .stat-info {
    display: flex;
    flex-direction: column;
  }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-label {
    font-size: 0.875rem;
    color: #64748b;
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .btn-primary {
    background: #dc2626;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .patrol-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
  }

  .patrol-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
  }

  .patrol-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .team-name {
    font-weight: 700;
    color: #1e293b;
  }

  .patrol-status {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .patrol-status.active {
    background: #dcfce7;
    color: #166534;
  }

  .patrol-status.pending {
    background: #fef3c7;
    color: #92400e;
  }

  .patrol-status.inactive {
    background: #f1f5f9;
    color: #64748b;
  }

  .patrol-details p {
    margin: 0.25rem 0;
    font-size: 0.875rem;
    color: #64748b;
  }

  .table-container {
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
  }

  th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e293b;
  }

  .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .status-badge.active {
    background: #dcfce7;
    color: #166534;
  }

  .status-badge.pending {
    background: #fef3c7;
    color: #92400e;
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .action-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
  }

  .action-card:hover {
    background: #fef2f2;
    border-color: #ef4444;
  }

  .action-card.emergency {
    background: #fef2f2;
    border: 2px solid #ef4444;
  }

  .action-icon {
    font-size: 2rem;
  }

  @media (max-width: 768px) {
    .content {
      padding: 1rem;
    }
  }
</style>
