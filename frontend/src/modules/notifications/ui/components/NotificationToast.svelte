<script lang="ts">
  import { fly, fade } from 'svelte/transition';
  import { flip } from 'svelte/animate';
  import type { NotificationItem } from '../../contracts/notificationsTypes';
  import { markAsRead } from '../../state/notificationsStore';

  interface Toast extends NotificationItem {
    toastId: string;
  }

  let toasts: Toast[] = [];

  export function showToast(notification: NotificationItem) {
    const toast: Toast = {
      ...notification,
      toastId: `${notification.id}-${Date.now()}`
    };
    
    toasts = [toast, ...toasts].slice(0, 5); // Max 5 toasts
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
      dismissToast(toast.toastId);
    }, 5000);
  }

  function dismissToast(toastId: string) {
    toasts = toasts.filter(t => t.toastId !== toastId);
  }

  function handleAction(toast: Toast) {
    if (!toast.read) {
      markAsRead(toast.id);
    }
    dismissToast(toast.toastId);
  }

  function getNotificationIcon(type: string) {
    switch (type) {
      case 'message': return '💬';
      case 'call': return '📞';
      case 'payment': return '💳';
      case 'system': return '⚙️';
      case 'social': return '👥';
      case 'security': return '🔒';
      default: return '🔔';
    }
  }

  function getNotificationColor(type: string) {
    switch (type) {
      case 'message': return 'bg-blue-500';
      case 'call': return 'bg-green-500';
      case 'payment': return 'bg-purple-500';
      case 'system': return 'bg-gray-500';
      case 'social': return 'bg-pink-500';
      case 'security': return 'bg-red-500';
      default: return 'bg-blue-500';
    }
  }
</script>

<div class="toast-container">
  {#each toasts as toast (toast.toastId)}
    <div 
      class="toast-item"
      transition:fly={{ x: 100, duration: 300 }}
      animate:flip={{ duration: 200 }}
    >
      <div class="bg-white rounded-xl shadow-2xl border overflow-hidden max-w-sm">
        <!-- Progress bar -->
        <div class="h-1 {getNotificationColor(toast.type)} animate-progress"></div>
        
        <div class="p-4 flex items-start gap-3">
          <!-- Icon -->
          <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-2xl">
            {getNotificationIcon(toast.type)}
          </div>
          
          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between">
              <h4 class="font-semibold text-gray-900 text-sm">{toast.title}</h4>
              <button 
                class="text-gray-400 hover:text-gray-600 ml-2"
                onclick={() => dismissToast(toast.toastId)}
                aria-label="Dismiss"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <p class="text-sm text-gray-600 mt-1 line-clamp-2">{toast.message}</p>
            
            {#if toast.action}
              <button 
                class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-800"
                onclick={() => handleAction(toast)}
              >
                {toast.action.label}
              </button>
            {/if}
          </div>
        </div>
      </div>
    </div>
  {/each}
</div>

<style>
  .toast-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    pointer-events: none;
  }
  
  .toast-item {
    pointer-events: auto;
  }
  
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  
  @keyframes progress {
    from { width: 100%; }
    to { width: 0%; }
  }
  
  .animate-progress {
    animation: progress 5s linear forwards;
  }
</style>