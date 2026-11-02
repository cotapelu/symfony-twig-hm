<script lang="ts">
  import { notificationsStore, markAsRead, markAllAsRead, deleteNotification } from '../state/notificationsStore';
  import { onMount } from 'svelte';
  import type { NotificationItem } from '../contracts/notificationsTypes';

  let selectedFilter = 'all';
  let searchQuery = '';

  onMount(() => {
    notificationsStore.loadNotifications();
  });

  $: filteredNotifications = $notificationsStore.notifications.filter((n: NotificationItem) => {
    const matchesFilter = selectedFilter === 'all' || 
      (selectedFilter === 'unread' && !n.read) ||
      n.type === selectedFilter;
    const matchesSearch = searchQuery === '' || 
      n.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      n.message.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesFilter && matchesSearch;
  });

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
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
  }
</script>

<div class="notifications-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-bold">Notifications</h1>
          {#if unreadCount > 0}
            <span class="bg-red-500 text-white text-sm px-2 py-1 rounded-full">{unreadCount}</span>
          {/if}
        </div>
        <div class="flex gap-2">
          {#if unreadCount > 0}
            <button 
              class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
              onclick={() => markAllAsRead()}
            >
              Mark all as read
            </button>
          {/if}
          <button 
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            onclick={() => notificationsStore.clearAll()}
          >
            Clear all
          </button>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b">
          <div class="flex flex-wrap gap-4">
            <input
              type="text"
              bind:value={searchQuery}
              placeholder="Search notifications..."
              class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
            <select 
              bind:value={selectedFilter}
              class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="all">All Notifications</option>
              <option value="unread">Unread</option>
              <option value="message">Messages</option>
              <option value="call">Calls</option>
              <option value="payment">Payments</option>
              <option value="social">Social</option>
              <option value="system">System</option>
              <option value="security">Security</option>
            </select>
          </div>
        </div>

        {#if $notificationsStore.loading}
          <div class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
          </div>
        {:else if filteredNotifications.length === 0}
          <div class="text-center py-12 text-gray-500">
            <div class="text-6xl mb-4">🔔</div>
            <p class="text-lg">No notifications</p>
            <p class="text-sm">You're all caught up!</p>
          </div>
        {:else}
          <div class="divide-y">
            {#each filteredNotifications as notification (notification.id)}
              <div 
                class="p-4 hover:bg-gray-50 transition-colors cursor-pointer flex items-start gap-4 {notification.read ? '' : 'bg-blue-50'}"
                onclick={() => !notification.read && markAsRead(notification.id)}
              >
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-xl">
                  {getNotificationIcon(notification.type)}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between">
                    <div>
                      <h3 class="font-semibold text-gray-900">{notification.title}</h3>
                      <p class="text-gray-600 text-sm mt-1">{notification.message}</p>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                      <span class="text-xs text-gray-400 whitespace-nowrap">{getTimeAgo(notification.createdAt)}</span>
                      {#if !notification.read}
                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                      {/if}
                    </div>
                  </div>
                  {#if notification.action}
                    <button class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                      {notification.action.label}
                    </button>
                  {/if}
                </div>
                <button 
                  class="text-gray-400 hover:text-red-500 p-1"
                  onclick={(e) => { e.stopPropagation(); deleteNotification(notification.id); }}
                  aria-label="Delete notification"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            {/each}
          </div>
        {/if}
      </div>

      {#if $notificationsStore.notifications.length > 0}
        <div class="text-center text-gray-500 text-sm">
          Showing {filteredNotifications.length} of {$notificationsStore.notifications.length} notifications
        </div>
      {/if}
    </div>
  </div>
</div>

<style>
  .notifications-page {
    min-height: 100vh;
    background: #f9fafb;
  }
</style>
