<script lang="ts">
  import { notificationsStore } from '../../state/notificationsStore';
  import type { NotificationItem } from '../../contracts/notificationsTypes';

  export let maxItems = 5;
  export let showViewAll = true;

  $: recentNotifications = $notificationsStore.notifications.slice(0, maxItems);
  $: unreadCount = $notificationsStore.notifications.filter((n: NotificationItem) => !n.read).length;

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
</script>

<div class="notifications-dropdown bg-white rounded-lg shadow-lg border w-80 max-h-96 overflow-hidden">
  <div class="p-4 border-b flex items-center justify-between">
    <div class="flex items-center gap-2">
      <h3 class="font-semibold">Notifications</h3>
      {#if unreadCount > 0}
        <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{unreadCount}</span>
      {/if}
    </div>
    {#if unreadCount > 0}
      <button class="text-xs text-blue-600 hover:text-blue-800">Mark all read</button>
    {/if}
  </div>

  {#if recentNotifications.length === 0}
    <div class="p-8 text-center text-gray-500">
      <div class="text-4xl mb-2">🔔</div>
      <p class="text-sm">No new notifications</p>
    </div>
  {:else}
    <div class="overflow-y-auto max-h-64">
      {#each recentNotifications as notification (notification.id)}
        <div 
          class="p-3 hover:bg-gray-50 cursor-pointer flex items-start gap-3 {notification.read ? '' : 'bg-blue-50'}"
        >
          <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-sm">
            {getNotificationIcon(notification.type)}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{notification.title}</p>
            <p class="text-xs text-gray-500 truncate">{notification.message}</p>
            <p class="text-xs text-gray-400 mt-1">{getTimeAgo(notification.createdAt)}</p>
          </div>
          {#if !notification.read}
            <span class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-1"></span>
          {/if}
        </div>
      {/each}
    </div>
  {/if}

  {#if showViewAll}
    <div class="p-3 border-t bg-gray-50">
      <a href="/notifications" class="text-sm text-blue-600 hover:text-blue-800 text-center block">
        View all notifications
      </a>
    </div>
  {/if}
</div>
