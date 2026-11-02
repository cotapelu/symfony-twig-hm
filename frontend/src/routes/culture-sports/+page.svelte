<script lang="ts">
  import { goto } from '$app/navigation';

  interface Event {
    id: string;
    name: string;
    date: string;
    location: string;
    type: string;
    participants: number;
    status: string;
  }

  interface Facility {
    id: string;
    name: string;
    type: string;
    address: string;
    capacity: number;
    availability: string;
  }

  interface CulturalActivity {
    id: string;
    name: string;
    category: string;
    schedule: string;
    location: string;
    participants: number;
  }

  const events: Event[] = [
    { id: '1', name: 'Festival mùa xuân 2026', date: '2026-02-15', location: 'Trung tâm Văn hóa', type: 'Festival', participants: 500, status: 'Sắp diễn ra' },
    { id: '2', name: 'Giải bóng đá cấp phường', date: '2026-02-20', location: 'Sân vận động', type: 'Thể thao', participants: 120, status: 'Đăng ký' },
    { id: '3', name: 'Triển lãm tranh', date: '2026-03-01', location: 'Nhà văn hóa', type: 'Văn hóa', participants: 80, status: 'Sắp diễn ra' },
    { id: '4', name: 'Đêm văn nghệ cộng đồng', date: '2026-03-05', location: 'Sân trường', type: 'Văn nghệ', participants: 300, status: 'Sắp diễn ra' }
  ];

  const facilities: Facility[] = [
    { id: '1', name: 'Nhà văn hóa Phường', type: 'Cultural Center', address: '123 Đường Láng', capacity: 500, availability: 'Có sẵn' },
    { id: '2', name: 'Sân vận động', type: 'Sports', address: '456 Nguyễn Trãi', capacity: 1000, availability: 'Đã đặt' },
    { id: '3', name: 'Thư viện', type: 'Library', address: '789 Lý Thường Kiệt', capacity: 100, availability: 'Có sẵn' },
    { id: '4', name: 'Phòng gym cộng đồng', type: 'Sports', address: '321 Hoàng Quốc Việt', capacity: 50, availability: 'Có sẵn' }
  ];

  const activities: CulturalActivity[] = [
    { id: '1', name: 'Yoga buổi sáng', category: 'Thể thao', schedule: '6:00 - 7:00', location: 'Sân vận động', participants: 45 },
    { id: '2', name: 'Học đàn guitar', category: 'Văn nghệ', schedule: '19:00 - 20:30', location: 'Nhà văn hóa', participants: 20 },
    { id: '3', name: 'Múa cổ truyền', category: 'Văn hóa', schedule: '14:00 - 16:00', location: 'Nhà văn hóa', participants: 30 },
    { id: '4', name: 'Bơi lội', category: 'Thể thao', schedule: '17:00 - 19:00', location: 'Bể bơi', participants: 25 }
  ];

  function navigateTo(path: string) {
    goto(path);
  }
</script>

<svelte:head>
  <title>Culture & Sports - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => navigateTo('/government')}>
        ← Back
      </button>
      <div>
        <h1>🎭 Culture & Sports</h1>
        <p class="subtitle">Văn hóa - Thể thao và hoạt động cộng đồng</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="stats-section">
      <h2>📊 Overview</h2>
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-icon">🎪</div>
          <div class="stat-info">
            <span class="stat-value">24</span>
            <span class="stat-label">Events This Month</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🏟️</div>
          <div class="stat-info">
            <span class="stat-value">8</span>
            <span class="stat-label">Facilities</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <span class="stat-value">2,450</span>
            <span class="stat-label">Total Participants</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📅</div>
          <div class="stat-info">
            <span class="stat-value">15</span>
            <span class="stat-label">Upcoming Events</span>
          </div>
        </div>
      </div>
    </section>

    <section class="events-section">
      <div class="section-header">
        <h2>📅 Upcoming Events</h2>
        <button class="btn-primary">+ Create Event</button>
      </div>
      <div class="events-grid">
        {#each events as event}
          <div class="event-card">
            <div class="event-header">
              <span class="event-type">{event.type}</span>
              <span class="event-status {event.status === 'Sắp diễn ra' ? 'upcoming' : 'open'}">{event.status}</span>
            </div>
            <h3>{event.name}</h3>
            <div class="event-details">
              <p>📅 {event.date}</p>
              <p>📍 {event.location}</p>
              <p>👥 {event.participants} người tham gia</p>
            </div>
            <button class="btn-secondary">Register</button>
          </div>
        {/each}
      </div>
    </section>

    <section class="facilities-section">
      <div class="section-header">
        <h2>🏛️ Facilities</h2>
        <button class="btn-primary">+ Book Facility</button>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Type</th>
              <th>Address</th>
              <th>Capacity</th>
              <th>Availability</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            {#each facilities as facility}
              <tr>
                <td>{facility.name}</td>
                <td>{facility.type}</td>
                <td>{facility.address}</td>
                <td>{facility.capacity}</td>
                <td><span class="status-badge {facility.availability === 'Có sẵn' ? 'active' : 'pending'}">{facility.availability}</span></td>
                <td><button class="btn-small">Book</button></td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>

    <section class="activities-section">
      <div class="section-header">
        <h2>🎪 Regular Activities</h2>
        <button class="btn-primary">+ Add Activity</button>
      </div>
      <div class="activities-grid">
        {#each activities as activity}
          <div class="activity-card">
            <div class="activity-icon">🏃</div>
            <h3>{activity.name}</h3>
            <p class="activity-category">{activity.category}</p>
            <div class="activity-details">
              <p>🕐 {activity.schedule}</p>
              <p>📍 {activity.location}</p>
              <p>👥 {activity.participants} người</p>
            </div>
            <button class="btn-secondary">Join</button>
          </div>
        {/each}
      </div>
    </section>

    <section class="actions-section">
      <h2>⚡ Quick Actions</h2>
      <div class="actions-grid">
        <button class="action-card">
          <div class="action-icon">📋</div>
          <span>Register for Event</span>
        </button>
        <button class="action-card">
          <div class="action-icon">🏟️</div>
          <span>Book Facility</span>
        </button>
        <button class="action-card">
          <div class="action-icon">🎨</div>
          <span>Submit Cultural Work</span>
        </button>
        <button class="action-card">
          <div class="action-icon">📊</div>
          <span>View Calendar</span>
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
    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
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
    background: #f3e8ff;
    border: 2px solid #a855f7;
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
    background: #a855f7;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-secondary {
    background: #f1f5f9;
    color: #1e293b;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .btn-small {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
  }

  .event-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
  }

  .event-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
  }

  .event-type {
    font-size: 0.75rem;
    color: #64748b;
  }

  .event-status {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
  }

  .event-status.upcoming {
    background: #dbeafe;
    color: #1d4ed8;
  }

  .event-status.open {
    background: #dcfce7;
    color: #166534;
  }

  .event-card h3 {
    margin: 0.5rem 0;
    color: #1e293b;
  }

  .event-details p {
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

  .activities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
  }

  .activity-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
  }

  .activity-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
  }

  .activity-card h3 {
    margin: 0.5rem 0;
    color: #1e293b;
  }

  .activity-category {
    color: #a855f7;
    font-weight: 600;
    font-size: 0.875rem;
  }

  .activity-details {
    margin: 1rem 0;
  }

  .activity-details p {
    margin: 0.25rem 0;
    font-size: 0.875rem;
    color: #64748b;
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
    background: #f3e8ff;
    border-color: #a855f7;
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
