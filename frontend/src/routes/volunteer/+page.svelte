<script lang="ts">
  import { goto } from '$app/navigation';

  interface VolunteerOpportunity {
    id: string;
    title: string;
    organization: string;
    location: string;
    date: string;
    volunteersNeeded: number;
    volunteersJoined: number;
    category: string;
  }

  interface VolunteerRecord {
    id: string;
    citizen: string;
    opportunity: string;
    hours: number;
    date: string;
    status: string;
  }

  const opportunities: VolunteerOpportunity[] = [
    { id: '1', title: 'Dọn dẹp môi trường', organization: 'Đoàn Thanh niên', location: 'Khu vực phường', date: '2026-02-20', volunteersNeeded: 50, volunteersJoined: 32, category: 'Môi trường' },
    { id: '2', title: 'Thăm hỏi người cao tuổi', organization: 'Hội Người cao tuổi', location: 'Nhà dân', date: '2026-02-18', volunteersNeeded: 20, volunteersJoined: 15, category: 'Xã hội' },
    { id: '3', title: 'Dạy kèm trẻ em', organization: 'Hội Phụ nữ', location: 'Trường Tiểu học', date: '2026-02-22', volunteersNeeded: 15, volunteersJoined: 10, category: 'Giáo dục' },
    { id: '4', title: 'Hiến máu tình nguyện', organization: 'Hội Chữ thập đỏ', location: 'Trạm y tế', date: '2026-03-01', volunteersNeeded: 100, volunteersJoined: 45, category: 'Y tế' }
  ];

  const records: VolunteerRecord[] = [
    { id: '1', citizen: 'Nguyễn Văn A', opportunity: 'Dọn dẹp môi trường', hours: 4, date: '2026-02-10', status: 'Hoàn thành' },
    { id: '2', citizen: 'Trần Thị B', opportunity: 'Thăm hỏi người cao tuổi', hours: 3, date: '2026-02-08', status: 'Hoàn thành' },
    { id: '3', citizen: 'Lê Văn C', opportunity: 'Dạy kèm trẻ em', hours: 6, date: '2026-02-05', status: 'Hoàn thành' },
    { id: '4', citizen: 'Phạm Thị D', opportunity: 'Hiến máu tình nguyện', hours: 2, date: '2026-02-01', status: 'Hoàn thành' }
  ];

  const stats = {
    totalVolunteers: 156,
    activeOpportunities: 8,
    totalHours: 2450,
    certificatesIssued: 89
  };

  function navigateTo(path: string) {
    goto(path);
  }
</script>

<svelte:head>
  <title>Volunteer & Social - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => navigateTo('/government')}>
        ← Back
      </button>
      <div>
        <h1>🤝 Volunteer & Social</h1>
        <p class="subtitle">Tình nguyện và hoạt động xã hội</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="stats-section">
      <h2>📊 Volunteer Overview</h2>
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <span class="stat-value">{stats.totalVolunteers}</span>
            <span class="stat-label">Total Volunteers</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📋</div>
          <div class="stat-info">
            <span class="stat-value">{stats.activeOpportunities}</span>
            <span class="stat-label">Active Programs</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⏱️</div>
          <div class="stat-info">
            <span class="stat-value">{stats.totalHours.toLocaleString()}</span>
            <span class="stat-label">Hours Contributed</span>
          </div>
        </div>
        <div class="stat-card highlight">
          <div class="stat-icon">🎖️</div>
          <div class="stat-info">
            <span class="stat-value">{stats.certificatesIssued}</span>
            <span class="stat-label">Certificates</span>
          </div>
        </div>
      </div>
    </section>

    <section class="opportunities-section">
      <div class="section-header">
        <h2>🎯 Volunteer Opportunities</h2>
        <button class="btn-primary">+ Create Program</button>
      </div>
      <div class="opportunities-grid">
        {#each opportunities as opp}
          <div class="opportunity-card">
            <div class="opp-header">
              <span class="opp-category">{opp.category}</span>
              <span class="opp-status">Open</span>
            </div>
            <h3>{opp.title}</h3>
            <p class="opp-org">🏢 {opp.organization}</p>
            <div class="opp-details">
              <p>📍 {opp.location}</p>
              <p>📅 {opp.date}</p>
            </div>
            <div class="volunteers-progress">
              <div class="progress-bar">
                <div class="progress" style="width: {(opp.volunteersJoined / opp.volunteersNeeded) * 100}%"></div>
              </div>
              <span class="volunteers-text">{opp.volunteersJoined}/{opp.volunteersNeeded} volunteers</span>
            </div>
            <button class="btn-secondary">Join Now</button>
          </div>
        {/each}
      </div>
    </section>

    <section class="records-section">
      <div class="section-header">
        <h2>📋 Volunteer Records</h2>
        <button class="btn-secondary">View All</button>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Citizen</th>
              <th>Opportunity</th>
              <th>Hours</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {#each records as record}
              <tr>
                <td>{record.citizen}</td>
                <td>{record.opportunity}</td>
                <td>{record.hours}h</td>
                <td>{record.date}</td>
                <td><span class="status-badge completed">{record.status}</span></td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>

    <section class="actions-section">
      <h2>⚡ Quick Actions</h2>
      <div class="actions-grid">
        <button class="action-card">
          <div class="action-icon">🤝</div>
          <span>Join Program</span>
        </button>
        <button class="action-card">
          <div class="action-icon">📝</div>
          <span>Log Hours</span>
        </button>
        <button class="action-card">
          <div class="action-icon">🎖️</div>
          <span>Get Certificate</span>
        </button>
        <button class="action-card">
          <div class="action-icon">📊</div>
          <span>View History</span>
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
    background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
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

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    background: #ffedd5;
    border: 2px solid #f97316;
  }

  .stat-card.highlight {
    background: #fef3c7;
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
    background: #f97316;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-secondary {
    background: #f1f5f9;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .opportunities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
  }

  .opportunity-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
  }

  .opp-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
  }

  .opp-category {
    font-size: 0.75rem;
    color: #64748b;
  }

  .opp-status {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    background: #dcfce7;
    color: #166534;
  }

  .opportunity-card h3 {
    margin: 0.5rem 0;
    color: #1e293b;
  }

  .opp-org {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0.5rem 0;
  }

  .opp-details p {
    margin: 0.25rem 0;
    font-size: 0.875rem;
    color: #64748b;
  }

  .volunteers-progress {
    margin: 1rem 0;
  }

  .progress-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
  }

  .progress {
    height: 100%;
    background: linear-gradient(90deg, #f97316, #fb923c);
    border-radius: 4px;
  }

  .volunteers-text {
    font-size: 0.75rem;
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

  .status-badge.completed {
    background: #dcfce7;
    color: #166534;
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
    background: #ffedd5;
    border-color: #f97316;
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
