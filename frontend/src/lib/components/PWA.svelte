<script lang="ts">
  import { onMount } from 'svelte';

  interface DeferredPromptEvent extends Event {
    preventDefault(): void;
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
  }

  let isOnline = true;
  let deferredPrompt: DeferredPromptEvent | null = null;

  onMount(() => {
    isOnline = navigator.onLine;

    const handleOnline = () => { isOnline = true; };
    const handleOffline = () => { isOnline = false; };
    const handleBeforeInstallPrompt = (e: Event) => {
      e.preventDefault();
      deferredPrompt = e as DeferredPromptEvent;
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    };
  });

  function installPWA() {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
          console.log('User accepted the A2HS prompt');
        }
        deferredPrompt = null;
      });
    }
  }
</script>

<div class="pwa-status">
  {#if !isOnline}
    <div class="offline-banner">
      🔴 You're offline - some features may not work
    </div>
  {/if}

  {#if deferredPrompt}
    <button class="install-btn" onclick={installPWA}>
      📱 Install App
    </button>
  {/if}
</div>

<style>
  .pwa-status {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
  }

  .offline-banner {
    background: #dc3545;
    color: white;
    text-align: center;
    padding: 0.5rem;
    font-weight: bold;
  }

  .install-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #007bff;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
  }

  .install-btn:hover {
    background: #0056b3;
  }
</style>