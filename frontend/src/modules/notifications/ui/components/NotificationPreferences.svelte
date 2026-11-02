<script lang="ts">
  import { notificationsStore } from '../../state/notificationsStore';
  import { onMount } from 'svelte';
  import type { NotificationPreferences } from '../../contracts/notificationsTypes';

  let preferences: NotificationPreferences = {
    email: true,
    push: true,
    sms: false,
    types: {
      message: true,
      call: true,
      payment: true,
      system: true,
      social: true,
      security: true
    }
  };
  
  let saving = false;
  let saved = false;

  onMount(async () => {
    await notificationsStore.loadPreferences();
  });

  $: if ($notificationsStore.preferences) {
    preferences = { ...$notificationsStore.preferences };
  }

  async function handleSave() {
    saving = true;
    saved = false;
    await notificationsStore.updatePreferences(preferences);
    saving = false;
    saved = true;
    setTimeout(() => saved = false, 3000);
  }

  const notificationTypes = [
    { key: 'message', label: 'Messages', icon: '💬', description: 'Direct messages and chat notifications' },
    { key: 'call', label: 'Calls', icon: '📞', description: 'Incoming calls and call history' },
    { key: 'payment', label: 'Payments', icon: '💳', description: 'Payment confirmations and receipts' },
    { key: 'system', label: 'System', icon: '⚙️', description: 'System updates and maintenance' },
    { key: 'social', label: 'Social', icon: '👥', description: 'Friend requests and social activity' },
    { key: 'security', label: 'Security', icon: '🔒', description: 'Login alerts and security warnings' }
  ];
</script>

<div class="preferences-container">
  <div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border">
      <!-- Header -->
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold text-gray-900">Notification Preferences</h2>
        <p class="text-gray-500 text-sm mt-1">Manage how you receive notifications</p>
      </div>

      <!-- Channels -->
      <div class="p-6 border-b">
        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Notification Channels</h3>
        
        <div class="space-y-4">
          <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-lg">📧</div>
              <div>
                <p class="font-medium text-gray-900">Email Notifications</p>
                <p class="text-sm text-gray-500">Receive updates via email</p>
              </div>
            </div>
            <input 
              type="checkbox" 
              bind:checked={preferences.email}
              class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
            />
          </label>

          <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-lg">🔔</div>
              <div>
                <p class="font-medium text-gray-900">Push Notifications</p>
                <p class="text-sm text-gray-500">Receive push notifications on your device</p>
              </div>
            </div>
            <input 
              type="checkbox" 
              bind:checked={preferences.push}
              class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
            />
          </label>

          <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-lg">📱</div>
              <div>
                <p class="font-medium text-gray-900">SMS Notifications</p>
                <p class="text-sm text-gray-500">Receive important updates via SMS</p>
              </div>
            </div>
            <input 
              type="checkbox" 
              bind:checked={preferences.sms}
              class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
            />
          </label>
        </div>
      </div>

      <!-- Types -->
      <div class="p-6">
        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Notification Types</h3>
        
        <div class="space-y-3">
          {#each notificationTypes as type}
            <label class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
              <div class="flex items-center gap-3">
                <span class="text-xl">{type.icon}</span>
                <div>
                  <p class="font-medium text-gray-900">{type.label}</p>
                  <p class="text-xs text-gray-500">{type.description}</p>
                </div>
              </div>
              <input 
                type="checkbox" 
                bind:checked={preferences.types[type.key as keyof typeof preferences.types]}
                class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
              />
            </label>
          {/each}
        </div>
      </div>

      <!-- Footer -->
      <div class="p-6 border-t bg-gray-50 flex items-center justify-between">
        <div>
          {#if saved}
            <span class="text-green-600 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              Saved successfully
            </span>
          {/if}
        </div>
        <button 
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          onclick={handleSave}
          disabled={saving}
        >
          {#if saving}
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            Saving...
          {:else}
            Save Changes
          {/if}
        </button>
      </div>
    </div>

    <!-- Quiet Hours -->
    <div class="bg-white rounded-xl shadow-sm border mt-6">
      <div class="p-6 border-b">
        <h3 class="text-lg font-semibold text-gray-900">Quiet Hours</h3>
        <p class="text-gray-500 text-sm mt-1">Pause notifications during specific times</p>
      </div>
      <div class="p-6">
        <div class="flex items-center justify-between mb-4">
          <span class="font-medium text-gray-900">Enable Quiet Hours</span>
          <input type="checkbox" class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
            <input type="time" value="22:00" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
            <input type="time" value="07:00" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .preferences-container {
    padding: 1.5rem;
  }
</style>