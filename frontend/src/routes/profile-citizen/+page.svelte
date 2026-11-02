<script lang="ts">
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';

  let user: any = {
    name: 'Nguyễn Văn A',
    citizenId: '012345678901',
    birthDate: '1990-05-15',
    gender: 'Nam',
    address: '123 Đường Láng, Phường ABC, Quận XYZ, TP Hà Nội',
    phone: '0912 345 678',
    email: 'nguyenvana@email.com',
    householdId: 'HH001'
  };

  let isEditing = false;
  let editForm = { ...user };

  onMount(() => {
    const stored = localStorage.getItem('citizenUser');
    if (stored) {
      user = JSON.parse(stored);
      editForm = { ...user };
    }
  });

  function saveProfile() {
    user = { ...editForm };
    localStorage.setItem('citizenUser', JSON.stringify(user));
    isEditing = false;
    alert('Profile updated successfully!');
  }

  const documents = [
    { type: 'CCCD', number: '012345678901', issuedDate: '2020-01-15', expiryDate: '2030-01-15', status: 'Valid' },
    { type: 'Sổ hộ khẩu', number: 'HH001', issuedDate: '2015-06-20', status: 'Valid' },
    { type: 'BHYT', number: 'BH123456', issuedDate: '2024-01-01', expiryDate: '2024-12-31', status: 'Valid' }
  ];

  const payments = [
    { id: '1', type: 'Phí dịch vụ', amount: 50000, date: '2026-02-10', status: 'Paid' },
    { id: '2', type: 'Thuế nhà đất', amount: 1500000, date: '2026-02-05', status: 'Paid' },
    { id: '3', type: 'Phí nước', amount: 120000, date: '2026-02-01', status: 'Pending' }
  ];

  function logout() {
    localStorage.removeItem('citizenUser');
    goto('/auth');
  }
</script>

<svelte:head>
  <title>Citizen Profile - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => goto('/dashboard')}>
        ← Back
      </button>
      <div>
        <h1>👤 My Profile</h1>
        <p class="subtitle">Thông tin công dân</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="profile-section">
      <div class="profile-header">
        <div class="avatar">
          {user.name.split(' ').map((n: string) => n[0]).join('').slice(0,2)}
        </div>
        <div class="profile-info">
          <h2>{user.name}</h2>
          <p class="citizen-id">CCCD: {user.citizenId}</p>
        </div>
        <button class="edit-btn" on:click={() => isEditing = !isEditing}>
          {isEditing ? 'Cancel' : '✏️ Edit'}
        </button>
      </div>

      {#if isEditing}
        <form class="edit-form" on:submit|preventDefault={saveProfile}>
          <div class="form-row">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" bind:value={editForm.name} />
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="tel" bind:value={editForm.phone} />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email</label>
              <input type="email" bind:value={editForm.email} />
            </div>
            <div class="form-group">
              <label>Address</label>
              <input type="text" bind:value={editForm.address} />
            </div>
          </div>
          <button type="submit" class="save-btn">Save Changes</button>
        </form>
      {:else}
        <div class="profile-details">
          <div class="detail-row">
            <span class="detail-label">Date of Birth</span>
            <span class="detail-value">{user.birthDate}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Gender</span>
            <span class="detail-value">{user.gender}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Phone</span>
            <span class="detail-value">{user.phone}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value">{user.email}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Address</span>
            <span class="detail-value">{user.address}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Household ID</span>
            <span class="detail-value">{user.householdId}</span>
          </div>
        </div>
      {/if}
    </section>

    <section class="documents-section">
      <h2>📄 My Documents</h2>
      <div class="documents-list">
        {#each documents as doc}
          <div class="document-card">
            <div class="doc-icon">📄</div>
            <div class="doc-info">
              <h3>{doc.type}</h3>
              <p>Number: {doc.number}</p>
              {#if doc.issuedDate}
                <p>Issued: {doc.issuedDate}</p>
              {/if}
            </div>
            <span class="doc-status valid">{doc.status}</span>
          </div>
        {/each}
      </div>
    </section>

    <section class="payments-section">
      <h2>💳 Payment History</h2>
      <div class="payments-list">
        {#each payments as payment}
          <div class="payment-card">
            <div class="payment-info">
              <h3>{payment.type}</h3>
              <p>{payment.date}</p>
            </div>
            <div class="payment-right">
              <span class="amount">{payment.amount.toLocaleString()} VND</span>
              <span class="payment-status {payment.status.toLowerCase()}">{payment.status}</span>
            </div>
          </div>
        {/each}
      </div>
      <button class="btn-primary">View All Payments</button>
    </section>

    <section class="settings-section">
      <h2>⚙️ Account Settings</h2>
      <div class="settings-list">
        <button class="setting-item">
          <span>🔔</span> Notification Settings
        </button>
        <button class="setting-item">
          <span>🔐</span> Change Password
        </button>
        <button class="setting-item">
          <span>🔒</span> Privacy & Security
        </button>
        <button class="setting-item">
          <span>❓</span> Help & Support
        </button>
        <button class="setting-item logout" on:click={logout}>
          <span>🚪</span> Logout
        </button>
      </div>
    </section>
  </div>
</div>

<style>
  .page-container { min-height: 100vh; background: #f8fafc; }
  .page-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 2rem; }
  .header-content { max-width: 800px; margin: 0 auto; display: flex; align-items: center; gap: 1rem; }
  .back-btn { background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }
  h1 { margin: 0; font-size: 1.75rem; }
  .subtitle { margin: 0.5rem 0 0; opacity: 0.9; }
  .content { max-width: 800px; margin: 0 auto; padding: 2rem; }
  section { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  h2 { margin: 0 0 1rem; color: #1e293b; font-size: 1.125rem; }
  
  .profile-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
  .avatar { width: 80px; height: 80px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 700; }
  .profile-info { flex: 1; }
  .profile-info h2 { margin: 0; color: #1e293b; }
  .citizen-id { margin: 0.25rem 0 0; color: #64748b; font-size: 0.875rem; }
  .edit-btn { background: #f1f5f9; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }
  
  .profile-details { display: flex; flex-direction: column; gap: 0.75rem; }
  .detail-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; }
  .detail-label { color: #64748b; }
  .detail-value { font-weight: 600; color: #1e293b; }
  
  .edit-form { display: flex; flex-direction: column; gap: 1rem; }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
  .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
  .form-group label { font-weight: 600; font-size: 0.875rem; color: #1e293b; }
  .form-group input { padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
  .save-btn { background: #3b82f6; color: white; border: none; padding: 0.75rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
  
  .documents-list { display: flex; flex-direction: column; gap: 0.75rem; }
  .document-card { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px; }
  .doc-icon { font-size: 2rem; }
  .doc-info { flex: 1; }
  .doc-info h3 { margin: 0; color: #1e293b; }
  .doc-info p { margin: 0.25rem 0 0; font-size: 0.875rem; color: #64748b; }
  .doc-status { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
  .doc-status.valid { background: #dcfce7; color: #166534; }
  
  .payments-list { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem; }
  .payment-card { display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8fafc; border-radius: 8px; }
  .payment-info h3 { margin: 0; color: #1e293b; }
  .payment-info p { margin: 0.25rem 0 0; font-size: 0.875rem; color: #64748b; }
  .payment-right { text-align: right; }
  .amount { display: block; font-weight: 700; color: #1e293b; }
  .payment-status { font-size: 0.75rem; }
  .payment-status.paid { color: #10b981; }
  .payment-status.pending { color: #f59e0b; }
  .btn-primary { width: 100%; background: #3b82f6; color: white; border: none; padding: 0.75rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
  
  .settings-list { display: flex; flex-direction: column; gap: 0.5rem; }
  .setting-item { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: none; border: none; border-bottom: 1px solid #e2e8f0; cursor: pointer; text-align: left; font-size: 1rem; color: #1e293b; }
  .setting-item:hover { background: #f8fafc; }
  .setting-item.logout { color: #dc2626; border-bottom: none; }
</style>
