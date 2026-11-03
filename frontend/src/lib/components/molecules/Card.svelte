<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let size: 'sm' | 'md' | 'lg' = 'md';
  export let variant: 'default' | 'outlined' | 'filled' | 'elevated' | 'danger' = 'default';
  export let hoverable = false;
  // class= forwarding (Svelte 5 strict): consumers pass className instead.
  export let className = '';
  export let clickable = false;
  export let loading = false;
  export let cover: string | null = null;
  export let actions: Array<{ label?: string; icon?: string }> = [];

  const dispatch = createEventDispatcher();

  $: classes = [
    'card',
    `card-${size}`,
    `card-${variant}`,
    hoverable ? 'card-hoverable' : '',
    clickable ? 'card-clickable' : '',
    loading ? 'card-loading' : '',
    className
  ].filter(Boolean).join(' ');

  function handleActionClick(action: { label?: string; icon?: string }, index: number) {
    dispatch('action', { action, index });
  }
</script>

<!-- svelte-ignore a11y_no_noninteractive_tabindex -->
<div class={classes} role={clickable ? 'button' : undefined} tabindex={clickable ? 0 : undefined} onkeydown={(e) => { if (clickable && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); dispatch('click'); } }}>
  {#if cover}
    <div class="card-cover">
      <img src={cover} alt="Card cover" loading="lazy" />
    </div>
  {/if}

  <div class="card-body">
    <slot />
  </div>

  {#if actions && actions.length > 0}
    <div class="card-actions">
      {#each actions as action, index}
        <button
          class="card-action-btn"
          onclick={() => handleActionClick(action, index)}
          aria-label={action.label || action.icon}
        >
          {#if action.icon}
            <span class="action-icon">{action.icon}</span>
          {/if}
          {#if action.label}
            <span class="action-label">{action.label}</span>
          {/if}
        </button>
      {/each}
    </div>
  {/if}

  {#if loading}
    <div class="card-loading-overlay" aria-hidden="true">
      <div class="card-spinner"></div>
    </div>
  {/if}
</div>

<style>
  .card {
    position: relative;
    display: flex;
    flex-direction: column;
    background: var(--bg-primary, white);
    border-radius: var(--border-radius, 8px);
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(0, 0, 0, 0.1));
    overflow: hidden;
    transition: all 0.2s ease;
  }

  /* Variants */
  .card-outlined {
    border: 1px solid var(--border-primary, #e5e7eb);
    box-shadow: none;
  }

  .card-filled {
    background: var(--bg-secondary, #f9fafb);
  }

  .card-elevated {
    box-shadow: var(--shadow-lg, 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05));
  }

  .card-danger {
    border: 1px solid var(--error-500, #ef4444);
    background: var(--error-50, #fef2f2);
  }

  /* Sizes */
  .card-sm {
    max-width: 300px;
  }

  .card-md {
    max-width: 400px;
  }

  .card-lg {
    max-width: 600px;
  }

  /* States */
  .card-hoverable:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06));
  }

  .card-clickable {
    cursor: pointer;
  }

  .card-clickable:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .card-loading {
    pointer-events: none;
    opacity: 0.7;
  }

  /* Cover */
  .card-cover {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
  }

  .card-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }

  .card-hoverable .card-cover img:hover {
    transform: scale(1.05);
  }

  /* Body */
  .card-body {
    padding: 1.5rem;
    flex: 1;
  }

  .card-sm .card-body {
    padding: 1rem;
  }

  .card-lg .card-body {
    padding: 2rem;
  }

  /* Actions */
  .card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-light, #f0f0f0);
    background: var(--bg-secondary, #f9fafb);
  }

  .card-action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: none;
    border: none;
    border-radius: 4px;
    color: var(--text-secondary, #6b7280);
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
  }

  .card-action-btn:hover {
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
  }

  .action-icon {
    font-size: 1rem;
  }

  /* Loading overlay */
  .card-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(2px);
  }

  .card-spinner {
    width: 2rem;
    height: 2rem;
    border: 3px solid var(--border-light, #f0f0f0);
    border-top: 3px solid var(--primary-500, #3b82f6);
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Responsive */
  @media (max-width: 640px) {
    .card-body {
      padding: 1rem;
    }

    .card-actions {
      padding: 0.75rem 1rem;
    }

    .card-action-btn {
      padding: 0.5rem;
    }

    .action-label {
      display: none; /* Hide labels on mobile */
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .card {
      border: 2px solid;
    }

    .card-outlined {
      border-width: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .card,
    .card-cover img,
    .card-action-btn {
      transition: none;
    }

    .card-hoverable:hover {
      transform: none;
    }

    .card-spinner {
      animation: none;
    }
  }
</style>