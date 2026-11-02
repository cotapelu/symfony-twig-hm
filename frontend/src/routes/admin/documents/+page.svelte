<script lang="ts">
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';

  let user: any = null;
  let searchQuery = '';
  let showModal = false;
  let editingDoc: any = null;

  let formData = {
    title: '',
    type: '',
    applicant: '',
    status: 'Chờ duyệt',
    submittedDate: ''
  };

  let documents = [
    { id: '1', title: 'Đăng ký hộ khẩu', type: 'Hộ khẩu', applicant: 'Nguyễn Văn A', status: 'Đã duyệt', submittedDate: '2026-02-10' },
    { id: '2', title: 'Xin cấp GCN đất đai', type: 'Đất đai', applicant: 'Trần Thị B', status: 'Đang xử lý', submittedDate: '2026-02-12' },
    { id: '3', title: 'Xây dựng nhà ở', type: 'Xây dựng', applicant: 'Lê Văn C', status: 'Chờ duyệt', submittedDate: '2026-02-14' },
    { id: '4', title: 'Đăng ký kết hôn', type: 'Hộ tịch', applicant: 'Phạm Thị D', status: 'Đã duyệt', submittedDate: '2026-02-08' },
    { id: '5', title: 'Xin cấp CCCD', type: 'CCCD', applicant: 'Hoàng Văn E', status: 'Đang xử lý', submittedDate: '2026-02-13' }
  ];

  let idCounter = 6;

  onMount(() => {
    const stored = localStorage.getItem('adminUser');
    if (!stored) goto('/admin/login');
    else user = JSON.parse(stored);
  });

  function logout() {
    localStorage.removeItem('adminUser');
    goto('/admin/login');
  }

  $: filteredDocs = documents.filter(d => 
    d.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
    d.applicant.toLowerCase().includes(searchQuery.toLowerCase())
  );

  function openAddModal() {
    editingDoc = null;
    formData = { title: '', type: '', applicant: '', status: 'Chờ duyệt', submittedDate: new Date().toISOString().split('T')[0] };
    showModal = true;
  }

  function saveDoc() {
    if (editingDoc) {
      documents = documents.map(d => d.id === editingDoc.id ? { ...formData, id: editingDoc.id } : d);
    } else {
      documents = [...documents, { ...formData, id: (idCounter++).toString() }];
    }
    showModal = false;
  }

  function updateStatus(id: string, status: string) {
    documents = documents.map(d => d.id === id ? { ...d, status } : d);
  }

  function deleteDoc(id: string) {
    if (confirm('Delete this document?')) {
      documents = documents.filter(d => d.id !== id);
    }
  }
</script>

<svelte:head>
  <title>Documents Management - Admin</title>
</svelte:head>

<div class="admin-layout">
  <aside class="sidebar">
    <div class="sidebar-header"><div class="logo">🏛️</div><h2>Admin Panel</h2></div>
    <nav class="sidebar-nav">
      <a href="/admin/dashboard" class="nav-item">📊 Dashboard</a>
      <a href="/admin/population" class="nav-item">👥 Population</a>
      <a href="/admin/documents" class="nav-item active">📄 Documents</a>
      <a href="/admin/ttth" class="nav-item">✅ TTHC</a>
      <a href="/admin/complaints" class="nav-item">📝 Complaints</a>
    </nav>
    <div class="sidebar-footer"><button class="logout-btn" on:click={logout}>Logout</button></div>
  </aside>

  <main class="main-content">
    <header class="content-header">
      <h1>Documents Management</h1>
      <button class="btn-primary" on:click={openAddModal}>+ Add Document</button>
    </header>

    <div class="content-body">
      <div class="toolbar">
        <input type="text" placeholder="Search documents..." bind:value={searchQuery} class="search-input" />
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr><th>ID</th><th>Title</th><th>Type</th><th>Applicant</th><th>Submitted</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            {#each filteredDocs as doc}
              <tr>
                <td>{doc.id}</td>
                <td>{doc.title}</td>
                <td>{doc.type}</td>
                <td>{doc.applicant}</td>
                <td>{doc.submittedDate}</td>
                <td>
                  <select class="status-select {doc.status}" bind:value={doc.status} on:change={() => updateStatus(doc.id, doc.status)}>
                    <option>Chờ duyệt</option>
                    <option>Đang xử lý</option>
                    <option>Đã duyệt</option>
                    <option>Từ chối</option>
                  </select>
                </td>
                <td>
                  <button class="btn-icon delete" on:click={() => deleteDoc(doc.id)}>🗑️</button>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

{#if showModal}
  <div class="modal-overlay" on:click={() => showModal = false}>
    <div class="modal" on:click|stopPropagation>
      <h2>{editingDoc ? 'Edit' : 'Add'} Document</h2>
      <form on:submit|preventDefault={saveDoc}>
        <div class="form-group"><label>Title</label><input type="text" bind:value={formData.title} required /></div>
        <div class="form-group"><label>Type</label><input type="text" bind:value={formData.type} required /></div>
        <div class="form-group"><label>Applicant</label><input type="text" bind:value={formData.applicant} required /></div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" on:click={() => showModal = false}>Cancel</button>
          <button type="submit" class="btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .admin-layout { display: flex; min-height: 100vh; background: #f8fafc; }
  .sidebar { width: 220px; background: #1e293b; color: white; position: fixed; height: 100vh; }
  .sidebar-header { padding: 1.5rem; }
  .logo { font-size: 2rem; }
  .sidebar-nav { flex: 1; padding: 1rem 0; }
  .nav-item { display: block; padding: 0.75rem 1.5rem; color: rgba(255,255,255,0.7); text-decoration: none; }
  .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
  .nav-item.active { border-left: 3px solid #3b82f6; }
  .sidebar-footer { padding: 1rem; }
  .logout-btn { width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.1); border: none; color: white; border-radius: 6px; cursor: pointer; }
  .main-content { flex: 1; margin-left: 220px; }
  .content-header { background: white; padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
  .content-header h1 { margin: 0; }
  .btn-primary { background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
  .content-body { padding: 2rem; }
  .toolbar { margin-bottom: 1rem; }
  .search-input { width: 100%; max-width: 400px; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
  .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
  th { background: #f8fafc; font-weight: 600; }
  .status-select { padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 0.875rem; }
  .status-select.Đã_duyệt { background: #dcfce7; color: #166534; }
  .status-select.Đang_xử_lý { background: #dbeafe; color: #1d4ed8; }
  .status-select.Chờ_duyệt { background: #fef3c7; color: #92400e; }
  .btn-icon { background: none; border: none; cursor: pointer; font-size: 1rem; }
  .btn-icon.delete { margin-left: 0.5rem; }
  .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
  .modal { background: white; padding: 2rem; border-radius: 12px; width: 100%; max-width: 500px; }
  .modal h2 { margin: 0 0 1.5rem; }
  .form-group { margin-bottom: 1rem; }
  .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
  .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
  .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
  .btn-secondary { background: #f1f5f9; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; }
</style>
