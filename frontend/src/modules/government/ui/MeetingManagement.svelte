<script lang="ts">
  import { writable } from 'svelte/store';

  interface Meeting {
    id: string;
    title: string;
    type: string;
    date: string;
    time: string;
    location: string;
    attendees: number;
    status: 'scheduled' | 'completed' | 'cancelled';
    agenda: string;
  }

  let meetings = writable<Meeting[]>([
    { id: '1', title: 'Họp Ban chỉ đạo tháng 2', type: 'Định kỳ', date: '2026-02-15', time: '14:00', location: 'Phòng họp A', attendees: 15, status: 'scheduled', agenda: 'Tổng kết tháng 1, triển khai nhiệm vụ tháng 2' },
    { id: '2', title: 'Họp thông tin TTHC mới', type: 'Đột xuất', date: '2026-02-13', time: '09:00', location: 'Phòng họp B', attendees: 8, status: 'completed', agenda: 'Cập nhật quy trình TTHC cấp mới' },
    { id: '3', title: 'Họp sơ kết quý I', type: 'Định kỳ', date: '2026-03-25', time: '14:00', location: 'Hội trường', attendees: 30, status: 'scheduled', agenda: 'Đánh giá KPI quý I' },
  ]);

  $: stats = { total: $meetings.length, scheduled: $meetings.filter(m => m.status === 'scheduled').length, completed: $meetings.filter(m => m.status === 'completed').length };
</script>

<div class="meeting-container">
  <header class="meeting-header">
    <div><h2>📅 Quản lý Họp phường/xã</h2><span class="subtitle">Lịch họp và nghị quyết</span></div>
    <button class="primary">➕ Lịch họp mới</button>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total}</span><span class="stat-label">Tổng cuộc họp</span></div>
    <div class="stat-item"><span class="stat-value">{stats.scheduled}</span><span class="stat-label">Sắp họp</span></div>
    <div class="stat-item"><span class="stat-value">{stats.completed}</span><span class="stat-label">Đã họp</span></div>
  </div>
  <div class="meeting-content">
    <div class="meetings-timeline">
      {#each $meetings as meeting}
        <div class="meeting-card {meeting.status}">
          <div class="meeting-date">
            <span class="day">{meeting.date.split('-')[2]}</span>
            <span class="month">Th{meeting.date.split('-')[1]}</span>
          </div>
          <div class="meeting-info">
            <h4>{meeting.title}</h4>
            <p class="meta">{meeting.time} • {meeting.location}</p>
            <p class="agenda">{meeting.agenda}</p>
            <div class="meeting-footer">
              <span class="attendees">👥 {meeting.attendees} người</span>
              <span class="type">{meeting.type}</span>
            </div>
          </div>
          <div class="meeting-status">
            <span class="status-badge {meeting.status}">
              {meeting.status === 'scheduled' ? 'Sắp họp' : meeting.status === 'completed' ? 'Đã họp' : 'Hủy'}
            </span>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .meeting-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .meeting-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .meeting-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .meeting-content { flex: 1; overflow-y: auto; padding: 20px; }
  .meetings-timeline { display: flex; flex-direction: column; gap: 12px; }
  .meeting-card { display: flex; gap: 16px; background: white; border-radius: 12px; padding: 16px; border-left: 4px solid #3b82f6; }
  .meeting-card.completed { opacity: 0.7; }
  .meeting-card.cancelled { opacity: 0.5; }
  .meeting-date { display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 60px; padding: 8px; background: #f8fafc; border-radius: 8px; }
  .meeting-date .day { font-size: 1.5rem; font-weight: 700; color: #3b82f6; }
  .meeting-date .month { font-size: 0.75rem; color: #64748b; }
  .meeting-info { flex: 1; }
  .meeting-info h4 { margin: 0 0 4px; font-size: 1rem; }
  .meeting-info .meta { margin: 0; font-size: 0.8rem; color: #64748b; }
  .meeting-info .agenda { margin: 8px 0; font-size: 0.85rem; color: #374151; }
  .meeting-footer { display: flex; gap: 12px; font-size: 0.75rem; color: #64748b; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.scheduled { background: #dbeafe; color: #1e40af; }
  .status-badge.completed { background: #dcfce7; color: #166534; }
  .status-badge.cancelled { background: #fee2e2; color: #991b1b; }
</style>
