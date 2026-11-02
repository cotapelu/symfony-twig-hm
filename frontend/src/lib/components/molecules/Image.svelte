<script lang="ts">
  import Modal from './Modal.svelte';
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  export let src: string;
  export let alt: string = '';
  export let width: string | number = 'auto';
  export let height: string | number = 'auto';
  export let fallbackSrc: string = '';
  export let lazy: boolean = true;
  export let preview: boolean = false;
  export let objectFit: 'fill' | 'contain' | 'cover' | 'none' | 'scale-down' = 'cover';
  export let className: string = '';

  let imageElement: HTMLImageElement;
  let isLoading = true;
  let hasError = false;
  let showPreview = false;

  $: classes = [
    'image',
    isLoading ? 'image-loading' : '',
    hasError ? 'image-error' : '',
    className
  ].filter(Boolean).join(' ');

  $: style = `width: ${typeof width === 'number' ? width + 'px' : width}; height: ${typeof height === 'number' ? height + 'px' : height}; object-fit: ${objectFit};`;

  function handleLoad() {
    isLoading = false;
    hasError = false;
    dispatch('load', { src });
  }

  function handleError() {
    isLoading = false;
    hasError = true;
    if (fallbackSrc && imageElement.src !== fallbackSrc) {
      imageElement.src = fallbackSrc;
    }
    dispatch('error', { src });
  }

  function handleClick() {
    if (preview) {
      showPreview = true;
    }
  }
</script>

<div class={classes} style="position: relative; display: inline-block;">
  {#if preview}
    <button
      type="button"
      class="image-button"
      onclick={handleClick}
      aria-label="View full size image"
    >
      <img
        bind:this={imageElement}
        {src}
        {alt}
        {style}
        loading={lazy ? 'lazy' : 'eager'}
        onload={handleLoad}
        onerror={handleError}
      />
    </button>
  {:else}
    <img
      bind:this={imageElement}
      {src}
      {alt}
      {style}
      loading={lazy ? 'lazy' : 'eager'}
      onload={handleLoad}
      onerror={handleError}
    />
  {/if}

  {#if isLoading}
    <div class="image-skeleton" style="position: absolute; inset: 0; background: var(--gray-200, #e5e7eb); border-radius: 4px;"></div>
  {/if}

  {#if hasError && !fallbackSrc}
    <div class="image-fallback" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: var(--gray-100, #f3f4f6); color: var(--gray-500, #6b7280);">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
      </svg>
    </div>
  {/if}
</div>

{#if preview}
  <Modal bind:open={showPreview} size="lg">
    <div style="text-align: center;">
      <img {src} {alt} style="max-width: 100%; max-height: 80vh; object-fit: contain;" />
    </div>
  </Modal>
{/if}

<style>
  .image {
    position: relative;
    display: inline-block;
    overflow: hidden;
    border-radius: 4px;
    transition: opacity 0.3s ease;
  }

  .image img {
    display: block;
    width: 100%;
    height: 100%;
    transition: opacity 0.3s ease;
  }

  .image-loading img {
    opacity: 0;
  }

  .image-error img {
    opacity: 0.5;
  }

  .image-skeleton {
    animation: pulse 1.5s ease-in-out infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  .image-fallback {
    font-size: 1rem;
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .image-skeleton {
      background: var(--gray-700-dark, #374151);
    }

    .image-fallback {
      background: var(--gray-800-dark, #1f2937);
      color: var(--gray-400-dark, #9ca3af);
    }
  }

  /* Accessibility */
  .image img:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .image, .image img, .image-skeleton {
      transition: none;
      animation: none;
    }
  }

  @media (prefers-contrast: high) {
    .image {
      border: 2px solid var(--border-color, #d1d5db);
    }
  }
</style>