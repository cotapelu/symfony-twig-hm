 <script lang="ts">
   import { createEventDispatcher } from 'svelte';
   import { fade } from 'svelte/transition';
   import Button from './atoms/Button.svelte';
   import Typography from './atoms/Typography.svelte';

   export let disabled = false;
   export let loadingProvider: string | null = null; // 'google', 'facebook', 'github'

  const dispatch = createEventDispatcher();

   function initiateOAuth(provider: string) {
    if (disabled || loadingProvider) return;

    loadingProvider = provider;
    dispatch('oauthStart', { provider });

    try {
      // Generate OAuth URL
      const oauthUrl = generateOAuthUrl(provider);

      // Open OAuth popup or redirect
      if (window.innerWidth > 768) {
        // Desktop: Open popup
        openOAuthPopup(oauthUrl, provider);
      } else {
        // Mobile: Redirect
        window.location.href = oauthUrl;
      }
      } catch (error: unknown) {
        console.error('OAuth initiation failed:', error);
        dispatch('oauthError', { provider, error: error instanceof Error ? error.message : 'Unknown error' });
      loadingProvider = null;
    }
  }

   function generateOAuthUrl(provider: string) {
    const baseUrl = '/auth/oauth';
    const params = new URLSearchParams({
      provider,
      redirect_uri: `${window.location.origin}/auth/callback`,
      scope: getProviderScopes(provider),
      state: generateState(),
      response_type: 'code'
    });

    return `${baseUrl}?${params.toString()}`;
  }

   function getProviderScopes(provider: string) {
     const scopes = {
       google: 'openid email profile',
       facebook: 'email public_profile',
       github: 'user:email read:user'
     };
     return scopes[provider as keyof typeof scopes] || 'email';
   }

   function generateState() {
     // Generate random state for CSRF protection
     return btoa(Math.random().toString()).substring(0, 16);
   }

   function openOAuthPopup(url: string, provider: string) {
    const width = 500;
    const height = 600;
    const left = (window.innerWidth - width) / 2;
    const top = (window.innerHeight - height) / 2;

    const popup = window.open(
      url,
      `oauth_${provider}`,
      `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`
    );

    if (popup) {
      // Listen for popup messages
      const messageListener = (event) => {
        // Verify origin for security
        if (event.origin !== window.location.origin) return;

        if (event.data.type === 'oauth_success') {
          handleOAuthSuccess(event.data);
          window.removeEventListener('message', messageListener);
        } else if (event.data.type === 'oauth_error') {
          handleOAuthError(event.data);
          window.removeEventListener('message', messageListener);
        }
      };

      window.addEventListener('message', messageListener);

      // Check if popup is closed without completing
      const checkClosed = setInterval(() => {
        if (popup.closed) {
          clearInterval(checkClosed);
          if (loadingProvider === provider) {
            loadingProvider = null;
            dispatch('oauthCancel', { provider });
          }
        }
      }, 1000);
    } else {
      // Popup blocked, fallback to redirect
      window.location.href = url;
    }
  }

  function handleOAuthSuccess(data: { provider: string; user: unknown; token: string }) {
    loadingProvider = null;
    dispatch('oauthSuccess', {
      provider: data.provider,
      user: data.user,
      token: data.token
    });
  }

  function handleOAuthError(data: { provider: string; error: string; message?: string }) {
    loadingProvider = null;
    dispatch('oauthError', {
      provider: data.provider,
      error: data.error,
      message: data.message
    });
  }
</script>

<div class="social-login" in:fade>
  <div class="social-divider">
    <span>or continue with</span>
  </div>

  <div class="social-buttons">
    <Button
      variant="secondary"
      className="social-btn google"
      onclick={() => initiateOAuth('google')}
      disabled={disabled || loadingProvider !== null}
    >
      {#if loadingProvider === 'google'}
        <div class="loading-spinner"></div>
      {:else}
        <svg class="social-icon" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
      {/if}
      <Typography variant="body">Google</Typography>
    </Button>

    <Button
      variant="secondary"
      className="social-btn facebook"
      onclick={() => initiateOAuth('facebook')}
      disabled={disabled || loadingProvider !== null}
    >
      {#if loadingProvider === 'facebook'}
        <div class="loading-spinner"></div>
      {:else}
        <svg class="social-icon" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
        </svg>
      {/if}
      <Typography variant="body">Facebook</Typography>
    </Button>

    <Button
      variant="secondary"
      className="social-btn github"
      onclick={() => initiateOAuth('github')}
      disabled={disabled || loadingProvider !== null}
    >
      {#if loadingProvider === 'github'}
        <div class="loading-spinner"></div>
      {:else}
        <svg class="social-icon" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#ffffff" d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
        </svg>
      {/if}
      <Typography variant="body">GitHub</Typography>
    </Button>
  </div>

   {#if loadingProvider}
     <div class="oauth-status">
       <Typography variant="caption">Connecting to {loadingProvider}...</Typography>
     </div>
   {/if}
</div>

<style>
  .social-login {
    width: 100%;
    max-width: 400px;
  }

  .social-divider {
    text-align: center;
    margin: 1.5rem 0;
    position: relative;
    color: rgba(255, 255, 255, 0.6);
  }

  .social-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  }

  .social-divider span {
    background: rgba(15, 15, 15, 0.9);
    padding: 0 1rem;
    position: relative;
    z-index: 1;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .social-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    border: 1px solid;
    border-radius: 8px;
    background: rgba(30, 30, 30, 0.8);
    color: #ffffff;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }

  .social-btn:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  }

  .social-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  .google {
    border-color: #4285F4;
    background: linear-gradient(135deg, rgba(66, 133, 244, 0.1), rgba(66, 133, 244, 0.05));
  }

  .google:hover:not(:disabled) {
    border-color: #4285F4;
    box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
  }

  .facebook {
    border-color: #1877F2;
    background: linear-gradient(135deg, rgba(24, 119, 242, 0.1), rgba(24, 119, 242, 0.05));
  }

  .facebook:hover:not(:disabled) {
    border-color: #1877F2;
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
  }

  .github {
    border-color: #ffffff;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    color: #ffffff;
  }

  .github:hover:not(:disabled) {
    border-color: #ffffff;
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
  }

  .social-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
  }

  .loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    flex-shrink: 0;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .oauth-status {
    text-align: center;
    margin-top: 1rem;
  }

  .oauth-status small {
    color: #00ffff;
    font-size: 0.9rem;
  }

  /* Mobile responsive */
  @media (max-width: 480px) {
    .social-buttons {
      gap: 0.5rem;
    }

    .social-btn {
      padding: 0.75rem 1rem;
      font-size: 0.9rem;
    }

    .social-icon {
      width: 18px;
      height: 18px;
    }
  }

  /* Dark mode support */
  :global([data-theme="dark"]) .social-btn {
    background: rgba(20, 20, 20, 0.9);
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .social-btn {
      transition: none;
    }

    .social-btn:not(:disabled):hover {
      transform: none;
    }

    .loading-spinner {
      animation: none;
    }
  }
</style>