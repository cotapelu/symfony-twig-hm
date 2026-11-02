<script lang="ts">
  import { onMount } from 'svelte';
  import Toast from './Toast.svelte';
  import ToastContainer from './ToastContainer.svelte';

  interface ToastMessage {
    id: number;
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    duration?: number;
  }

  let toasts: ToastMessage[] = [];
  let nextId = 0;

  onMount(() => {
    (window as any).showToast = (message: string, type: ToastMessage['type'] = 'info', duration = 3000) => {
      const id = nextId++;
      toasts = [...toasts, { id, message, type, duration }];
      setTimeout(() => {
        toasts = toasts.filter(t => t.id !== id);
      }, duration);
    };
    return () => {
      delete (window as any).showToast;
    };
  });

  function removeToast(id: number) {
    toasts = toasts.filter(t => t.id !== id);
  }
</script>

<ToastContainer>
  {#each toasts as toast (toast.id)}
    <Toast 
      type={toast.type} 
      message={toast.message} 
      on:close={() => removeToast(toast.id)} 
    />
  {/each}
</ToastContainer>
