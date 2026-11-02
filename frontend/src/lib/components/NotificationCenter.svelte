<script lang="ts">
  import { onMount } from 'svelte';
  import { subscribeToTopic } from '$lib/api';
  import { PHP } from '$lib/api';
  import Button from './atoms/Button.svelte';
  import Typography from './atoms/Typography.svelte';
  import type { Notification } from '$lib/types';

  interface NotificationPayload {
    type: string;
    notification: Notification;
  }

  export let notifications: Notification[] = [];
  let unreadCount = 0;
  let showPanel = false;

  onMount(async () => {
    // Only bootstrap from the API when the parent has not already seeded
    // notifications, otherwise the fetch would clobber the prop contract.
    if (!notifications.length) {
      await loadNotifications();
    }

    subscribeToTopic('notifications', (data: unknown) => {
      const payload = data as NotificationPayload;
      if (payload.type === 'new_notification') {
        notifications = [payload.notification, ...notifications];
        unreadCount++;
      }
    });
  });

  async function loadNotifications() {
    try {
      const data = await PHP.getNotifications();
      notifications = data || [];
      unreadCount = 0;
    } catch (error) {
      console.error('Failed to load notifications:', error);
    }
  }

  function togglePanel() {
    showPanel = !showPanel;
    if (showPanel) {
      unreadCount = 0;
    }
  }

  function closePanel() {
    showPanel = false;
  }

  function markAsRead(notificationId: string) {
    notifications = notifications.filter(n => n.id !== notificationId);
  }
</script>

<!-- Notification Bell -->
<!-- svelte-ignore a11y_click_events_have_key_events -->
<!-- svelte-ignore a11y_no_static_element_interactions -->
<div class="notification-bell" role="button" tabindex="0" onclick={togglePanel} onkeydown={(e) => e.key === 'Enter' && togglePanel()}>
  🔔
  {#if unreadCount > 0}
    <span class="badge">{unreadCount}</span>
  {/if}
</div>

<!-- Notification Panel -->
{#if showPanel}
  <!-- svelte-ignore a11y_click_events_have_key_events -->
  <!-- svelte-ignore a11y_no_static_element_interactions -->
  <div class="notification-overlay" role="presentation" onclick={closePanel} onkeydown={(e) => e.key === 'Escape' && closePanel()}>
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div class="notification-panel" role="dialog" aria-modal="true" tabindex="-1" onclick={(e) => e.stopPropagation()}>
       <div class="panel-header">
          <Typography variant="h3">Notifications</Typography>
          <Button className="close-btn" onclick={closePanel}>×</Button>
        </div>

      <div class="notifications-list">
        {#each notifications as notification (notification.id)}
          <!-- svelte-ignore a11y_click_events_have_key_events -->
          <!-- svelte-ignore a11y_no_static_element_interactions -->
          <div class="notification-item" role="button" tabindex="0" onclick={() => markAsRead(notification.id)} onkeydown={(e) => e.key === 'Enter' && markAsRead(notification.id)}>
             <div class="notification-content">
               <Typography variant="h4">{notification.title}</Typography>
               <Typography>{notification.message}</Typography>
               <Typography variant="caption">{notification.created_at}</Typography>
             </div>
             <Button className="dismiss-btn" onclick={(e: MouseEvent) => { e.stopPropagation(); markAsRead(notification.id); }}>×</Button>
          </div>
         {:else}
           <div class="no-notifications">
             <Typography>No notifications</Typography>
           </div>
         {/each}
      </div>
    </div>
  </div>
{/if}

<style>
  .notification-bell {
    position: relative;
    cursor: pointer;
    font-size: 1.5rem;
    padding: 0.5rem;
    border-radius: 50%;
    transition: background 0.3s ease;
  }

  .notification-bell:hover {
    background: rgba(0, 123, 255, 0.1);
  }

  .badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 0.2rem 0.5rem;
    font-size: 0.8rem;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
  }

  .notification-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    justify-content: flex-end;
  }

  .notification-panel {
    width: 350px;
    max-width: 90vw;
    height: 100%;
    background: white;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
  }

  .panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #eee;
  }

  .panel-header h3 {
    /* svelte-ignore css_unused_selector */
    margin: 0;
    /* svelte-ignore css_unused_selector */
    color: #333;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
  }

  .notifications-list {
    flex: 1;
    overflow-y: auto;
  }

  .notification-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  .notification-item:hover {
    background: #f8f9fa;
  }

  .notification-content {
    flex: 1;
  }

  .notification-content h4 {
    /* svelte-ignore css_unused_selector */
    margin: 0 0 0.5rem 0;
    /* svelte-ignore css_unused_selector */
    color: #333;
    /* svelte-ignore css_unused_selector */
    font-size: 1rem;
  }

  .notification-content p {
    /* svelte-ignore css_unused_selector */
    margin: 0 0 0.5rem 0;
    /* svelte-ignore css_unused_selector */
    color: #666;
    /* svelte-ignore css_unused_selector */
    font-size: 0.9rem;
  }

  .notification-content small {
    /* svelte-ignore css_unused_selector */
    color: #999;
    /* svelte-ignore css_unused_selector */
    font-size: 0.8rem;
  }

  .dismiss-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .dismiss-btn:hover {
    color: #666;
  }

  .no-notifications {
    padding: 2rem;
    text-align: center;
    color: #666;
  }

  .no-notifications p {
    /* svelte-ignore css_unused_selector */
    margin: 0;
    /* svelte-ignore css_unused_selector */
    font-style: italic;
  }
</style>