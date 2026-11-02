<script lang="ts">
  import { writable } from 'svelte/store';

  interface StreetLight {
    id: string;
    location: string;
    status: 'on' | 'off' | 'dim' | 'fault';
    brightness: number;
    power: number;
    lastMaintenance: Date;
    ward: string;
  }

  let lights = writable<StreetLight[]>([
    { id: '1', location: 'Đường Nguyễn Trãi', status: 'on', brightness: 100, power: 150, lastMaintenance: new Date('2025-12-01'), ward: 'Phường X' },
    { id: '2', location: 'Đường Lê Lợi', status: 'on', brightness: 100, power: 150, lastMaintenance: new Date('2025-11-15'), ward: 'Phường X' },
    { id: '3', location: 'Đường Quang Trung', status: 'dim', brightness: 50, power: 75, lastMaintenance: new Date('2025-10-20'), ward: 'Phường X' },
    { id: '4', location: 'Công viên A', status: 'on', brightness: 100, power: 200, lastMaintenance: new Date('2025-09-10'), ward: 'Phường X' },
    { id: '5', location: 'Ngõ 5 - Đường 30/4', status: 'fault', brightness: 0, power: 0, lastMaintenance: new Date('2025-08-01'), ward: 'Phường Y' },
  ]);

  let activeSchedule = writable({ start: '18:00', end: '05:00' });

  const statusColors: Record<string, string> = {
    on: '#10b981',
    off: '#64748b',
    dim: '#f59e0b',
    fault: '#ef4444',
  };

  function toggleLight(id: string) {
    lights.update(l => l.map(light => 
      light.id === id ? { ...light, status: light.status === 'on' ? 'off' : 'on', brightness: light.status === 'on' ? 0 : 100 } : light
    ));
  }

  $: onCount = $lights.filter(l => l.status === 'on').length;
  $: offCount = $lights.filter(l => l.status === 'off').length;
  $: faultCount = $lights.filter(l => l.status === 'fault').length;
</script>

<div class="lighting-container">
  <header class="lighting-header">
    <div class="header-left">
      <h2>💡 Quản lý Đèn đường</h2>
      <span class="subtitle">Điều khiển và giám sát hệ thống chiếu sáng</span>
    </div>
    <div class="schedule-info">
      <span>Lịch: { $activeSchedule.start } - { $activeSchedule.end }</span>
      <button class="edit-btn">✏️</button>
    </div>
  </header>

  <div class="stats-bar">
    <div class="stat-item on">
      <span class="stat-value">{onCount}</span>
      <span class="stat-label">Đang bật</span>
    </div>
    <div class="stat-item">
      <span class="stat-value">{offCount}</span>
      <span class="stat-label">Đang tắt</span>
    </div>
    <div class="stat-item fault">
      <span class="stat-value">{faultCount}</span>
      <span class="stat-label">Sự cố</span>
    </div>
  </div>

  <div class="lighting-content">
    <div class="lights-grid">
      {#each $lights as light (light.id)}
        <div class="light-card" class:fault={light.status === 'fault'}>
          <div class="light-icon" style="opacity: {light.brightness / 100}">
            💡
          </div>
          <div class="light-info">
            <h4>{light.location}</h4>
            <p>{light.ward}</p>
            <span class="status-badge" style="background: {statusColors[light.status]}">
              {light.status === 'on' ? 'Bật' : light.status === 'off' ? 'Tắt' : light.status === 'dim' ? 'Mờ' : 'Sự cố'}
            </span>
          </div>
          <div class="light-controls">
            <button class="control-btn" class:active={light.status === 'on'} on:click={toggleLight(light.id)}>
              💡
            </button>
            <button class="control-btn">⚙️</button>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .lighting-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .lighting-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .header-left h2 { margin: 0; font-size: 1.25rem; color: #1e293b; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .schedule-info { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; color: #475569; }
  .edit-btn { background: transparent; border: none; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-item.on { background: #d1fae5; }
  .stat-item.fault { background: #fee2e2; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .lighting-content { flex: 1; overflow-y: auto; padding: 20px; }
  .lights-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
  .light-card { display: flex; align-items: center; gap: 16px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .light-card.fault { border: 2px solid #ef4444; }
  .light-icon { font-size: 2rem; }
  .light-info { flex: 1; }
  .light-info h4 { margin: 0; font-size: 0.9375rem; }
  .light-info p { margin: 4px 0; font-size: 0.8rem; color: #64748b; }
  .status-badge { padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; color: white; }
  .light-controls { display: flex; gap: 8px; }
  .control-btn { padding: 8px 12px; border: 1px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; }
  .control-btn.active { background: #fef3c7; }
</style>
