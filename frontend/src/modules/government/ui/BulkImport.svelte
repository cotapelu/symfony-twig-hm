<script lang="ts">
  import { slide, fade } from 'svelte/transition';
  
  // State
  let file: File | null = null;
  let importData: any[] = [];
  let validationErrors: any[] = [];
  let importResult: any = null;
  let loading = false;
  let currentStep: 'upload' | 'validate' | 'preview' | 'import' = 'upload';
  let dragOver = false;
  
  // Template CSV content
  const csvTemplate = `fullName,identityNumber,province,district,ward,street,apartmentNumber,email,phone,dateOfBirth,gender,ethnicity,nationality,occupation,educationLevel,maritalStatus,householdId
Nguyen Van A,012345678901,Ha Noi,Ba Dinh,Phuc Xa,Tran Phu,12A,nguyenvana@example.com,0901234567,1990-01-01,male,Kinh,Vietnam,Engineer,University,married,HH001
Tran Thi B,098765432109,TP.HCM,Quan 1,Ben Nghe,Le Loi,5B,tranthib@example.com,0912345678,1985-05-15,female,Kinh,Vietnam,Teacher,University,single,HH002`;
  
  function downloadTemplate() {
    const blob = new Blob([csvTemplate], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'citizen_import_template.csv';
    a.click();
    URL.revokeObjectURL(url);
  }
  
  function handleDragOver(e: DragEvent) {
    e.preventDefault();
    dragOver = true;
  }
  
  function handleDragLeave() {
    dragOver = false;
  }
  
  function handleDrop(e: DragEvent) {
    e.preventDefault();
    dragOver = false;
    
    const droppedFile = e.dataTransfer?.files[0];
    if (droppedFile && (droppedFile.type === 'text/csv' || droppedFile.name.endsWith('.csv'))) {
      file = droppedFile;
      handleFileUpload();
    }
  }
  
  function handleFileSelect(e: Event) {
    const input = e.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      file = input.files[0];
      handleFileUpload();
    }
  }
  
  async function handleFileUpload() {
    if (!file) return;
    
    loading = true;
    try {
      const text = await file.text();
      const lines = text.split('\n');
      const headers = lines[0].split(',').map(h => h.trim());
      
      importData = [];
      for (let i = 1; i < lines.length; i++) {
        if (lines[i].trim()) {
          const values = lines[i].split(',').map(v => v.trim());
          const row: any = {};
          headers.forEach((h, idx) => {
            row[h] = values[idx] || '';
          });
          importData.push(row);
        }
      }
      
      await validateData();
      currentStep = validationErrors.length > 0 ? 'validate' : 'preview';
    } catch (e) {
      alert('Lỗi đọc file: ' + (e as Error).message);
    } finally {
      loading = false;
    }
  }
  
  async function validateData() {
    try {
      const response = await fetch('/api/government/import/validate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ citizens: importData })
      });
      
      if (response.ok) {
        const result = await response.json();
        validationErrors = result.errors || [];
      }
    } catch (e) {
      // Fallback client-side validation
      validationErrors = [];
      importData.forEach((row, idx) => {
        if (!row.fullName) validationErrors.push({ row: idx + 1, error: 'Thiếu họ tên' });
        if (!row.identityNumber) validationErrors.push({ row: idx + 1, error: 'Thiếu số CCCD/CMND' });
        if (!row.province || !row.district || !row.ward || !row.street) {
          validationErrors.push({ row: idx + 1, error: 'Thiếu thông tin địa chỉ' });
        }
      });
    }
  }
  
  async function importCitizens() {
    loading = true;
    try {
      const response = await fetch('/api/government/import/citizens', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ citizens: importData })
      });
      
      if (response.ok) {
        importResult = await response.json();
        currentStep = 'import';
      } else {
        alert('Import thất bại');
      }
    } catch (e) {
      alert('Lỗi kết nối: ' + (e as Error).message);
    } finally {
      loading = false;
    }
  }
  
  function reset() {
    file = null;
    importData = [];
    validationErrors = [];
    importResult = null;
    currentStep = 'upload';
  }
  
  function fixErrors() {
    currentStep = 'upload';
  }
</script>

<div class="bulk-import">
  <header class="import-header">
    <h2>📥 Import công dân hàng loạt</h2>
    <button class="btn-template" on:click={downloadTemplate}>
      📄 Tải file mẫu
    </button>
  </header>
  
  {#if currentStep === 'upload'}
    <div class="upload-section">
      <div 
        class="drop-zone {dragOver ? 'drag-over' : ''}"
        on:dragover={handleDragOver}
        on:dragleave={handleDragLeave}
        on:drop={handleDrop}
      >
        <div class="drop-content">
          <div class="icon">📁</div>
          <p>Kéo thả file CSV vào đây</p>
          <p class="or">hoặc</p>
          <label class="btn-select">
            <input type="file" accept=".csv" on:change={handleFileSelect} hidden />
            Chọn file
          </label>
        </div>
      </div>
      
      <div class="format-info">
        <h4>📋 Định dạng file:</h4>
        <ul>
          <li>File CSV với header: fullName, identityNumber, province, district, ward, street...</li>
          <li>Mã định danh: 9-12 chữ số</li>
          <li>Ngày sinh: YYYY-MM-DD</li>
          <li>Tối đa 1000 bản ghi mỗi lần import</li>
        </ul>
      </div>
    </div>
  {:else if currentStep === 'validate'}
    <div class="validation-section" transition:slide>
      <div class="validation-header">
        <h3>⚠️ Phát hiện lỗi</h3>
        <p>Có {validationErrors.length} lỗi cần sửa trong file của bạn</p>
      </div>
      
      <div class="errors-list">
        {#each validationErrors as error}
          <div class="error-item">
            <span class="error-row">Dòng {error.row}</span>
            <span class="error-msg">{error.error}</span>
          </div>
        {/each}
      </div>
      
      <div class="actions">
        <button class="btn-secondary" on:click={reset}>Chọn file khác</button>
        <button class="btn-primary" on:click={fixErrors}>Sửa lỗi</button>
      </div>
    </div>
  {:else if currentStep === 'preview'}
    <div class="preview-section" transition:slide>
      <div class="preview-header">
        <h3>👁️ Xem trước dữ liệu</h3>
        <p>Sẵn sàng import {importData.length} công dân</p>
      </div>
      
      <div class="preview-table-wrapper">
        <table class="preview-table">
          <thead>
            <tr>
              <th>Họ tên</th>
              <th>CCCD/CMND</th>
              <th>Địa chỉ</th>
              <th>Điện thoại</th>
            </tr>
          </thead>
          <tbody>
            {#each importData.slice(0, 10) as row}
              <tr>
                <td>{row.fullName}</td>
                <td>{row.identityNumber}</td>
                <td>{row.street}, {row.ward}, {row.district}</td>
                <td>{row.phone || '-'}</td>
              </tr>
            {/each}
          </tbody>
        </table>
        {#if importData.length > 10}
          <p class="more-rows">... và {importData.length - 10} dòng khác</p>
        {/if}
      </div>
      
      <div class="actions">
        <button class="btn-secondary" on:click={reset}>Quay lại</button>
        <button class="btn-primary" on:click={importCitizens} disabled={loading}>
          {loading ? '⏳ Đang import...' : '✅ Xác nhận import'}
        </button>
      </div>
    </div>
  {:else if currentStep === 'import' && importResult}
    <div class="result-section" transition:fade>
      <div class="result-header">
        <h3>✅ Hoàn thành import</h3>
      </div>
      
      <div class="result-stats">
        <div class="stat success">
          <div class="stat-number">{importResult.successCount}</div>
          <div class="stat-label">Thành công</div>
        </div>
        <div class="stat failed">
          <div class="stat-number">{importResult.failedCount}</div>
          <div class="stat-label">Thất bại</div>
        </div>
        <div class="stat total">
          <div class="stat-number">{importResult.totalCount}</div>
          <div class="stat-label">Tổng số</div>
        </div>
      </div>
      
      {#if importResult.errors && importResult.errors.length > 0}
        <div class="import-errors">
          <h4>⚠️ Chi tiết lỗi:</h4>
          {#each importResult.errors as error}
            <div class="error-item">
              <span>Dòng {error.row}:</span>
              <span>{error.error}</span>
            </div>
          {/each}
        </div>
      {/if}
      
      <div class="actions">
        <button class="btn-primary" on:click={reset}>Import tiếp</button>
      </div>
    </div>
  {/if}
</div>

<style>
  .bulk-import {
    padding: 24px;
    background: white;
    border-radius: 12px;
  }
  
  .import-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }
  
  .import-header h2 {
    margin: 0;
    color: #1e293b;
  }
  
  .btn-template {
    padding: 8px 16px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-template:hover {
    background: #e2e8f0;
  }
  
  .drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 48px;
    text-align: center;
    transition: all 0.2s;
    background: #f8fafc;
  }
  
  .drop-zone.drag-over {
    border-color: #3b82f6;
    background: #eff6ff;
  }
  
  .drop-content .icon {
    font-size: 48px;
    margin-bottom: 16px;
  }
  
  .drop-content p {
    color: #64748b;
    margin: 8px 0;
  }
  
  .drop-content .or {
    font-size: 14px;
  }
  
  .btn-select {
    display: inline-block;
    padding: 10px 24px;
    background: #3b82f6;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 8px;
    transition: background 0.2s;
  }
  
  .btn-select:hover {
    background: #2563eb;
  }
  
  .format-info {
    margin-top: 24px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
  }
  
  .format-info h4 {
    margin: 0 0 12px 0;
    color: #475569;
  }
  
  .format-info ul {
    margin: 0;
    padding-left: 20px;
    color: #64748b;
  }
  
  .format-info li {
    margin: 4px 0;
  }
  
  .validation-section,
  .preview-section,
  .result-section {
    animation: fadeIn 0.3s ease;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .validation-header,
  .preview-header,
  .result-header {
    margin-bottom: 20px;
  }
  
  .validation-header h3 {
    color: #ef4444;
    margin: 0 0 8px 0;
  }
  
  .errors-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #fee2e2;
    border-radius: 8px;
    padding: 16px;
    background: #fef2f2;
  }
  
  .error-item {
    display: flex;
    gap: 16px;
    padding: 8px 0;
    border-bottom: 1px solid #fecaca;
  }
  
  .error-item:last-child {
    border-bottom: none;
  }
  
  .error-row {
    font-weight: 500;
    color: #dc2626;
    min-width: 80px;
  }
  
  .error-msg {
    color: #7f1d1d;
  }
  
  .preview-table-wrapper {
    overflow-x: auto;
    margin-bottom: 20px;
  }
  
  .preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
  }
  
  .preview-table th,
  .preview-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .preview-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #475569;
  }
  
  .more-rows {
    text-align: center;
    color: #64748b;
    padding: 12px;
    font-style: italic;
  }
  
  .result-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
  }
  
  .stat {
    text-align: center;
    padding: 20px;
    border-radius: 12px;
  }
  
  .stat.success {
    background: #d1fae5;
    color: #065f46;
  }
  
  .stat.failed {
    background: #fee2e2;
    color: #991b1b;
  }
  
  .stat.total {
    background: #dbeafe;
    color: #1e40af;
  }
  
  .stat-number {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 4px;
  }
  
  .stat-label {
    font-size: 14px;
    opacity: 0.8;
  }
  
  .actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
  }
  
  .btn-primary,
  .btn-secondary {
    padding: 10px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    border: none;
  }
  
  .btn-primary {
    background: #3b82f6;
    color: white;
  }
  
  .btn-primary:hover:not(:disabled) {
    background: #2563eb;
  }
  
  .btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
  }
  
  .btn-secondary:hover {
    background: #e2e8f0;
  }
  
  .import-errors {
    margin-top: 20px;
    padding: 16px;
    background: #fef2f2;
    border-radius: 8px;
    border: 1px solid #fee2e2;
  }
  
  .import-errors h4 {
    color: #dc2626;
    margin: 0 0 12px 0;
  }
</style>
