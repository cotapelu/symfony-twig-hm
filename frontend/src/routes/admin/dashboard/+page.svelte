<script lang="ts">
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';

  let user: any = null;

  onMount(() => {
    const stored = localStorage.getItem('adminUser');
    if (!stored) {
      goto('/admin/login');
    } else {
      user = JSON.parse(stored);
    }
  });

  function logout() {
    localStorage.removeItem('adminUser');
    goto('/admin/login');
  }

  const stats = [
    { label: 'Total Population', value: '15,847', icon: '👥', color: 'blue' },
    { label: 'Households', value: '4,521', icon: '🏠', color: 'green' },
    { label: 'Pending Documents', value: '234', icon: '📋', color: 'orange' },
    { label: 'Active TTHC', value: '156', icon: '✅', color: 'purple' }
  ];

  const recentActivities = [
    { type: 'population', action: 'New citizen registered', time: '2 min ago' },
    { type: 'document', action: 'Document approved', time: '15 min ago' },
    { type: 'complaint', action: 'New complaint submitted', time: '1 hour ago' },
    { type: 'ttth', action: 'TTHC application processed', time: '2 hours ago' }
  ];
</script>

<svelte:head>
  <title>Admin Dashboard - Phường/Xã Super App</title>
</svelte:head>

<div class="admin-layout">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="logo">🏛️</div>
      <h2>Admin Panel</h2>
    </div>

    <nav class="sidebar-nav">
      <a href="/admin/dashboard" class="nav-item active">
        <span>📊</span> Dashboard
      </a>
      <a href="/admin/population" class="nav-item">
        <span>👥</span> Population
      </a>
      <a href="/admin/household" class="nav-item">
        <span>🏠</span> Household
      </a>
      <a href="/admin/documents" class="nav-item">
        <span>📄</span> Documents
      </a>
      <a href="/admin/ttth" class="nav-item">
        <span>✅</span> TTHC
      </a>
      <a href="/admin/complaints" class="nav-item">
        <span>📝</span> Complaints
      </a>
      <a href="/admin/finance" class="nav-item">
        <span>💰</span> Finance
      </a>
      <a href="/admin/reports" class="nav-item">
        <span>📈</span> Reports
      </a>
      <a href="/admin/settings" class="nav-item">
        <span>⚙️</span> Settings
      </a>
    </nav>

    <div class="sidebar-footer">
      {#if user}
        <div class="user-info">
          <div class="user-avatar">{user.username[0].toUpperCase()}</div>
          <div class="user-details">
            <span class="user-name">{user.username}</span>
            <span class="user-role">{user.role}</span>
          </div>
        </div>
        <button class="logout-btn" on:click={logout}>Logout</button>
      {/if}
    </div>
  </aside>

  <main class="main-content">
    <header class="content-header">
      <h1>Dashboard</h1>
      <div class="header-actions">
        <button class="notification-btn">
          🔔 <span class="badge">3</span>
        </button>
        <span class="date">{new Date().toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
      </div>
    </header>

    <div class="content-body">
      <section class="stats-grid">
        {#each stats as stat}
          <div class="stat-card {stat.color}">
            <div class="stat-icon">{stat.icon}</div>
            <div class="stat-info">
              <span class="stat-value">{stat.value}</span>
              <span class="stat-label">{stat.label}</span>
            </div>
          </div>
        {/each}
      </section>

      <section class="recent-activity">
        <h2>Recent Activity</h2>
        <div class="activity-list">
          {#each recentActivities as activity}
            <div class="activity-item">
              <div class="activity-icon">
                {#if activity.type === 'population'}👥
                {:else if activity.type === 'document'}📄
                {:else if activity.type === 'complaint'}📝
                {:else}✅{/if}
              </div>
              <div class="activity-info">
                <span class="activity-action">{activity.action}</span>
                <span class="activity-time">{activity.time}</span>
              </div>
            </div>
          {/each}
        </div>
      </section>

      <section class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="actions-grid">
          <button class="action-btn" on:click={() => goto('/admin/population')}>
            <span>➕</span> Add Citizen
          </button>
          <button class="action-btn" on:click={() => goto('/admin/documents')}>
            <span>📄</span> Process Document
          </button>
          <button class="action-btn" on:click={() => goto('/admin/complaints')}>
            <span>📝</span> View Complaints
          </button>
          <button class="action-btn" on:click={() => goto('/admin/reports')}>
            <span>📊</span> Generate Report
          </button>
        </div>
      </section>
    </div>
  </main>
</div>

<style>
  .admin-layout {
    display: flex;
    min-height: 100vh;
    background: #f8fafc;
  }

  .sidebar {
    width: 260px;
    background: #1e293b;
    color: white;
    display: flex;
    flex-direction: column;
    position: fixed;
    height: 100vh;
  }

  .sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
  }

  .logo {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
  }

  .sidebar-header h2 {
    margin: 0;
    font-size: 1.25rem;
  }

  .sidebar-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
  }

  .nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.2s;
  }

  .nav-item:hover, .nav-item.active {
    background: rgba(255,255,255,0.1);
    color: white;
  }

  .nav-item.active {
    border-left: 3px solid #3b82f6;
  }

  .sidebar-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.1);
  }

  .user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .user-avatar {
    width: 40px;
    height: 40px;
    background: #3b82f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
  }

  .user-details {
    display: flex;
    flex-direction: column;
  }

  .user-name {
    font-weight: 600;
  }

  .user-role {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.6);
    text-transform: capitalize;
  }

  .logout-btn {
    width: 100%;
    padding: 0.5rem;
    background: rgba(255,255,255,0.1);
    border: none;
    color: white;
    border-radius: 6px;
    cursor: pointer;
  }

  .logout-btn:hover {
    background: rgba(255,255,255,0.2);
  }

  .main-content {
    flex: 1;
    margin-left: 260px;
  }

  .content-header {
    background: white;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .content-header h1 {
    margin: 0;
    color: #1e293b;
  }

  .header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .notification-btn {
    position: relative;
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
  }

  .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    font-size: 0.625rem;
    padding: 0.125rem 0.375rem;
    border-radius: 9999px;
  }

  .date {
    color: #64748b;
    font-size: 0.875rem;
  }

  .content-body {
    padding: 2rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .stat-card.blue { border-left: 4px solid #3b82f6; }
  .stat-card.green { border-left: 4px solid #10b981; }
  .stat-card.orange { border-left: 4px solid #f59e0b; }
  .stat-card.purple { border-left: 4px solid #8b5cf6; }

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

  section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  section h2 {
    margin: 0 0 1rem;
    color: #1e293b;
    font-size: 1.125rem;
  }

  .activity-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
  }

  .activity-icon {
    font-size: 1.25rem;
  }

  .activity-info {
    display: flex;
    flex-direction: column;
  }

  .activity-action {
    font-weight: 500;
    color: #1e293b;
  }

  .activity-time {
    font-size: 0.75rem;
    color: #64748b;
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
  }

  .action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    color: #1e293b;
    transition: all 0.2s;
  }

  .action-btn:hover {
    background: #eff6ff;
    border-color: #3b82f6;
  }

  @media (max-width: 768px) {
    .sidebar {
      width: 60px;
    }

    .sidebar-header h2, .nav-item span:not(:first-child), .user-details, .logout-btn {
      display: none;
    }

    .main-content {
      margin-left: 60px;
    }
  }
</style>
