<script lang="ts">
  import { onMount } from 'svelte';
  import { Button, Typography } from '$lib/components';

  interface Reader {
    name?: string;
    user_id?: string;
    read_at?: string;
    isOnline?: boolean;
  }

  export let messageId: string;
  export let isOwnMessage = false;
  export let readCount = 0;
  export let readers: Reader[] = [];
  export let showDetails = false;
	export const conversationId: string | null = null;

  let readStatus: 'sending' | 'sent' | 'delivered' | 'read' = 'sending';
  let lastReadUpdate: number | null = null;

  let messageElement: HTMLElement;

  onMount(() => {
    if (messageElement && !isOwnMessage) {
      const observer = new IntersectionObserver(
         (entries) => {
           entries.forEach((entry) => {
             if (entry.isIntersecting) {
               markAsRead();
             }
           });
         },
        { threshold: 0.5 } // 50% of message must be visible
      );

      observer.observe(messageElement);
      return () => observer.disconnect();
    }
    return () => {};
  });

  async function markAsRead() {
    if (isOwnMessage || readStatus === 'read') return;

    // Get JWT token from localStorage
    const token = localStorage.getItem('auth_token');

    try {
      const response = await fetch('/api/messages/read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': token ? `Bearer ${token}` : ''
        },
        body: JSON.stringify({ messageIds: [messageId] })
      });

      if (response.ok) {
        readStatus = 'read';
        lastReadUpdate = Date.now();
        // Emit event for real-time updates
        window.dispatchEvent(new CustomEvent('messageRead', {
          detail: { messageId, readCount: readCount + 1, timestamp: lastReadUpdate }
        }));
      } else if (response.status === 401) {
        // Token might be expired, redirect to login
        localStorage.removeItem('auth_token');
        window.location.href = '/auth';
      }
    } catch (error) {
      console.error('Failed to mark message as read:', error);
    }
  }

  async function loadReadDetails() {
    if (!showDetails || readers.length > 0) return;

    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch(`/api/messages/${messageId}/read-receipts`, {
        headers: {
          'Authorization': token ? `Bearer ${token}` : ''
        }
      });

      if (response.ok) {
        const data = await response.json();
        readers = data.readers || [];
        readCount = data.totalReadCount || readCount;
      }
    } catch (error) {
      console.error('Failed to load read details:', error);
    }
  }

  // Load detailed read information when details are shown
  $: if (showDetails) {
    loadReadDetails();
  }

  function getStatusIcon(): string {
    switch (readStatus) {
      case 'sending':
        return '⏳';
      case 'sent':
        return '✓';
      case 'delivered':
        return '✓✓';
      case 'read':
        return '✓✓';
      default:
        return '';
    }
  }



  function toggleDetails() {
    showDetails = !showDetails;
  }
</script>

<div class="read-receipt" bind:this={messageElement}>
  {#if isOwnMessage}
    <div class="status-indicator" class:read={readStatus === 'read'}>
      {getStatusIcon()}
      {#if readCount > 0}
        <span class="read-count" role="button" tabindex="0" aria-label="Toggle read details" onclick={toggleDetails} onkeydown={(e: KeyboardEvent) => { if (e.key === 'Enter' || e.key === ' ') { toggleDetails(); e.preventDefault(); } }}>
          {readCount}
        </span>
      {/if}
    </div>

    {#if showDetails}
      <div class="read-details">
         <div class="read-details-header">
           <Typography variant="h4">Read by {readCount} people</Typography>
           <Button onclick={toggleDetails} aria-label="Close read details">×</Button>
         </div>
        {#if readers.length > 0}
          <div class="readers-list">
            {#each readers as reader}
              <div class="reader-item">
                <div class="reader-avatar">
                  {reader.name ? reader.name.charAt(0).toUpperCase() : '?'}
                </div>
                 <div class="reader-info">
                    <Typography className="reader-name">{reader.name || reader.user_id}</Typography>
                    <Typography className="read-time">
                     {reader.read_at ? new Date(reader.read_at).toLocaleTimeString([], {
                       hour: '2-digit',
                       minute: '2-digit'
                     }) : 'Just now'}
                   </Typography>
                 </div>
                {#if reader.isOnline}
                  <div class="online-indicator" title="Online"></div>
                {/if}
              </div>
            {/each}
          </div>
         {:else}
           <div class="no-readers">
             <Typography variant="caption">No read receipts available</Typography>
           </div>
         {/if}
      </div>
    {/if}
  {/if}
</div>

<style>
  .read-receipt {
    display: inline-flex;
    align-items: center;
    margin-left: 4px;
  }

  .status-indicator {
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 12px;
    opacity: 0.7;
  }

  .status-indicator.read {
    color: #10b981;
  }

  .read-count {
    cursor: pointer;
    background: linear-gradient(45deg, #10b981, #34d399);
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 4px;
    box-shadow: 0 1px 3px rgba(16, 185, 129, 0.3);
    transition: all 0.3s ease;
  }

  .read-count:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.4);
  }

  .read-details {
    position: absolute;
    background: var(--bg-primary);
    border: 1px solid var(--border-primary);
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    padding: 12px;
    min-width: 250px;
    z-index: 1000;
    margin-top: 8px;
  }

  .read-details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }

  .read-details-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
  }

  .read-details-header button {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .read-details-header button:hover {
    color: #374151;
  }

  .readers-list {
    max-height: 250px;
    overflow-y: auto;
  }

  .reader-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
  }

  .reader-item:last-child {
    border-bottom: none;
  }

  .reader-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(45deg, #00ffff, #ff00ff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000000;
    font-weight: bold;
    font-size: 14px;
    flex-shrink: 0;
  }

  .reader-info {
    flex: 1;
    min-width: 0;
  }

  .reader-name {
    font-weight: 500;
    color: #ffffff;
    font-size: 14px;
    display: block;
    margin-bottom: 2px;
  }

  .read-time {
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
  }

  .online-indicator {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    border: 2px solid #1a1a1a;
    position: absolute;
    top: 8px;
    right: 8px;
    box-shadow: 0 0 4px rgba(16, 185, 129, 0.6);
  }

  .no-readers {
    text-align: center;
    padding: 20px;
    color: rgba(255, 255, 255, 0.6);
  }

  /* Dark mode support */
  :global([data-theme="dark"]) .read-details {
    background: var(--bg-secondary);
    border-color: var(--border-primary);
  }

  :global([data-theme="dark"]) .read-details-header h4 {
    color: var(--text-primary);
  }

  :global([data-theme="dark"]) .reader-name {
    color: var(--text-primary);
  }

  :global([data-theme="dark"]) .read-time {
    color: var(--text-secondary);
  }

  :global([data-theme="dark"]) .reader-item {
    border-bottom-color: var(--border-primary);
  }

  /* Mobile responsive */
  @media (max-width: 480px) {
    .read-details {
      min-width: 200px;
      max-width: calc(100vw - 32px);
    }

    .reader-item {
      flex-direction: column;
      align-items: flex-start;
      gap: 2px;
    }
  }
</style>