<script lang="ts">
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';

  let username = '';
  let password = '';
  let error = '';
  let loading = false;

  const adminUsers = [
    { username: 'admin', password: 'admin123', role: 'admin' },
    { username: 'officer', password: 'officer123', role: 'officer' }
  ];

  function handleLogin() {
    loading = true;
    error = '';

    setTimeout(() => {
      const user = adminUsers.find(u => u.username === username && u.password === password);
      
      if (user) {
        localStorage.setItem('adminUser', JSON.stringify(user));
        goto('/admin/dashboard');
      } else {
        error = 'Invalid username or password';
      }
      loading = false;
    }, 1000);
  }

  onMount(() => {
    const stored = localStorage.getItem('adminUser');
    if (stored) {
      goto('/admin/dashboard');
    }
  });
</script>

<svelte:head>
  <title>Admin Login - Phường/Xã Super App</title>
</svelte:head>

<div class="login-page">
  <div class="login-container">
    <div class="login-header">
      <div class="logo">🏛️</div>
      <h1>Admin Dashboard</h1>
      <p>Phường/Xã Super App</p>
    </div>

    <form on:submit|preventDefault={handleLogin} class="login-form">
      {#if error}
        <div class="error-message">{error}</div>
      {/if}

      <div class="form-group">
        <label for="username">Username</label>
        <input 
          type="text" 
          id="username"
          bind:value={username}
          placeholder="Enter username"
          required
        />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input 
          type="password" 
          id="password"
          bind:value={password}
          placeholder="Enter password"
          required
        />
      </div>

      <button type="submit" class="login-btn" disabled={loading}>
        {#if loading}
          <span class="spinner"></span> Logging in...
        {:else}
          Login
        {/if}
      </button>

      <div class="demo-credentials">
        <p><strong>Demo Credentials:</strong></p>
        <p>Admin: admin / admin123</p>
        <p>Officer: officer / officer123</p>
      </div>
    </form>
  </div>
</div>

<style>
  .login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
  }

  .login-container {
    background: white;
    padding: 2.5rem;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 400px;
  }

  .login-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .logo {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .login-header h1 {
    margin: 0;
    color: #1e293b;
    font-size: 1.75rem;
  }

  .login-header p {
    margin: 0.5rem 0 0;
    color: #64748b;
  }

  .login-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .error-message {
    background: #fef2f2;
    color: #dc2626;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    text-align: center;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .form-group label {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.875rem;
  }

  .form-group input {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
  }

  .form-group input:focus {
    outline: none;
    border-color: #3b82f6;
  }

  .login-btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .login-btn:hover:not(:disabled) {
    background: #2563eb;
  }

  .login-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .demo-credentials {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    font-size: 0.75rem;
    color: #64748b;
  }

  .demo-credentials p {
    margin: 0.25rem 0;
  }
</style>
