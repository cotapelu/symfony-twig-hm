<script lang="ts">
   import '../app.css';
   import PWA from '../lib/components/PWA.svelte';
   import ToastManager from '../lib/components/ToastManager.svelte';
   import ErrorBoundary from '../lib/components/templates/ErrorBoundary.svelte';

  // Register service worker
  import { onMount } from 'svelte';
  import { dev } from '$app/environment';

  onMount(async () => {
    if ('serviceWorker' in navigator && !dev) {
      try {
        const registration = await navigator.serviceWorker.register('/service-worker.js', {
          scope: '/'
        });
        console.log('Service Worker registered:', registration);
      } catch (error) {
        console.log('Service Worker registration failed:', error);
      }
    }
  });
</script>

<PWA />
<ToastManager />

<a href="#main" class="skip-link">Skip to main content</a>

<main id="main">
  <ErrorBoundary>
    <slot />
  </ErrorBoundary>
</main>

<style>
  main {
    min-height: 100vh;
    background: #f9f9f9;
  }
</style>