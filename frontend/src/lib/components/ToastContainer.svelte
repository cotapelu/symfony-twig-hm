<script lang="ts">
  import { onMount } from 'svelte';
  import { toasts, removeToast } from '$lib/stores/global';
  import Toast from './Toast.svelte';
</script>

{#each $toasts as toast (toast.id)}
  <Toast
    id={toast.id}
    message={toast.message}
    type={toast.type}
    title={toast.title}
    onDismiss={() => removeToast(toast.id)}
  />
{/each}