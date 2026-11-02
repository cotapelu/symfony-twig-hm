<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount, onDestroy } from 'svelte';
  import Button from '../atoms/Button.svelte';

  export let open = false;
  export let title = '';
  export let size = 'md'; // sm, md, lg, xl
  export let closable = true;
  export let backdrop = true;
  export let persistent = false; // prevent close on backdrop click or escape

  const dispatch = createEventDispatcher();

  let modalElement: HTMLElement;
  let previousFocus: HTMLElement | null | undefined;

  $: if (open) {
    previousFocus = document.activeElement as HTMLElement | null;
    // Focus trap will be handled by focus management
  } else if (previousFocus) {
    previousFocus.focus();
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && !persistent && closable) {
      close();
    }
  }

  function handleBackdropClick(event: MouseEvent) {
    if (event.target === event.currentTarget && !persistent) {
      close();
    }
  }

  function close() {
    if (closable && !persistent) {
      open = false;
      dispatch('close');
    }
  }

  onMount(() => {
    document.addEventListener('keydown', handleKeydown);
  });

  onDestroy(() => {
    document.removeEventListener('keydown', handleKeydown);
  });

  $: classes = [
    'modal',
    `modal-${size}`,
    open ? 'modal-open' : ''
  ].filter(Boolean).join(' ');

  // Exported state getter for testing
  export const getIsOpen = () => open;
</script>

{#if open}
  <div
    class="modal-overlay"
    class:backdrop
    role="presentation"
    onclick={backdrop ? handleBackdropClick : undefined}
    onkeydown={(e) => { if (e.key === 'Escape' && !persistent && closable) close(); }}
  >
    <div
      bind:this={modalElement}
      class={classes}
      role="dialog"
      aria-modal="true"
      aria-labelledby={title ? 'modal-title' : undefined}
      tabindex="-1"
    >
      {#if title || closable}
        <div class="modal-header">
          {#if title}
            <h2 id="modal-title" class="modal-title">{title}</h2>
          {/if}
          {#if closable}
            <Button
              variant="ghost"
              size="sm"
              onclick={close}
              aria-label="Close modal"
            >
              ✕
            </Button>
          {/if}
        </div>
      {/if}

      <div class="modal-body">
        <slot />
      </div>

      <div class="modal-footer">
        <slot name="footer" />
      </div>
    </div>
  </div>
{/if}

<style>
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .modal-overlay.backdrop {
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
  }

  .modal {
    background: var(--bg-primary, white);
    border-radius: 8px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transform: scale(0.95);
    opacity: 0;
    transition: all 0.2s ease;
  }

  .modal-open {
    transform: scale(1);
    opacity: 1;
  }

  /* Sizes */
  .modal-sm {
    width: 100%;
    max-width: 400px;
  }

  .modal-md {
    width: 100%;
    max-width: 600px;
  }

  .modal-lg {
    width: 100%;
    max-width: 800px;
  }

  .modal-xl {
    width: 100%;
    max-width: 1200px;
  }

  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-primary, #e5e7eb);
  }

  .modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary, #111827);
  }

  .modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
  }

  .modal-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--border-primary, #e5e7eb);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
  }

  /* Focus management */
  .modal:focus {
    outline: none;
  }

  /* Responsive */
  @media (max-width: 640px) {
    .modal-overlay {
      padding: 0.5rem;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
      padding: 1rem;
    }

    .modal-title {
      font-size: 1.125rem;
    }
  }
</style>