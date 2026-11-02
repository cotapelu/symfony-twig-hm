<script lang="ts">
  interface AnalyticsData {
    users: number;
    orders: number;
    revenue: number;
    growth: number;
  }

  let data: AnalyticsData = { users: 12543, orders: 8934, revenue: 156780000, growth: 12.5 };
  let timeRange = '7d';

  function formatCurrency(amount: number) {
    return '₫' + amount.toLocaleString();
  }
</script>

<div class="analytics-page">
  <header>
    <h1>📊 Analytics</h1>
    <div class="time-selector">
      {#each ['24h', '7d', '30d', '90d'] as range}
        <button class:active={timeRange === range} on:click={() => timeRange = range}>
          {range}
        </button>
      {/each}
    </div>
  </header>

  <div class="stats-grid">
    <div class="stat-card users">
      <div class="stat-icon">👥</div>
      <div class="stat-info">
        <span class="label">Users</span>
        <span class="value">{data.users.toLocaleString()}</span>
        <span class="trend up">↑ 8.2%</span>
      </div>
    </div>
    <div class="stat-card orders">
      <div class="stat-icon">📦</div>
      <div class="stat-info">
        <span class="label">Orders</span>
        <span class="value">{data.orders.toLocaleString()}</span>
        <span class="trend up">↑ 12.1%</span>
      </div>
    </div>
    <div class="stat-card revenue">
      <div class="stat-icon">💰</div>
      <div class="stat-info">
        <span class="label">Revenue</span>
        <span class="value">{formatCurrency(data.revenue)}</span>
        <span class="trend up">↑ {data.growth}%</span>
      </div>
    </div>
  </div>

  <div class="charts-section">
    <div class="chart-card">
      <h3>Revenue Trend</h3>
      <div class="chart-placeholder">
        <div class="bar-chart">
          {#each [30, 45, 35, 60, 55, 80, 75] as height}
            <div class="bar" style="height: {height}%"></div>
          {/each}
        </div>
        <div class="chart-labels">
          <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
        </div>
      </div>
    </div>

    <div class="chart-card">
      <h3>Top Categories</h3>
      <div class="category-list">
        {#each [
          { name: 'Food Delivery', value: 35, color: '#ff6b6b' },
          { name: 'E-commerce', value: 28, color: '#4ecdc4' },
          { name: 'Services', value: 20, color: '#45b7d1' },
          { name: 'Healthcare', value: 17, color: '#96ceb4' }
        ] as cat}
          <div class="category-item">
            <div class="cat-info">
              <span class="dot" style="background: {cat.color}"></span>
              <span>{cat.name}</span>
            </div>
            <div class="cat-bar-container">
              <div class="cat-bar" style="width: {cat.value}%; background: {cat.color}"></div>
            </div>
            <span class="cat-value">{cat.value}%</span>
          </div>
        {/each}
      </div>
    </div>
  </div>
</div>

<style>
  .analytics-page { padding: 20px; max-width: 1200px; margin: 0 auto; }
  header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
  h1 { font-size: 1.8rem; }
  .time-selector { display: flex; gap: 5px; }
  .time-selector button { padding: 8px 16px; border: 1px solid #ddd; background: white; border-radius: 6px; cursor: pointer; }
  .time-selector button.active { background: #007bff; color: white; border-color: #007bff; }
  .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
  .stat-card { display: flex; align-items: center; gap: 20px; padding: 25px; background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
  .users .stat-icon { background: #e3f2fd; }
  .orders .stat-icon { background: #fff3e0; }
  .revenue .stat-icon { background: #e8f5e9; }
  .stat-info { display: flex; flex-direction: column; }
  .stat-info .label { color: #666; font-size: 0.9rem; }
  .stat-info .value { font-size: 1.8rem; font-weight: bold; }
  .trend { font-size: 0.85rem; font-weight: 500; }
  .trend.up { color: #28a745; }
  .charts-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; }
  .chart-card { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .chart-card h3 { margin: 0 0 20px; font-size: 1.1rem; }
  .bar-chart { display: flex; align-items: flex-end; justify-content: space-between; height: 200px; padding: 0 10px; }
  .bar { width: 40px; background: linear-gradient(to top, #667eea, #764ba2); border-radius: 6px 6px 0 0; margin: 0 5px; }
  .chart-labels { display: flex; justify-content: space-between; padding: 10px 10px 0; color: #999; font-size: 0.8rem; }
  .category-list { display: flex; flex-direction: column; gap: 15px; }
  .category-item { display: flex; align-items: center; gap: 15px; }
  .cat-info { display: flex; align-items: center; gap: 10px; width: 120px; }
  .dot { width: 10px; height: 10px; border-radius: 50%; }
  .cat-bar-container { flex: 1; height: 10px; background: #f1f3f5; border-radius: 5px; overflow: hidden; }
  .cat-bar { height: 100%; border-radius: 5px; transition: width 0.5s ease; }
  .cat-value { width: 50px; text-align: right; font-weight: 500; }
</style>
