<script lang="ts">
  import { userStore } from '$lib/stores';
  import { goto } from '$app/navigation';
  import { browser } from '$app/environment';

  interface LoginData {
    email: string;
    password: string;
    rememberMe: boolean;
  }

  interface RegisterData {
    email: string;
    password: string;
    confirmPassword: string;
    name: string;
  }

  let loginData: LoginData = {
    email: '',
    password: '',
    rememberMe: false
  };

  let registerData: RegisterData = {
    email: '',
    password: '',
    confirmPassword: '',
    name: ''
  };

  let loading = false;
  let error = '';
  let showRegister = false;

  async function handleLogin(event: Event) {
    event.preventDefault();
    loading = true;
    error = '';
    
    try {
      await userStore.login(loginData.email, loginData.password);
      if (browser) {
        goto('/');
      }
    } catch (err) {
      error = (err as Error).message || 'Login failed';
    } finally {
      loading = false;
    }
  }

  async function handleRegister(event: Event) {
    event.preventDefault();
    if (registerData.password !== registerData.confirmPassword) {
      error = 'Passwords do not match';
      return;
    }

    loading = true;
    error = '';

    try {
      await userStore.register(
        registerData.name,
        registerData.email,
        registerData.password
      );
      
      if (browser) {
        goto('/');
      }
    } catch (err) {
      error = (err as Error).message || 'Registration failed';
    } finally {
      loading = false;
    }
  }

  async function handleForgotPassword() {
    if (!loginData.email) {
      error = 'Please enter your email address';
      return;
    }

    loading = true;
    error = '';

    try {
      const result = await userStore.forgotPassword(loginData.email);
      if (result.success) {
        alert('Password reset email sent!');
      } else {
        error = result.message || 'Failed to send reset email';
      }
    } catch (err) {
      error = (err as Error).message || 'Failed to send reset email';
    } finally {
      loading = false;
    }
  }
</script>

<div class="auth-page">
  <div class="auth-container">
    <div class="auth-card">
      <h1 class="auth-title">
        {showRegister ? 'Create Account' : 'Welcome Back'}
      </h1>
      <p class="auth-subtitle">
        {showRegister 
          ? 'Join us to start your journey' 
          : 'Sign in to continue to your account'}
      </p>

      {#if error}
        <div class="error-message">
          <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {error}
        </div>
      {/if}

      {#if !showRegister}
        <form onsubmit={handleLogin} class="auth-form">
          <div class="form-group">
            <label for="login-email">Email Address</label>
            <input
              id="login-email"
              type="email"
              bind:value={loginData.email}
              placeholder="you@example.com"
              required
              disabled={loading}
            />
          </div>

          <div class="form-group">
            <label for="login-password">Password</label>
            <input
              id="login-password"
              type="password"
              bind:value={loginData.password}
              placeholder="Enter your password"
              required
              disabled={loading}
            />
          </div>

          <div class="form-options">
            <label class="checkbox-label">
              <input type="checkbox" bind:checked={loginData.rememberMe} />
              <span>Remember me</span>
            </label>
            <button 
              type="button" 
              class="forgot-password"
              onclick={handleForgotPassword}
              disabled={loading}
            >
              Forgot password?
            </button>
          </div>

          <button type="submit" class="auth-button" disabled={loading}>
            {#if loading}
              <span class="spinner"></span>
              Signing in...
            {:else}
              Sign In
            {/if}
          </button>
        </form>
      {:else}
        <form onsubmit={handleRegister} class="auth-form">
          <div class="form-group">
            <label for="register-name">Full Name</label>
            <input
              id="register-name"
              type="text"
              bind:value={registerData.name}
              placeholder="Enter your full name"
              required
              disabled={loading}
            />
          </div>

          <div class="form-group">
            <label for="register-email">Email Address</label>
            <input
              id="register-email"
              type="email"
              bind:value={registerData.email}
              placeholder="you@example.com"
              required
              disabled={loading}
            />
          </div>

          <div class="form-group">
            <label for="register-password">Password</label>
            <input
              id="register-password"
              type="password"
              bind:value={registerData.password}
              placeholder="Create a password"
              required
              disabled={loading}
            />
          </div>

          <div class="form-group">
            <label for="register-confirm">Confirm Password</label>
            <input
              id="register-confirm"
              type="password"
              bind:value={registerData.confirmPassword}
              placeholder="Confirm your password"
              required
              disabled={loading}
            />
          </div>

          <button type="submit" class="auth-button" disabled={loading}>
            {#if loading}
              <span class="spinner"></span>
              Creating account...
            {:else}
              Create Account
            {/if}
          </button>
        </form>
      {/if}

      <div class="auth-divider">
        <span>OR</span>
      </div>

      <div class="social-login">
        <button class="social-button google">
          <svg class="social-icon" viewBox="0 0 24 24">
            <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          Continue with Google
        </button>

        <button class="social-button github">
          <svg class="social-icon" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
          </svg>
          Continue with GitHub
        </button>
      </div>

      <div class="auth-footer">
        <p>
          {showRegister 
            ? 'Already have an account? '
            : 'Don\'t have an account? '}
          <button 
            class="toggle-auth"
            onclick={() => { showRegister = !showRegister; error = ''; }}
          >
            {showRegister ? 'Sign in' : 'Sign up'}
          </button>
        </p>
      </div>
    </div>
  </div>
</div>

<style>
  .auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1rem;
  }

  .auth-container {
    width: 100%;
    max-width: 420px;
  }

  .auth-card {
    background: white;
    border-radius: 12px;
    padding: 2.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  }

  .auth-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
    text-align: center;
  }

  .auth-subtitle {
    color: #6b7280;
    text-align: center;
    margin: 0 0 2rem 0;
  }

  .error-message {
    background: #fee2e2;
    border: 1px solid #f87171;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 1.5rem;
    color: #991b1b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
  }

  .error-icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
  }

  .auth-form {
    margin-bottom: 1.5rem;
  }

  .form-group {
    margin-bottom: 1.25rem;
  }

  .form-group label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
  }

  .form-group input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
  }

  .form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .form-group input:disabled {
    background: #f3f4f6;
    cursor: not-allowed;
  }

  .form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
  }

  .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: #374151;
  }

  .checkbox-label input {
    width: 1rem;
    height: 1rem;
    cursor: pointer;
  }

  .forgot-password {
    background: none;
    border: none;
    color: #667eea;
    cursor: pointer;
    font-weight: 500;
    padding: 0;
    font-size: 0.875rem;
  }

  .forgot-password:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .auth-button {
    width: 100%;
    padding: 0.875rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .auth-button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .auth-button:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .auth-divider {
    display: flex;
    align-items: center;
    margin: 1.5rem 0;
    color: #9ca3af;
    font-size: 0.875rem;
  }

  .auth-divider::before,
  .auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
  }

  .auth-divider span {
    padding: 0 1rem;
  }

  .social-login {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
  }

  .social-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s, border-color 0.2s;
  }

  .social-button:hover {
    background: #f9fafb;
    border-color: #9ca3af;
  }

  .social-icon {
    width: 1.25rem;
    height: 1.25rem;
  }

  .social-button.google:hover {
    background: #fef2f2;
  }

  .social-button.github:hover {
    background: #f8fafc;
  }

  .auth-footer {
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
  }

  .toggle-auth {
    background: none;
    border: none;
    color: #667eea;
    cursor: pointer;
    font-weight: 600;
    padding: 0;
  }

  .toggle-auth:hover {
    text-decoration: underline;
  }

  @media (max-width: 640px) {
    .auth-card {
      padding: 1.5rem;
    }

    .auth-title {
      font-size: 1.5rem;
    }
  }
</style>
