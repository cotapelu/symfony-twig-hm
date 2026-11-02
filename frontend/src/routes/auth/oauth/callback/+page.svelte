<script lang="ts">
	import { onMount } from 'svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import { setUser } from '$lib/stores/global';

  let status = 'processing';
  let error = '';
  let user = null;

  onMount(async () => {
    try {
      // Get OAuth parameters from URL
      const urlParams = new URLSearchParams(window.location.search);
      const code = urlParams.get('code');
      const state = urlParams.get('state');
      const errorParam = urlParams.get('error');

      if (errorParam) {
        status = 'error';
        error = `OAuth error: ${errorParam}`;
        return;
      }

      if (!code) {
        status = 'error';
        error = 'Authorization code missing';
        return;
      }

      // Verify state for CSRF protection
      const storedState = sessionStorage.getItem('oauth_state');
      if (!state || state !== storedState) {
        status = 'error';
        error = 'Invalid state parameter';
        return;
      }

      // Exchange code for token
      const response = await fetch('/auth/oauth/callback', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          code,
          state
        })
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'OAuth callback failed');
      }

      const data = await response.json();

      // Set user data
      setUser(data.user);
      if (data.token) {
        localStorage.setItem('auth_token', data.token);
      }

      status = 'success';
      user = data.user;

      // Redirect after short delay
      setTimeout(() => {
        window.location.href = '/dashboard';
      }, 2000);

    } catch (err) {
      console.error('OAuth callback error:', err);
      status = 'error';
      error = err instanceof Error ? err.message : 'Authentication failed';
    }
  });
</script>

<main class="oauth-callback">
  <div class="callback-container">
    {#if status === 'processing'}
      <div class="processing">
        <div class="loading-spinner"></div>
        <Typography variant="h2">Completing Authentication</Typography>
        <Typography variant="body">Please wait while we finish setting up your account...</Typography>
      </div>
    {:else if status === 'success'}
      <div class="success">
        <div class="success-icon">✅</div>
        <Typography variant="h2">Welcome, {user?.name || user?.email}!</Typography>
        <Typography variant="body">Authentication successful. Redirecting you to the dashboard...</Typography>
        <div class="redirect-timer">
          <small>Redirecting in <span class="countdown">2</span> seconds...</small>
        </div>
      </div>
    {:else if status === 'error'}
      <div class="error">
        <div class="error-icon">❌</div>
        <Typography variant="h2">Authentication Failed</Typography>
        <Typography variant="body">{error}</Typography>
        <div class="error-actions">
          <Button onclick={() => window.location.href = '/auth'} variant="primary">
            Try Again
          </Button>
          <Button onclick={() => window.location.href = '/'} variant="secondary">
            Go Home
          </Button>
        </div>
      </div>
    {/if}
  </div>
</main>

<style>
  .oauth-callback {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }

  .callback-container {
    background: rgba(15, 15, 15, 0.95);
    border-radius: 16px;
    border: 1px solid #00ffff;
    box-shadow:
      0 0 20px rgba(0, 255, 255, 0.4),
      inset 0 0 20px rgba(0, 255, 255, 0.1);
    padding: 2rem;
    text-align: center;
    max-width: 400px;
    width: 100%;
    margin: 1rem;
  }

  .processing, .success, .error {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .loading-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid rgba(0, 255, 255, 0.3);
    border-top: 4px solid #00ffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  h2 {
    color: #00ffff;
    margin: 0;
    font-size: 1.5rem;
  }

  p {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    line-height: 1.5;
  }

  .success-icon {
    font-size: 3rem;
    color: #10b981;
  }

  .error-icon {
    font-size: 3rem;
    color: #ef4444;
  }

  .redirect-timer, .error-actions {
    margin-top: 1rem;
  }

  .countdown {
    color: #00ffff;
    font-weight: bold;
  }

  .error-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
  }

  .retry-btn, .home-btn {
    padding: 0.75rem 1.5rem;
    border: 1px solid #00ffff;
    border-radius: 8px;
    background: rgba(0, 255, 255, 0.1);
    color: #00ffff;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .retry-btn:hover, .home-btn:hover {
    background: rgba(0, 255, 255, 0.2);
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
  }

  .home-btn {
    border-color: #6b7280;
    color: #6b7280;
  }

  .home-btn:hover {
    background: rgba(107, 114, 128, 0.1);
    border-color: #6b7280;
    box-shadow: 0 0 10px rgba(107, 114, 128, 0.2);
  }

  /* Mobile responsive */
  @media (max-width: 480px) {
    .callback-container {
      padding: 1.5rem;
      margin: 0.5rem;
    }

    h2 {
      font-size: 1.25rem;
    }

    .error-actions {
      flex-direction: column;
    }

    .retry-btn, .home-btn {
      width: 100%;
    }
  }
</style>