<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { fade, slide } from 'svelte/transition';
  import Button from './atoms/Button.svelte';
  import Input from './atoms/Input.svelte';
  import Typography from './atoms/Typography.svelte';
  import Checkbox from './atoms/Checkbox.svelte';

  export let showPanel = false;
  export let onClose: (() => void) | null = null;

  let permissionStatus: string = 'default';
  let isLoading = false;
  let registrationStatus: 'idle' | 'registering' | 'registered' | 'failed' = 'idle';
  let testNotificationStatus: string = 'idle';

  let notificationsEnabled = true;
  let soundEnabled = true;
  let messageNotifications = true;
  let groupNotifications = true;
  let callNotifications = true;
  let systemNotifications = false;

  let quietHoursEnabled = false;
  let quietStartTime = '22:00';
  let quietEndTime = '08:00';

  let deviceToken: string | null = null;
  let registrationId: string | null = null;

  const dispatch = createEventDispatcher();

  import { onMount } from 'svelte';
  onMount(() => {
    if ('Notification' in window) {
      permissionStatus = Notification.permission;
    } else {
      permissionStatus = 'unsupported';
    }

    loadSettings();
    loadDeviceStatus();
  });

  function loadSettings() {
    try {
      const settings = JSON.parse(localStorage.getItem('notificationSettings') || '{}');
      notificationsEnabled = settings.notificationsEnabled ?? true;
      soundEnabled = settings.soundEnabled ?? true;
      messageNotifications = settings.messageNotifications ?? true;
      groupNotifications = settings.groupNotifications ?? true;
      callNotifications = settings.callNotifications ?? true;
      systemNotifications = settings.systemNotifications ?? false;
      quietHoursEnabled = settings.quietHoursEnabled ?? false;
      quietStartTime = settings.quietStartTime ?? '22:00';
      quietEndTime = settings.quietEndTime ?? '08:00';
    } catch (error) {
      console.warn('Failed to load notification settings:', error);
    }
  }

  function saveSettings() {
    try {
      const settings = {
        notificationsEnabled,
        soundEnabled,
        messageNotifications,
        groupNotifications,
        callNotifications,
        systemNotifications,
        quietHoursEnabled,
        quietStartTime,
        quietEndTime
      };
      localStorage.setItem('notificationSettings', JSON.stringify(settings));
    } catch (error) {
      console.warn('Failed to save notification settings:', error);
    }
  }

  function loadDeviceStatus() {
    try {
      deviceToken = localStorage.getItem('deviceToken');
      registrationId = localStorage.getItem('registrationId');
      registrationStatus = deviceToken ? 'registered' : 'idle';
    } catch (error) {
      console.warn('Failed to load device status:', error);
    }
  }

  async function requestPermission() {
    if (!('Notification' in window)) {
      alert('This browser does not support notifications');
      return;
    }

    isLoading = true;
    try {
      const granted = await Notification.requestPermission();
      permissionStatus = granted;

      if (granted === 'granted') {
        await registerDevice();
      }
    } catch (error) {
      console.error('Permission request failed:', error);
      permissionStatus = 'denied';
    } finally {
      isLoading = false;
    }
  }

  async function registerDevice() {
    registrationStatus = 'registering';
    try {
      // Mock registration
      setTimeout(() => {
        registrationStatus = 'registered';
        deviceToken = 'mock-token';
        registrationId = 'mock-id';
        localStorage.setItem('deviceToken', deviceToken);
        localStorage.setItem('registrationId', registrationId);
        dispatch('deviceRegistered', { deviceToken, registrationId });
      }, 1000);
    } catch (error) {
      console.error('Device registration failed:', error);
      registrationStatus = 'failed';
    }
  }

  async function sendTestNotification() {
    if (permissionStatus !== 'granted' || registrationStatus !== 'registered') {
      alert('Please enable notifications and register device first');
      return;
    }

    testNotificationStatus = 'sending';
    try {
      // Create a test notification
      const notification = new Notification('Test Notification', {
        body: 'This is a test push notification from your app!',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: 'test-notification',
        requireInteraction: false,
        silent: !soundEnabled
      });

      // Auto-close after 3 seconds
      setTimeout(() => {
        notification.close();
      }, 3000);

      testNotificationStatus = 'sent';

      // Reset status after 2 seconds
      setTimeout(() => {
        testNotificationStatus = 'idle';
      }, 2000);

    } catch (error) {
      console.error('Test notification failed:', error);
      testNotificationStatus = 'failed';

      // Reset status after 2 seconds
      setTimeout(() => {
        testNotificationStatus = 'idle';
      }, 2000);
    }
  }

  function unregisterDevice() {
    try {
      localStorage.removeItem('deviceToken');
      localStorage.removeItem('registrationId');
      deviceToken = null;
      registrationId = null;
      registrationStatus = 'idle';
      dispatch('deviceUnregistered');
    } catch (error) {
      console.warn('Failed to unregister device:', error);
    }
  }

  // Auto-save settings when they change
  $: if (showPanel) {
    saveSettings();
  }

  function closePanel() {
    showPanel = false;
    onClose?.();
  }

  function getPermissionStatusText() {
    switch (permissionStatus) {
      case 'granted': return '✅ Enabled';
      case 'denied': return '❌ Blocked';
      case 'default': return '⏳ Not asked';
      case 'unsupported': return '🚫 Not supported';
      default: return 'Unknown';
    }
  }

  function getRegistrationStatusText() {
    switch (registrationStatus) {
      case 'idle': return 'Not registered';
      case 'registering': return 'Registering...';
      case 'registered': return '✅ Registered';
      case 'failed': return '❌ Failed';
      default: return 'Unknown';
    }
  }

  function isQuietHour() {
    if (!quietHoursEnabled) return false;

    const now = new Date();
    const currentTime = now.getHours() * 60 + now.getMinutes();

    const [startHour, startMin] = quietStartTime.split(':').map(Number);
    const [endHour, endMin] = quietEndTime.split(':').map(Number);

    const startMinutes = startHour * 60 + startMin;
    const endMinutes = endHour * 60 + endMin;

    if (startMinutes < endMinutes) {
      // Same day range (e.g., 22:00 to 08:00 next day)
      return currentTime >= startMinutes || currentTime <= endMinutes;
    } else {
      // Cross-day range (e.g., 22:00 to 08:00)
      return currentTime >= startMinutes && currentTime <= endMinutes;
    }
  }
</script>

{#if showPanel}
  <div
    class="notification-overlay"
    role="presentation"
    onclick={closePanel}
    onkeydown={(e) => { if (e.key === 'Escape') closePanel(); }}
  >
    <div
      class="notification-panel"
      role="dialog"
      aria-modal="true"
      tabindex="-1"
      onclick={(e) => e.stopPropagation()}
      transition:slide={{ duration: 300 }}
    >
       <!-- Header -->
       <div class="panel-header">
         <Typography variant="h2">🔔 Push Notifications</Typography>
         <Button variant="ghost" className="close-btn" onclick={closePanel}>×</Button>
       </div>

       <!-- Permission Status -->
       <div class="status-section">
         <Typography variant="h3">Permission Status</Typography>
         <div class="status-item">
           <Typography variant="body">Browser Permission: {getPermissionStatusText()}</Typography>
           {#if permissionStatus === 'default'}
             <Button
               variant="primary"
               className="action-btn"
               onclick={requestPermission}
               disabled={isLoading}
               loading={isLoading}
             >
               {isLoading ? 'Requesting...' : 'Enable Notifications'}
             </Button>
           {:else if permissionStatus === 'denied'}
             <Typography variant="caption" className="help-text">
               Notifications are blocked. Please enable them in your browser settings.
             </Typography>
           {/if}
         </div>

         <div class="status-item">
           <Typography variant="body">Device Registration: {getRegistrationStatusText()}</Typography>
           {#if registrationStatus === 'registered'}
             <Button variant="secondary" className="action-btn" onclick={unregisterDevice}>
               Unregister
             </Button>
            {:else if permissionStatus === 'granted' && registrationStatus === 'idle'}
              <Button
                variant="primary"
                className="action-btn"
                onclick={registerDevice}
              >
                Register Device
              </Button>
            {/if}
         </div>
      </div>

       <!-- Notification Settings -->
       <div class="settings-section">
         <Typography variant="h3">Notification Settings</Typography>

         <!-- Master toggle -->
         <div class="setting-item">
           <Checkbox bind:checked={notificationsEnabled} label="Enable Notifications" />
         </div>

        {#if notificationsEnabled}
           <!-- Sound -->
           <div class="setting-item">
             <Checkbox bind:checked={soundEnabled} label="Sound Effects" />
           </div>

           <!-- Notification Types -->
           <div class="setting-group">
             <Typography variant="h4">Notification Types</Typography>

             <div class="setting-item">
               <Checkbox bind:checked={messageNotifications} label="Message Notifications" />
             </div>

             <div class="setting-item">
               <Checkbox bind:checked={groupNotifications} label="Group Notifications" />
             </div>

             <div class="setting-item">
               <Checkbox bind:checked={callNotifications} label="Call Notifications" />
             </div>

             <div class="setting-item">
               <Checkbox bind:checked={systemNotifications} label="System Notifications" />
             </div>
           </div>

           <!-- Quiet Hours -->
           <div class="setting-group">
             <Typography variant="h4">Quiet Hours</Typography>

             <div class="setting-item">
               <Checkbox bind:checked={quietHoursEnabled} label="Enable Quiet Hours" />
             </div>

             {#if quietHoursEnabled}
               <div class="time-settings">
                 <div class="time-input">
                   <Typography variant="body" className="time-label">Start Time:</Typography>
                   <Input
                     type="time"
                     name="quiet-start"
                     bind:value={quietStartTime}
                   />
                 </div>

                 <div class="time-input">
                   <Typography variant="body" className="time-label">End Time:</Typography>
                   <Input
                     type="time"
                     name="quiet-end"
                     bind:value={quietEndTime}
                   />
                 </div>
               </div>

               {#if isQuietHour()}
                 <div class="quiet-notice">
                   <Typography variant="body">🔕 Quiet hours are currently active</Typography>
                 </div>
               {/if}
             {/if}
           </div>
        {/if}
      </div>

       <!-- Test Section -->
       <div class="test-section">
         <Typography variant="h3">Test Notifications</Typography>
         <Button
           variant="primary"
           className="test-btn"
           onclick={sendTestNotification}
           disabled={testNotificationStatus === 'sending' || permissionStatus !== 'granted' || registrationStatus !== 'registered'}
         >
           {#if testNotificationStatus === 'sending'}
             Sending...
           {:else if testNotificationStatus === 'sent'}
             ✅ Test Sent!
           {:else if testNotificationStatus === 'failed'}
             ❌ Failed
           {:else}
             Send Test Notification
           {/if}
         </Button>

         {#if permissionStatus !== 'granted' || registrationStatus !== 'registered'}
           <Typography variant="caption" className="help-text">
             Enable notifications and register device to test
           </Typography>
         {/if}
       </div>

       <!-- Footer -->
       <div class="panel-footer">
         <Button variant="secondary" className="close-main-btn" onclick={closePanel}>
           Close Settings
         </Button>
       </div>
    </div>
  </div>
{/if}

<style>
  .notification-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .notification-panel {
    background: var(--bg-card, #1e293b);
    border-radius: 16px;
    border: 2px solid var(--border-accent, rgba(0, 255, 255, 0.3));
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
  }

  .panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-primary);
  }

  .panel-header h2 {
    margin: 0;
    color: var(--text-primary);
    font-size: 1.25rem;
  }

  .close-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
  }

  .close-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
  }

  .status-section, .settings-section, .test-section {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-primary);
  }

  .status-section:last-child,
  .settings-section:last-child,
  .test-section:last-child {
    border-bottom: none;
  }

  .status-section h3, .settings-section h3, .test-section h3 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
    font-size: 1.1rem;
  }

  .status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-primary);
  }

  .status-item:last-child {
    border-bottom: none;
  }

  .status-item span {
    color: var(--text-primary);
    font-weight: 500;
  }

  .setting-item {
    margin-bottom: 1rem;
  }

  .toggle-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    user-select: none;
    color: var(--text-primary);
  }

  .toggle-label input[type="checkbox"] {
    display: none;
  }

  .toggle-slider {
    width: 44px;
    height: 24px;
    background: var(--border-secondary);
    border-radius: 12px;
    position: relative;
    transition: background 0.3s ease;
  }

  .toggle-slider::before {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
  }

  .toggle-label input:checked + .toggle-slider {
    background: var(--accent-primary);
  }

  .toggle-label input:checked + .toggle-slider::before {
    transform: translateX(20px);
  }

  .setting-group {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-primary);
  }

  .setting-group h4 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
    font-size: 1rem;
  }

  .time-settings {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-top: 1rem;
  }

  .time-input {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .time-input label {
    font-size: 0.9rem;
    color: var(--text-secondary);
  }

  .time-input input {
    padding: 0.5rem;
    border: 1px solid var(--border-primary);
    border-radius: 6px;
    background: var(--bg-input);
    color: var(--text-primary);
  }

  .quiet-notice {
    margin-top: 1rem;
    padding: 0.75rem;
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid var(--border-accent);
    border-radius: 6px;
    color: var(--text-primary);
    font-size: 0.9rem;
  }

  .action-btn, .test-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .action-btn.primary, .test-btn {
    background: var(--accent-primary);
    color: white;
  }

  .action-btn.primary:hover, .test-btn:hover {
    background: var(--hover-primary);
    transform: translateY(-1px);
  }

  .action-btn.secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 1px solid var(--border-primary);
  }

  .action-btn.secondary:hover {
    background: var(--bg-tertiary);
  }

  .action-btn:disabled, .test-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  .help-text {
    display: block;
    margin-top: 0.5rem;
    color: var(--text-muted);
    font-size: 0.8rem;
  }

  .panel-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--border-primary);
    text-align: center;
  }

  .close-main-btn {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 1px solid var(--border-primary);
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .close-main-btn:hover {
    background: var(--bg-tertiary);
  }

  /* Cyberpunk theme special effects */
  :global([data-theme="cyberpunk"]) .notification-panel {
    border-color: var(--border-accent);
    box-shadow: 0 0 40px rgba(0, 255, 255, 0.3);
  }

  :global([data-theme="cyberpunk"]) .toggle-label input:checked + .toggle-slider {
    background: var(--gradient-cyber);
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
  }

  :global([data-theme="cyberpunk"]) .action-btn.primary, :global([data-theme="cyberpunk"]) .test-btn {
    background: var(--gradient-cyber);
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
  }

  /* Mobile responsive */
  @media (max-width: 480px) {
    .notification-panel {
      margin: 0.5rem;
      max-width: none;
    }

    .panel-header {
      padding: 1rem;
    }

    .status-section, .settings-section, .test-section {
      padding: 1rem;
    }

    .time-settings {
      grid-template-columns: 1fr;
      gap: 0.5rem;
    }

    .panel-footer {
      padding: 1rem;
    }
  }
</style>