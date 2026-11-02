<script lang="ts">
  import { writable } from 'svelte/store';
  import Toast from '../Toast.svelte';
  import { fade } from 'svelte/transition';
  import { flip } from 'svelte/animate';

  interface ToastOptions {
    duration?: number;
    persistent?: boolean;
    showCloseButton?: boolean;
  }

  interface ToastItem {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message: string;
    duration: number;
    persistent: boolean;
    showCloseButton: boolean;
  }

  export const toasts = writable<ToastItem[]>([]);

  let toastId = 0;

  export function showToast(type: 'success' | 'error' | 'warning' | 'info' = 'info', title = '', message = '', options: ToastOptions = {}): string {
    const id = String(++toastId);
    const toast: ToastItem = {
      id,
      type,
      title,
      message,
      duration: options.duration || 5000,
      persistent: options.persistent || false,
      showCloseButton: options.showCloseButton !== false
    };

    toasts.update(current => [...current, toast]);

    return id;
  }

  export function dismissToast(id: string) {
    toasts.update(current => current.filter(toast => toast.id !== id));
  }

  export function clearAllToasts() {
    toasts.set([]);
  }

  export function showSuccessToast(title: string, message: string, options: ToastOptions = {}) {
    return showToast('success', title, message, options);
  }

  export function showErrorToast(title: string, message: string, options: ToastOptions = {}) {
    return showToast('error', title, message, options);
  }

  export function showWarningToast(title: string, message: string, options: ToastOptions = {}) {
    return showToast('warning', title, message, options);
  }

  export function showInfoToast(title: string, message: string, options: ToastOptions = {}) {
    return showToast('info', title, message, options);
  }

  let currentToasts: ToastItem[] = [];
  toasts.subscribe(value => {
    currentToasts = value;
  });
</script>

{#if currentToasts.length > 0}
  <div class="toast-container" in:fade={{ duration: 200 }}>
     {#each currentToasts as toast (toast.id)}
      <div>
        <Toast
          id={toast.id}
          type={toast.type}
          title={toast.title}
          message={toast.message}
          duration={toast.duration}
          persistent={toast.persistent}
          showCloseButton={toast.showCloseButton}
          on:dismiss={({ detail }) => dismissToast(detail.id)}
        />
      </div>
    {/each}
  </div>
{/if}

<style>
  .toast-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-width: 400px;
    pointer-events: none;
  }

  .toast-container > div {
    pointer-events: auto;
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .toast-container {
      top: 1rem;
      right: 1rem;
      left: 1rem;
      max-width: none;
    }
  }

  @media (max-width: 480px) {
    .toast-container {
      top: 0.5rem;
      right: 0.5rem;
      left: 0.5rem;
    }
  }
</style>