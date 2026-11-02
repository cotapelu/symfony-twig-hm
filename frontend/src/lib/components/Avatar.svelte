<script lang="ts">
  import { onMount } from 'svelte';

  export let src = '';
  export let alt = '';
  // Consumers pass a display `name`; feeds the initials fallback.
  export let name = '';
  export let size: 'xs' | 'sm' | 'md' | 'lg' | 'xl' = 'md';
  export let shape: 'circle' | 'square' | 'rounded' = 'circle';
  export let fallback = '';
  export let showStatus = false;
  export let status: 'online' | 'offline' | 'busy' | 'away' = 'online';
  export let group = false;
  export const maxCount = 3;

  let hasError = false;
  let avatarElement: HTMLImageElement;

  $: classes = [
    'avatar',
    `avatar-${size}`,
    `avatar-${shape}`,
    hasError ? 'avatar-error' : '',
    group ? 'avatar-group' : ''
  ].filter(Boolean).join(' ');

  $: statusClasses = [
    'avatar-status',
    `avatar-status-${status}`
  ].filter(Boolean).join(' ');

  $: displayText = getDisplayText();

  function getDisplayText() {
    if (fallback) return fallback;
    const label = alt || name;
    if (label) return label.charAt(0).toUpperCase();
    return '?';
  }

  function handleError() {
    hasError = true;
  }

  onMount(() => {
    if (avatarElement && avatarElement.complete && avatarElement.naturalHeight === 0) {
      hasError = true;
    }
  });
</script>

{#if group}
  <div class="avatar-group-wrapper">
    <slot />
  </div>
{:else}
  <div class={classes}>
    {#if src && !hasError}
      <img
        bind:this={avatarElement}
        {src}
        {alt}
        on:error={handleError}
        loading="lazy"
        class="avatar-image"
      />
    {:else}
      <div class="avatar-fallback" aria-label={alt || 'Avatar'}>
        {displayText}
      </div>
    {/if}

    {#if showStatus}
      <div class={statusClasses} aria-label={`Status: ${status}`}></div>
    {/if}
  </div>
{/if}

<style>
  .avatar {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
    background: var(--gray-200, #e5e7eb);
    color: var(--gray-600, #4b5563);
    font-weight: 500;
  }

  /* Shapes */
  .avatar-circle {
    border-radius: 50%;
  }

  .avatar-square {
    border-radius: 0;
  }

  .avatar-rounded {
    border-radius: 6px;
  }

  /* Sizes */
  .avatar-xs {
    width: 1.5rem;
    height: 1.5rem;
    font-size: 0.625rem;
  }

  .avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.75rem;
  }

  .avatar-md {
    width: 2.5rem;
    height: 2.5rem;
    font-size: 0.875rem;
  }

  .avatar-lg {
    width: 3rem;
    height: 3rem;
    font-size: 1rem;
  }

  .avatar-xl {
    width: 4rem;
    height: 4rem;
    font-size: 1.25rem;
  }

  /* Image */
  .avatar-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  /* Fallback */
  .avatar-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: var(--primary-500, #3b82f6);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
  }

  /* Error state */
  .avatar-error .avatar-fallback {
    background: var(--error-500, #ef4444);
  }

  /* Status indicator */
  .avatar-status {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 25%;
    height: 25%;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
  }

  .avatar-status-online {
    background: var(--success-500, #10b981);
  }

  .avatar-status-offline {
    background: var(--gray-400, #9ca3af);
  }

  .avatar-status-busy {
    background: var(--error-500, #ef4444);
  }

  .avatar-status-away {
    background: var(--warning-500, #f59e0b);
  }

  /* Avatar groups */
  .avatar-group-wrapper {
    display: flex;
    align-items: center;
  }

  .avatar-group-wrapper :global(.avatar:not(:first-child)) {
    margin-left: -0.5rem;
  }

  .avatar-group-wrapper :global(.avatar) {
    border: 2px solid white;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
  }

  /* Responsive */
  @media (max-width: 640px) {
    .avatar-status {
      width: 30%;
      height: 30%;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .avatar {
      border: 2px solid;
    }

    .avatar-status {
      border-width: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .avatar-image {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .avatar {
      background: var(--gray-700, #374151);
      color: var(--gray-300, #d1d5db);
    }

    .avatar-fallback {
      background: var(--primary-600, #2563eb);
    }
  }
</style>