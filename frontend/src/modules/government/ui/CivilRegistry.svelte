<script lang="ts">
  import { writable } from 'svelte/store';

  interface CivilRecord {
    id: string;
    recordNumber: string;
    recordType: string;
    applicant: string;
    subject: string;
    registrationDate: string;
    status: 'pending' | 'approved' | 'completed';
    documents: string[];
  }

  let records = writable<CivilRecord[]>([
    { id: '1', recordNumber: 'KS-001', recordType: 'Khai sinh', applicant: 'Nguyễn Văn A', subject: 'Nguyễn Văn B (con)', registrationDate: '2026-02-10', status: 'completed', documents: ['Giấy chứng sinh', 'CMND cha'] },
    { id: '2', recordNumber: 'KT-001', recordType: 'Khai tử', applicant: 'Trần Thị B', subject: 'Trần Văn C (chồng)', registrationDate: '2026-02-08', status: 'approved', documents: ['Giấy chứng tử', 'CMND'] },
    { id: '3', recordNumber: 'HK-001', recordType: 'Hôn nhân', applicant: 'Lê Văn C & Phạm Thị D', subject: 'Đăng ký kết hôn', registrationDate: '2026-02-12', status: 'pending', documents: ['CMND 2 bên', 'Giấy xác nhận tình trạng'] },
    { id: '4', recordNumber: 'TT-001', recordType: 'Tuyên bố T/Trí', applicant: 'Phạm Văn E', subject: 'Tuyên bố mất tích', registrationDate: '2026-01-20', status: 'pending', documents: ['Đơn trình bày', 'CMND'] },
  ]);

  $: stats = { total: $records.length, pending: $records.filter(r => r.status === 'pending').length, completed: $records.filter(r => r.status === 'completed').length };
</script>

<div class="civil-container">
  <header class="civil-header">
    <div><h2>📜 Quản lý Hộ tịch</h2><span class="subtitle">Đăng ký khai sinh, khai tử, hôn nhân</span></div>
    <button class="primary">➕ Hồ sơ mới</button>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total}</span><span class="stat-label">Tổng hồ sơ</span></div>
    <div class="stat-item"><span class="stat-value">{stats.pending}</span><span class="stat-label">Chờ xử lý</span></div>
    <div class="stat-item"><span class="stat-value">{stats.completed}</span><span class="stat-label">Hoàn thành</span></div>
  </div>
  <div class="civil-content">
    <div class="records-list">
      {#each $records as record}
        <div class="record-card">
          <div class="record-header">
            <span class="record-icon">
              {record.recordType === 'Khai sinh' ? '👶' : record.recordType === 'Khai tử' ? '🕯️' : record.recordType === 'Hôn nhân' ? '💑' : '📋'}
            </span>
            <div class="record-info">
              <h4>{record.recordType}</h4>
              <p class="record-number">Số hồ sơ: {record.recordNumber}</p>
            </div>
            <span class="status-badge {record.status}">
              {record.status === 'pending' ? 'Chờ xử lý' : record.status === 'approved' ? 'Đã duyệt' : 'Hoàn thành'}
            </span>
          </div>
          <div class="record-details">
            <div class="detail-row">
              <span class="label">Người yêu cầu:</span>
              <span class="value">{record.applicant}</span>
            </div>
            <div class="detail-row">
              <span class="label">Đối tượng:</span>
              <span class="value">{record.subject}</span>
            </div>
            <div class="detail-row">
              <span class="label">Ngày đăng ký:</span>
              <span class="value">{record.registrationDate}</span>
            </div>
          </div>
          <div class="record-documents">
            <span class="label">Tài liệu:</span>
            <div class="doc-tags">
              {#each record.documents as doc}
                <span class="doc-tag">📄 {doc}</span>
              {/each}
            </div>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .civil-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .civil-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .civil-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .civil-content { flex: 1; overflow-y: auto; padding: 20px; }
  .records-list { display: flex; flex-direction: column; gap: 12px; }
  .record-card { background: white; border-radius: 12px; padding: 16px; }
  .record-header { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; }
  .record-icon { font-size: 2rem; }
  .record-info { flex: 1; }
  .record-info h4 { margin: 0; font-size: 1rem; }
  .record-info .record-number { margin: 4px 0 0; font-size: 0.8rem; color: #64748b; }
  .record-details { margin-bottom: 12px; }
  .detail-row { display: flex; gap: 8px; padding: 4px 0; font-size: 0.85rem; }
  .detail-row .label { color: #64748b; min-width: 120px; }
  .detail-row .value { font-weight: 500; }
  .record-documents { padding-top: 12px; border-top: 1px solid #e2e8f0; }
  .record-documents .label { font-size: 0.8rem; color: #64748b; display: block; margin-bottom: 8px; }
  .doc-tags { display: flex; gap: 8px; flex-wrap: wrap; }
  .doc-tag { padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 0.75rem; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.pending { background: #fef3c7; color: #92400e; }
  .status-badge.approved { background: #dbeafe; color: #1e40af; }
  .status-badge.completed { background: #dcfce7; color: #166534; }
</style>
