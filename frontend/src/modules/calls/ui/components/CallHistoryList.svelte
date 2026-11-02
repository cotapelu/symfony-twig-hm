<script lang="ts">
  import type { CallHistory } from '../../contracts/callTypes';

  export let history: CallHistory[] = [];
  export let loading = false;
  export let onCallClick: ((item: CallHistory) => void) | null = null;

  function formatDuration(seconds: number): string {
    if (seconds < 60) return `${seconds}s`;
    const mins = Math.floor(seconds / 60);
    if (mins < 60) return `${mins}m`;
    const hours = Math.floor(mins / 60);
    return `${hours}h ${mins % 60}m`;
  }

  function getStatusIcon(status: string): string {
    switch (status) {
      case 'completed': return '✅';
      case 'missed': return '❌';
      case 'declined': return '🚫';
      default: return '📞';
    }
  }

  function getStatusColor(status: string): string {
    switch (status) {
      case 'completed': return 'text-green-600 bg-green-50';
      case 'missed': return 'text-red-600 bg-red-50';
      case 'declined': return 'text-gray-600 bg-gray-50';
      default: return 'text-gray-600 bg-gray-50';
    }
  }

  function formatTime(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days === 0) {
      return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    } else if (days === 1) {
      return 'Yesterday';
    } else if (days < 7) {
      return date.toLocaleDateString('en-US', { weekday: 'long' });
    } else {
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
  }

  function groupByDate(items: CallHistory[]): Record<string, CallHistory[]> {
    const groups: Record<string, CallHistory[]> = {};
    
    items.forEach(item => {
      const date = new Date(item.startTime);
      const now = new Date();
      const diff = now.getTime() - date.getTime();
      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      
      let key: string;
      if (days === 0) key = 'Today';
      else if (days === 1) key = 'Yesterday';
      else if (days < 7) key = 'This Week';
      else if (days < 30) key = 'This Month';
      else key = 'Older';
      
      if (!groups[key]) groups[key] = [];
      groups[key].push(item);
    });
    
    return groups;
  }

  $: groupedHistory = groupByDate(history);
</script>

<div class="call-history">
  {#if loading}
    <div class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
    </div>
  {:else if history.length === 0}
    <div class="text-center py-8 text-gray-500">
      <div class="text-4xl mb-2">📞</div>
      <p>No call history</p>
    </div>
  {:else}
    <div class="space-y-6">
      {#each Object.entries(groupedHistory) as [date, calls]}
        <div class="history-group">
          <h3 class="text-sm font-semibold text-gray-500 mb-3 px-2">{date}</h3>
          <div class="space-y-2">
            {#each calls as call}
              <button 
                class="w-full p-3 rounded-xl hover:bg-gray-50 flex items-center gap-4 transition-colors text-left"
                onclick={() => onCallClick?.(call)}
              >
                <!-- Status Icon -->
                <div class="w-10 h-10 rounded-full {getStatusColor(call.status)} flex items-center justify-center text-lg">
                  {call.type === 'video' ? '📹' : '📞'}
                </div>
                
                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-900">
                      {call.participants.map(p => p.name).join(', ')}
                    </span>
                    {#if call.participants.length > 1}
                      <span class="text-xs text-gray-500">+{call.participants.length - 1}</span>
                    {/if}
                  </div>
                  <div class="flex items-center gap-3 text-sm text-gray-500">
                    <span>{call.status}</span>
                    <span>•</span>
                    <span>{formatDuration(call.duration)}</span>
                  </div>
                </div>
                
                <!-- Time -->
                <span class="text-sm text-gray-400">{formatTime(call.startTime)}</span>
                
                <!-- Call Button -->
                <div class="w-8 h-8 rounded-full bg-green-500 hover:bg-green-600 flex items-center justify-center text-white">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                </div>
              </button>
            {/each}
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>