<script lang="ts">
  import { notificationsStore, markAsRead } from '../../state/notificationsStore';
  import { onMount, onDestroy } from 'svelte';
  import type { NotificationItem } from '../../contracts/notificationsTypes';

  export let onClick: (() => void) | null = null;
  
  let showDropdown = false;
  let dropdownRef: HTMLDivElement;
  let unsubscribe: (() => void) | null = null;

  onMount(() => {
    notificationsStore.loadNotifications();
    
    // Subscribe to real-time notifications
    const service = new (await import('../../services/notificationsService')).NotificationsService();
    unsubscribe = service.subscribeToNotifications((notification: NotificationItem) => {
      notificationsStore.addNotification(notification);
      showToast(notification);
    });

    // Close dropdown when clicking outside
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef && !dropdownRef.contains(event.target as Node)) {
        showDropdown = false;
      }
    };
    document.addEventListener('click', handleClickOutside);

    return () => {
      document.removeEventListener('click', handleClickOutside);
      if (unsubscribe) unsubscribe();
    };
  });

  onDestroy(() => {
    if (unsubscribe) unsubscribe();
  });

  function showToast(notification: NotificationItem) {
    // Toast notification logic would go here
    // For now, just log
    console.log('New notification:', notification);
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

  function getTimeAgo(date: string) {
    const diff = Date.now() - new Date(date).getTime();
    const minutes = Math.floor(diff / 60000);
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h`;
    return `${Math.floor(hours / 24)}d`;
  }

  function handleNotificationClick(notification: NotificationItem) {
    if (!notification.read) {
      markAsRead(notification.id);
    }
    showDropdown = false;
  }

  $: unreadCount = $notificationsStore.notifications.filter((n: NotificationItem) => !n.read).length;
  $: recentNotifications = $notificationsStore.notifications.slice(0, 5);
</script>

<div class="relative" bind:this={dropdownRef}>
  <!-- Bell Button -->
  <button 
    class="relative p-2 rounded-full hover:bg-gray-100 transition-colors"
    onclick={() => {
      showDropdown = !showDropdown;
      onClick?.();
    }}
    aria-label="Notifications"
  >
    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
    </svg>
    
    {#if unreadCount > 0}
      <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold min-w-[20px] h-5 px-1.5 rounded-full flex items-center justify-center animate-pulse">
        {unreadCount > 99 ? '99+' : unreadCount}
      </span>
    {/if}
  </button>

  <!-- Dropdown -->
  {#if showDropdown}
    <div class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-2xl border z-50 overflow-hidden animate-fadeIn">
      <!-- Header -->
      <div class="p-4 border-b flex items-center justify-between bg-gray-50">
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-gray-900">Notifications</h3>
          {#if unreadCount > 0}
            <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{unreadCount}</span>
          {/if}
        </div>
        <div class="flex items-center gap-2">
          {#if unreadCount > 0}
            <button 
              class="text-xs text-blue-600 hover:text-blue-800 font-medium"
              onclick={() => notificationsStore.markAllAsRead()}
            >
              Mark all read
            </button>
          {/if}
        </div>
      </div>

      <!-- Notifications List -->
      {#if recentNotifications.length === 0}
        <div class="p-8 text-center text-gray-500">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
          <p class="font-medium">No notifications yet</p>
          <p class="text-sm mt-1">We'll notify you when something arrives</p>
        </div>
      {:else}
        <div class="max-h-80 overflow-y-auto">
          {#each recentNotifications as notification (notification.id)}
            <div 
              class="p-3 hover:bg-gray-50 cursor-pointer flex items-start gap-3 border-b last:border-b-0 transition-colors {notification.read ? '' : 'bg-blue-50/50'}"
              onclick={() => handleNotificationClick(notification)}
            >
              <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center text-lg shadow-sm">
                {getNotificationIcon(notification.type)}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <p class="text-sm font-semibold text-gray-900 truncate">{notification.title}</p>
                  <span class="text-xs text-gray-400 whitespace-nowrap ml-2">{getTimeAgo(notification.createdAt)}</span>
                </div>
                <p class="text-xs text-gray-600 mt-0.5 line-clamp-2">{notification.message}</p>
              </div>
              {#if !notification.read}
                <span class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2 animate-pulse"></span>
              {/if}
            </div>
          {/each}
        </div>
      {/if}

      <!-- Footer -->
      <div class="p-3 border-t bg-gray-50">
        <a 
          href="/notifications" 
          class="text-sm text-blue-600 hover:text-blue-800 font-medium text-center block py-1"
          onclick={() => showDropdown = false}
        >
          View all notifications
          <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </a>
      </div>
    </div>
  {/if}
</div>

<style>
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .animate-fadeIn {
    animation: fadeIn 0.2s ease-out;
  }
  
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>