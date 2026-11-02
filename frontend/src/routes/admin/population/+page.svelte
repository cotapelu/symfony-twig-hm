<script lang="ts">
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';

  let user: any = null;
  let searchQuery = '';
  let showModal = false;
  let editingRecord: any = null;

  let formData = {
    name: '',
    birthDate: '',
    gender: 'Nam',
    address: '',
    householdId: '',
    status: 'Thường trú',
    type: 'Công dân'
  };

  let records = [
    { id: '1', name: 'Nguyễn Văn A', birthDate: '1990-05-15', gender: 'Nam', address: '123 Đường Láng', householdId: 'HH001', status: 'Thường trú', type: 'Công dân' },
    { id: '2', name: 'Trần Thị B', birthDate: '1985-08-20', gender: 'Nữ', address: '456 Nguyễn Trãi', householdId: 'HH002', status: 'Thường trú', type: 'Công dân' },
    { id: '3', name: 'Lê Văn C', birthDate: '2010-03-10', gender: 'Nam', address: '789 Lý Thường Kiệt', householdId: 'HH003', status: 'Tạm trú', type: 'Trẻ em' },
    { id: '4', name: 'Phạm Thị D', birthDate: '1978-11-25', gender: 'Nữ', address: '321 Hoàng Quốc Việt', householdId: 'HH001', status: 'Thường trú', type: 'Công dân' },
    { id: '5', name: 'Hoàng Văn E', birthDate: '1955-07-08', gender: 'Nam', address: '654 Cầu Giấy', householdId: 'HH004', status: 'Thường trú', type: 'Người cao tuổi' }
  ];

  let idCounter = 6;

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

  $: filteredRecords = records.filter(r => 
    r.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    r.address.toLowerCase().includes(searchQuery.toLowerCase())
  );

  function openAddModal() {
    editingRecord = null;
    formData = { name: '', birthDate: '', gender: 'Nam', address: '', householdId: '', status: 'Thường trú', type: 'Công dân' };
    showModal = true;
  }

  function openEditModal(record: any) {
    editingRecord = record;
    formData = { ...record };
    showModal = true;
  }

  function saveRecord() {
    if (editingRecord) {
      records = records.map(r => r.id === editingRecord.id ? { ...formData, id: editingRecord.id } : r);
    } else {
      records = [...records, { ...formData, id: (idCounter++).toString() }];
    }
    showModal = false;
  }

  function deleteRecord(id: string) {
    if (confirm('Are you sure you want to delete this record?')) {
      records = records.filter(r => r.id !== id);
    }
  }
</script>

<svelte:head>
  <title>Population Management - Admin</title>
</svelte:head>

<div class="admin-layout">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="logo">🏛️</div>
      <h2>Admin Panel</h2>
    </div>
    <nav class="sidebar-nav">
      <a href="/admin/dashboard" class="nav-item">📊 Dashboard</a>
      <a href="/admin/population" class="nav-item active">👥 Population</a>
      <a href="/admin/household" class="nav-item">🏠 Household</a>
      <a href="/admin/documents" class="nav-item">📄 Documents</a>
      <a href="/admin/ttth" class="nav-item">✅ TTHC</a>
      <a href="/admin/complaints" class="nav-item">📝 Complaints</a>
      <a href="/admin/finance" class="nav-item">💰 Finance</a>
    </nav>
    <div class="sidebar-footer">
      <button class="logout-btn" on:click={logout}>Logout</button>
    </div>
  </aside>

  <main class="main-content">
    <header class="content-header">
      <h1>Population Management</h1>
      <button class="btn-primary" on:click={openAddModal}>+ Add New</button>
    </header>

    <div class="content-body">
      <div class="toolbar">
        <input type="text" placeholder="Search by name or address..." bind:value={searchQuery} class="search-input" />
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Birth Date</th>
              <th>Gender</th>
              <th>Address</th>
              <th>Household</th>
              <th>Status</th>
              <th>Type</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each filteredRecords as record}
              <tr>
                <td>{record.id}</td>
                <td>{record.name}</td>
                <td>{record.birthDate}</td>
                <td>{record.gender}</td>
                <td>{record.address}</td>
                <td>{record.householdId}</td>
                <td><span class="badge {record.status === 'Thường trú' ? 'success' : 'warning'}">{record.status}</span></td>
                <td>{record.type}</td>
                <td>
                  <button class="btn-icon" on:click={() => openEditModal(record)}>✏️</button>
                  <button class="btn-icon delete" on:click={() => deleteRecord(record.id)}>🗑️</button>
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
      <h2>{editingRecord ? 'Edit' : 'Add'} Population Record</h2>
      <form on:submit|preventDefault={saveRecord}>
        <div class="form-group">
          <label>Name</label>
          <input type="text" bind:value={formData.name} required />
        </div>
        <div class="form-group">
          <label>Birth Date</label>
          <input type="date" bind:value={formData.birthDate} required />
        </div>
        <div class="form-group">
          <label>Gender</label>
          <select bind:value={formData.gender}>
            <option>Nam</option>
            <option>Nữ</option>
          </select>
        </div>
        <div class="form-group">
          <label>Address</label>
          <input type="text" bind:value={formData.address} required />
        </div>
        <div class="form-group">
          <label>Household ID</label>
          <input type="text" bind:value={formData.householdId} required />
        </div>
        <div class="form-group">
          <label>Status</label>
          <select bind:value={formData.status}>
            <option>Thường trú</option>
            <option>Tạm trú</option>
          </select>
        </div>
        <div class="form-group">
          <label>Type</label>
          <select bind:value={formData.type}>
            <option>Công dân</option>
            <option>Trẻ em</option>
            <option>Người cao tuổi</option>
          </select>
        </div>
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
  .badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
  .badge.success { background: #dcfce7; color: #166534; }
  .badge.warning { background: #fef3c7; color: #92400e; }
  .btn-icon { background: none; border: none; cursor: pointer; font-size: 1rem; padding: 0.25rem; }
  .btn-icon.delete { margin-left: 0.5rem; }
  .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
  .modal { background: white; padding: 2rem; border-radius: 12px; width: 100%; max-width: 500px; }
  .modal h2 { margin: 0 0 1.5rem; }
  .form-group { margin-bottom: 1rem; }
  .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
  .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
  .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
  .btn-secondary { background: #f1f5f9; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; }
</style>
