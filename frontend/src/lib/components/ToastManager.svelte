<script lang="ts">
  import { onMount } from 'svelte';
  import Toast from './Toast.svelte';

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

<!-- Own container: ToastContainer renders from the global store and accepts no
     children (its render slot resolved to 'never' in svelte2tsx). This manager
     owns its toast list, so it renders its own stack. -->
<div class="toast-manager">
  {#each toasts as toast (toast.id)}
    <Toast 
      id={toast.id}
      type={toast.type} 
      message={toast.message} 
      onDismiss={() => removeToast(toast.id)}
    />
  {/each}
</div>
