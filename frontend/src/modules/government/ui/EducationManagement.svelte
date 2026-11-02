<script lang="ts">
  import { writable } from 'svelte/store';

  interface School {
    id: string;
    name: string;
    type: 'primary' | 'secondary' | 'high';
    address: string;
    students: number;
    teachers: number;
    facilities: string[];
    status: 'active' | 'inactive';
    ward: string;
  }

  let schools = writable<School[]>([
    { id: '1', name: 'Tiểu học Nguyễn Trãi', type: 'primary', address: '123 Nguyễn Trãi', students: 450, teachers: 25, facilities: ['Thư viện', 'Phòng IT', 'Sân chơi'], status: 'active', ward: 'Khu vực 1' },
    { id: '2', name: 'THCS Lê Lợi', type: 'secondary', address: '45 Lê Lợi', students: 680, teachers: 40, facilities: ['Thư viện', 'Phòng Lab', 'Nhà đa năng'], status: 'active', ward: 'Khu vực 1' },
    { id: '3', name: 'THPT Quang Trung', type: 'high', address: '78 Quang Trung', students: 520, teachers: 35, facilities: ['Thư viện', 'Phòng Lab', 'Sân thể thao'], status: 'active', ward: 'Khu vực 2' },
  ]);

  let activeType: 'all' | 'primary' | 'secondary' | 'high' = 'all';

  const typeIcons: Record<string, string> = { primary: '🏫', secondary: '🏫', high: '🏫' };

  $: filteredSchools = $schools.filter(s => activeType === 'all' || s.type === activeType);
  $: stats = { total: $schools.length, students: $schools.reduce((a, b) => a + b.students, 0), teachers: $schools.reduce((a, b) => a + b.teachers, 0) };
</script>

<div class="edu-container">
  <header class="edu-header">
    <div><h2>🏫 Quản lý Giáo dục</h2><span class="subtitle">Trường học và học sinh</span></div>
    <button class="primary">➕ Thêm trường</button>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total}</span><span class="stat-label">Trường</span></div>
    <div class="stat-item"><span class="stat-value">{stats.students}</span><span class="stat-label">Học sinh</span></div>
    <div class="stat-item"><span class="stat-value">{stats.teachers}</span><span class="stat-label">Giáo viên</span></div>
  </div>
  <div class="tab-nav">
    <button class="tab-btn" class:active={activeType === 'all'} on:click={() => activeType = 'all'}>Tất cả</button>
    <button class="tab-btn" class:active={activeType === 'primary'} on:click={() => activeType = 'primary'}>Tiểu học</button>
    <button class="tab-btn" class:active={activeType === 'secondary'} on:click={() => activeType = 'secondary'}>THCS</button>
    <button class="tab-btn" class:active={activeType === 'high'} on:click={() => activeType = 'high'}>THPT</button>
  </div>
  <div class="edu-content">
    <div class="schools-grid">
      {#each filteredSchools as school (school.id)}
        <div class="school-card">
          <div class="school-header">
            <span class="school-icon">🏫</span>
            <div class="school-info">
              <h4>{school.name}</h4>
              <p>{school.address}</p>
            </div>
          </div>
          <div class="school-stats">
            <div class="school-stat"><span class="label">Học sinh</span><span class="value">{school.students}</span></div>
            <div class="school-stat"><span class="label">Giáo viên</span><span class="value">{school.teachers}</span></div>
          </div>
          <div class="school-facilities">
            {#each school.facilities as facility}
              <span class="facility-tag">{facility}</span>
            {/each}
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .edu-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .edu-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .edu-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .tab-nav { display: flex; gap: 8px; padding: 12px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .tab-btn { padding: 10px 20px; border: none; background: transparent; cursor: pointer; border-radius: 8px; }
  .tab-btn.active { background: #dbeafe; color: #1d4ed8; }
  .edu-content { flex: 1; overflow-y: auto; padding: 20px; }
  .schools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
  .school-card { background: white; border-radius: 12px; padding: 16px; }
  .school-header { display: flex; gap: 12px; margin-bottom: 12px; }
  .school-icon { font-size: 2rem; }
  .school-info h4 { margin: 0; font-size: 0.9375rem; }
  .school-info p { margin: 4px 0 0; font-size: 0.8rem; color: #64748b; }
  .school-stats { display: flex; gap: 24px; margin-bottom: 12px; }
  .school-stat { display: flex; flex-direction: column; }
  .school-stat .label { font-size: 0.75rem; color: #64748b; }
  .school-stat .value { font-size: 1.25rem; font-weight: 700; }
  .school-facilities { display: flex; gap: 8px; flex-wrap: wrap; }
  .facility-tag { padding: 4px 10px; background: #f1f5f9; border-radius: 12px; font-size: 0.7rem; }
</style>
