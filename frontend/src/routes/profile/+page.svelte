<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { userStore } from '$lib/stores';

  let loading = true;
  let saving = false;
  let message = '';
  
  let profile = {
    name: '',
    email: '',
    phone: '',
    address: ''
  };

  onMount(async () => {
    if (!$userStore) {
      goto('/auth');
      return;
    }

    profile.name = $userStore.user?.name || '';
    profile.email = $userStore.user?.email || '';
    loading = false;
  });

  async function saveProfile() {
    saving = true;
    message = '';
    
    try {
      await new Promise(resolve => setTimeout(resolve, 1000));
      message = 'Profile updated successfully!';
    } catch (err) {
      message = 'Failed to update profile';
    } finally {
      saving = false;
    }
  }

  function logout() {
    userStore.logout();
    goto('/auth');
  }
</script>

<svelte:head>
  <title>Profile - Travel Booking App</title>
</svelte:head>

<div class="profile-page">
  <header class="header">
    <div class="header-content">
      <a href="/dashboard" class="back-link">← Back to Dashboard</a>
      <h1>My Profile</h1>
    </div>
  </header>

  <main class="main-content">
    {#if loading}
      <div class="loading">Loading...</div>
    {:else}
      <div class="profile-card">
        <div class="profile-header">
          <div class="avatar">
            <span>{$userStore?.user?.name?.charAt(0) || 'U'}</span>
          </div>
          <h2>{$userStore?.user?.name || 'User'}</h2>
          <p class="email">{$userStore?.user?.email}</p>
        </div>

        {#if message}
          <div class="message" class:success={message.includes('success')}>
            {message}
          </div>
        {/if}

        <form on:submit|preventDefault={saveProfile} class="profile-form">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input
              id="name"
              type="text"
              bind:value={profile.name}
              placeholder="Your full name"
            />
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input
              id="email"
              type="email"
              bind:value={profile.email}
              placeholder="Your email"
              disabled
            />
          </div>

          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input
              id="phone"
              type="tel"
              bind:value={profile.phone}
              placeholder="Your phone number"
            />
          </div>

          <div class="form-group">
            <label for="address">Address</label>
            <textarea
              id="address"
              bind:value={profile.address}
              placeholder="Your address"
              rows="3"
            ></textarea>
          </div>

          <button type="submit" class="save-btn" disabled={saving}>
            {saving ? 'Saving...' : 'Save Changes'}
          </button>
        </form>

        <div class="danger-zone">
          <h3>Danger Zone</h3>
          <button class="logout-btn" on:click={logout}>
            Logout
          </button>
        </div>
      </div>
    {/if}
  </main>
</div>

<style>
  .profile-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  .header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 1.5rem 2rem;
  }

  .header-content {
    max-width: 800px;
    margin: 0 auto;
  }

  .back-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.875rem;
    display: inline-block;
    margin-bottom: 0.5rem;
  }

  .back-link:hover {
    text-decoration: underline;
  }

  .header h1 {
    margin: 0;
    font-size: 1.75rem;
    color: #1e293b;
  }

  .main-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
  }

  .loading {
    text-align: center;
    padding: 4rem;
    color: #64748b;
  }

  .profile-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
  }

  .avatar {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
    font-weight: 600;
  }

  .profile-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
  }

  .email {
    margin: 0;
    opacity: 0.9;
  }

  .message {
    padding: 1rem;
    margin: 1rem;
    border-radius: 8px;
    text-align: center;
  }

  .message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
  }

  .profile-form {
    padding: 2rem;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .form-group input,
  .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
    font-family: inherit;
  }

  .form-group input:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .form-group input:disabled {
    background: #f3f4f6;
    cursor: not-allowed;
  }

  .save-btn {
    width: 100%;
    padding: 0.875rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .save-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .save-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .danger-zone {
    padding: 2rem;
    border-top: 1px solid #e2e8f0;
    background: #fef2f2;
  }

  .danger-zone h3 {
    margin: 0 0 1rem 0;
    color: #991b1b;
    font-size: 1rem;
  }

  .logout-btn {
    padding: 0.75rem 1.5rem;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
  }

  .logout-btn:hover {
    background: #dc2626;
  }

  @media (max-width: 640px) {
    .main-content {
      padding: 1rem;
    }

    .profile-form,
    .danger-zone {
      padding: 1.5rem;
    }
  }
</style>