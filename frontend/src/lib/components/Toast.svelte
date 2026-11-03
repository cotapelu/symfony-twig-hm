<script lang="ts">
   import { createEventDispatcher } from 'svelte';
   import { fade, fly } from 'svelte/transition';
   import { quintOut } from 'svelte/easing';
   import Typography from './atoms/Typography.svelte';
   import Button from './atoms/Button.svelte';

   export let id: string | number;
   export let type: 'info' | 'success' | 'warning' | 'error' = 'info';
  export let title = '';
  export let message = '';
   export let duration: number = 5000;
   export let showCloseButton = true;
   export let persistent = false;
   // Prop callback (Svelte 5 dispatcher has no typed EventMap: on:dismiss from
   // consumers resolves to 'never' in svelte2tsx). Left untyped so consumers'
   // handlers assign cleanly.
   export let onDismiss = null;

   const dispatch = createEventDispatcher();

   let timeoutId: ReturnType<typeof setTimeout> | undefined;
   let mounted = false;

  function getTypeConfig() {
    const configs = {
      success: {
        icon: '✅',
        bgColor: 'var(--accent-success)',
        textColor: '#ffffff',
        borderColor: 'var(--accent-success)'
      },
      error: {
        icon: '❌',
        bgColor: 'var(--accent-error)',
        textColor: '#ffffff',
        borderColor: 'var(--accent-error)'
      },
      warning: {
        icon: '⚠️',
        bgColor: 'var(--accent-warning)',
        textColor: '#000000',
        borderColor: 'var(--accent-warning)'
      },
      info: {
        icon: 'ℹ️',
        bgColor: 'var(--accent-primary)',
        textColor: '#ffffff',
        borderColor: 'var(--accent-primary)'
      }
    };
    return configs[type] || configs.info;
  }

  function dismiss() {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }
    onDismiss?.();
    dispatch('dismiss', { id });
  }

  function pauseTimer() {
    if (timeoutId) {
      clearTimeout(timeoutId);
      timeoutId = undefined;
    }
  }

  function resumeTimer() {
    if (!persistent && duration > 0) {
      timeoutId = setTimeout(() => {
        dismiss();
      }, duration);
    }
  }

  import { onMount, onDestroy } from 'svelte';
  onMount(() => {
    mounted = true;
    resumeTimer();
  });

  onDestroy(() => {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }
  });

  $: if (mounted && !persistent && duration > 0) {
    resumeTimer();
  }
</script>

{#if mounted}
  <!-- class:type adds a class literally named "type", which left every
       .toast-notification.success/error/warning/info rule dead; interpolate
       the value instead. -->
  <div
    class="toast-notification {type}"
    onmouseenter={pauseTimer}
    onmouseleave={resumeTimer}
    role="alert"
    aria-live="assertive"
  >
    <div class="toast-icon">
      {getTypeConfig().icon}
    </div>

    <div class="toast-content">
      {#if title}
        <Typography variant="body" weight={600}>{title}</Typography>
      {/if}
      {#if message}
        <Typography variant="body">{message}</Typography>
      {/if}
    </div>

    {#if showCloseButton}
      <Button
        variant="ghost"
        size="sm"
        onclick={dismiss}
        aria-label="Close notification"
      >
        ×
      </Button>
    {/if}

    {#if !persistent && duration > 0}
      <div class="toast-progress" style="animation-duration: {duration}ms"></div>
    {/if}
  </div>
{/if}

<style>
  .toast-notification {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 8px;
    border: 1px solid;
    box-shadow: var(--shadow-lg);
    background: var(--bg-card);
    color: var(--text-primary);
    min-width: 300px;
    max-width: 500px;
    position: relative;
    overflow: hidden;
    margin-bottom: 0.5rem;
  }

  .toast-notification.success {
    border-color: var(--accent-success);
    background: linear-gradient(135deg, var(--accent-success), rgba(16, 185, 129, 0.1));
  }

  .toast-notification.error {
    border-color: var(--accent-error);
    background: linear-gradient(135deg, var(--accent-error), rgba(239, 68, 68, 0.1));
  }

  .toast-notification.warning {
    border-color: var(--accent-warning);
    background: linear-gradient(135deg, var(--accent-warning), rgba(245, 158, 11, 0.1));
  }

  .toast-notification.info {
    border-color: var(--accent-primary);
    background: linear-gradient(135deg, var(--accent-primary), rgba(59, 130, 246, 0.1));
  }

  .toast-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
    opacity: 0.9;
  }

  .toast-content {
    flex: 1;
    min-width: 0;
  }

  .toast-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
  }

  .toast-message {
    font-size: 0.9rem;
    line-height: 1.4;
    color: var(--text-secondary);
  }

  .toast-close {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0;
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .toast-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
  }

  .toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: currentColor;
    animation: progress linear;
    opacity: 0.7;
  }

  @keyframes progress {
    0% { width: 100%; }
    100% { width: 0%; }
  }

  /* Cyberpunk special effects */
  :global([data-theme="cyberpunk"]) .toast-notification {
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
    border: 1px solid var(--border-accent);
  }

  :global([data-theme="cyberpunk"]) .toast-notification.success {
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.4);
  }

  :global([data-theme="cyberpunk"]) .toast-notification.error {
    box-shadow: 0 0 20px rgba(255, 0, 64, 0.4);
  }

  :global([data-theme="cyberpunk"]) .toast-icon {
    text-shadow: 0 0 10px currentColor;
  }

  /* Mobile responsive */
  @media (max-width: 480px) {
    .toast-notification {
      min-width: 280px;
      max-width: calc(100vw - 2rem);
      margin: 0 1rem 0.5rem 1rem;
    }

    .toast-content {
      font-size: 0.9rem;
    }

    .toast-title {
      font-size: 0.9rem;
    }

    .toast-message {
      font-size: 0.85rem;
    }
  }

  /* Reduced motion support */
  @media (prefers-reduced-motion: reduce) {
    .toast-notification {
      animation: none;
    }

    .toast-progress {
      display: none;
    }
  }

  /* High contrast mode */
  @media (prefers-contrast: high) {
    .toast-notification {
      border-width: 2px;
    }

    .toast-title {
      font-weight: 700;
    }
  }
</style>