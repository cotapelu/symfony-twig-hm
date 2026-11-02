/**
 * Native Features Demo Component
 * Shows how to use all Capacitor plugins in a Svelte component
 */

<script lang="ts">
  import {
    camera,
    geolocation,
    pushNotifications,
    localNotifications,
    device,
    network,
    filesystem,
    share,
    haptics,
    clipboard,
    app,
    browser,
    preferences,
    native,
  } from '$lib/services/native';

  import {
    networkStore,
    geolocationStore,
    deviceStore,
    appStateStore,
    preferencesStore,
    capabilitiesStore,
  } from '$lib/services/native/stores';

  // Reactive state
  let photoPreview = $state<string | null>(null);
  let position = $state<any>(null);
  let deviceInfo = $state<any>(null);
  let networkStatus = $state<any>({ connected: true, connectionType: 'unknown' });
  let appState = $state<any>({ isActive: true, state: 'active' });
  let capabilities = $state<Record<string, boolean>>({});
  let notifications = $state<string[]>([]);
  let clipboardText = $state('');

  // Subscribe to stores
  $effect(() => networkStore.subscribe((v) => (networkStatus = v)));
  $effect(() => geolocationStore.subscribe((v) => (position = v?.position)));
  $effect(() => deviceStore.subscribe((v) => (deviceInfo = v?.info)));
  $effect(() => appStateStore.subscribe((v) => (appState = v)));
  $effect(() => capabilitiesStore.subscribe((v) => (capabilities = v)));

  // Check capabilities on mount
  async function checkCapabilities() {
    const caps = await capabilitiesStore.check();
    capabilities = caps;
  }

  // Camera
  async function takePhoto() {
    try {
      const photo = await camera.takePhoto({ quality: 80, resultType: 'uri' });
      if (photo?.webPath) {
        photoPreview = photo.webPath;
        await haptics.success();
      }
    } catch (error) {
      console.error('Camera error:', error);
    }
  }

  async function pickFromGallery() {
    try {
      const photo = await camera.pickFromGallery({ quality: 80, resultType: 'uri' });
      if (photo?.webPath) {
        photoPreview = photo.webPath;
      }
    } catch (error) {
      console.error('Gallery error:', error);
    }
  }

  // Geolocation
  async function getLocation() {
    try {
      const pos = await geolocation.getCurrentPosition();
      position = pos;
      await haptics.light();
    } catch (error) {
      console.error('Geolocation error:', error);
    }
  }

  async function startWatching() {
    try {
      await geolocationStore.watch();
      await haptics.medium();
    } catch (error) {
      console.error('Watch error:', error);
    }
  }

  async function stopWatching() {
    await geolocationStore.stop();
  }

  // Notifications
  async function requestNotificationPerms() {
    await localNotifications.requestPermissions();
    await pushNotifications.requestPermissions();
    await checkCapabilities();
    await haptics.success();
  }

  async function sendLocalNotification() {
    await localNotifications.showNow('Test Notification', 'This is a local notification!', {
      id: Date.now(),
      extra: { test: true },
    });
    await haptics.light();
  }

  async function scheduleNotification() {
    const future = new Date(Date.now() + 5000);
    await localNotifications.schedule({
      title: 'Scheduled Notification',
      body: 'This was scheduled 5 seconds ago',
      id: Date.now(),
      schedule: { at: future },
    });
    await haptics.medium();
  }

  // Push Notifications
  async function initPush() {
    await pushNotifications.initialize();
    pushNotifications.onRegistration((token) => {
      notifications.push(`Token: ${token.value}`);
    });
    pushNotifications.onPushReceived((notification) => {
      notifications.push(`Received: ${notification.title}`);
    });
    await haptics.success();
  }

  // Device Info
  async function refreshDeviceInfo() {
    await deviceStore.refresh();
  }

  // Network
  async function refreshNetwork() {
    await networkStore.refresh();
  }

  // Filesystem
  async function testFilesystem() {
    try {
      const testContent = `Test file created at ${new Date().toISOString()}`;
      await filesystem.writeFile({
        path: 'test.txt',
        data: testContent,
        directory: 'data',
      });
      const readContent = await filesystem.readFile({ path: 'test.txt', directory: 'data' });
      notifications.push(`File read: ${readContent}`);
      await haptics.success();
    } catch (error) {
      notifications.push(`Filesystem error: ${error}`);
      await haptics.error();
    }
  }

  // Share
  async function testShare() {
    try {
      await share.shareText('Hello from CommunityApp!', 'Share Test');
      await haptics.light();
    } catch (error) {
      notifications.push(`Share error: ${error}`);
    }
  }

  // Haptics
  async function testHaptics(type: 'light' | 'medium' | 'heavy' | 'success' | 'warning' | 'error') {
    switch (type) {
      case 'light': await haptics.light(); break;
      case 'medium': await haptics.medium(); break;
      case 'heavy': await haptics.heavy(); break;
      case 'success': await haptics.success(); break;
      case 'warning': await haptics.warning(); break;
      case 'error': await haptics.error(); break;
    }
  }

  // Clipboard
  async function copyToClipboard() {
    await clipboard.write(`Copied at ${new Date().toISOString()}`);
    await haptics.light();
  }

  async function pasteFromClipboard() {
    try {
      clipboardText = await clipboard.read();
      await haptics.light();
    } catch (error) {
      clipboardText = 'Error reading clipboard';
    }
  }

  // Preferences
  async function setPreference() {
    const key = 'test_key';
    const value = `Value at ${new Date().toISOString()}`;
    await preferences.set(key, value);
    await preferencesStore.loadAll();
    await haptics.light();
  }

  async function getPreference() {
    const value = await preferences.get('test_key');
    notifications.push(`Preference: ${value}`);
  }

  // Browser
  async function openBrowser() {
    await browser.open('https://capacitorjs.com');
  }

  async function openInAppBrowser() {
    await browser.openInApp('https://svelte.dev');
  }

  // App
  async function exitApp() {
    await app.exit();
  }
</script>

<div class="demo-container">
  <header class="demo-header">
    <h1>📱 Native Features Demo</h1>
    <p class="platform-badge">
      Platform: {native.platform()} {native.isNative() ? '✅ Native' : '🌐 Web (PWA)'}
    </p>
  </header>

  <section class="section">
    <h2>🎯 Capabilities</h2>
    <button class="btn" onclick={checkCapabilities}>Check Capabilities</button>
    <div class="capabilities-grid">
      {#each Object.entries(capabilities) as [key, value]}
        <div class="capability-item {value ? 'granted' : 'denied'}">
          <span class="cap-name">{key}</span>
          <span class="cap-status">{value ? '✅' : '❌'}</span>
        </div>
      {/each}
    </div>
  </section>

  <section class="section">
    <h2>📷 Camera</h2>
    <div class="btn-group">
      <button class="btn" onclick={takePhoto}>📸 Take Photo</button>
      <button class="btn" onclick={pickFromGallery}>🖼️ Pick from Gallery</button>
    </div>
    {#if photoPreview}
      <img src={photoPreview} alt="Preview" class="photo-preview" />
    {/if}
  </section>

  <section class="section">
    <h2>📍 Geolocation</h2>
    <div class="btn-group">
      <button class="btn" onclick={getLocation}>📍 Get Location</button>
      <button class="btn" onclick={startWatching}>👁️ Start Watching</button>
      <button class="btn" onclick={stopWatching}>⏹️ Stop Watching</button>
    </div>
    {#if position}
      <pre class="json-output">{JSON.stringify(position, null, 2)}</pre>
    {/if}
  </section>

  <section class="section">
    <h2>🔔 Notifications</h2>
    <div class="btn-group">
      <button class="btn" onclick={requestNotificationPerms}>🔐 Request Permissions</button>
      <button class="btn" onclick={sendLocalNotification}>📢 Show Now</button>
      <button class="btn" onclick={scheduleNotification}>⏰ Schedule (5s)</button>
      <button class="btn" onclick={initPush}>📥 Init Push</button>
    </div>
    <div class="notifications">
      {#each notifications as note}
        <div class="notification-item">{note}</div>
      {/each}
    </div>
  </section>

  <section class="section">
    <h2>📱 Device Info</h2>
    <button class="btn" onclick={refreshDeviceInfo}>🔄 Refresh</button>
    {#if deviceInfo}
      <pre class="json-output">{JSON.stringify(deviceInfo, null, 2)}</pre>
    {/if}
  </section>

  <section class="section">
    <h2>🌐 Network</h2>
    <button class="btn" onclick={refreshNetwork}>🔄 Refresh</button>
    <div class="status-badge {networkStatus.connected ? 'online' : 'offline'}">
      {networkStatus.connected ? '🟢 Online' : '🔴 Offline'} - {networkStatus.connectionType}
    </div>
  </section>

  <section class="section">
    <h2>📱 App State</h2>
    <div class="status-badge {appState.isActive ? 'active' : 'background'}">
      {appState.isActive ? '🟢 Active' : '🟡 Background'} - {appState.state}
    </div>
  </section>

  <section class="section">
    <h2>💾 Filesystem</h2>
    <button class="btn" onclick={testFilesystem}>📝 Write & Read Test File</button>
  </section>

  <section class="section">
    <h2>🔗 Share</h2>
    <button class="btn" onclick={testShare}>📤 Share Text</button>
  </section>

  <section class="section">
    <h2>📳 Haptics</h2>
    <div class="btn-group">
      <button class="btn" onclick={() => testHaptics('light')}>Light</button>
      <button class="btn" onclick={() => testHaptics('medium')}>Medium</button>
      <button class="btn" onclick={() => testHaptics('heavy')}>Heavy</button>
      <button class="btn" onclick={() => testHaptics('success')}>✅ Success</button>
      <button class="btn" onclick={() => testHaptics('warning')}>⚠️ Warning</button>
      <button class="btn" onclick={() => testHaptics('error')}>❌ Error</button>
    </div>
  </section>

  <section class="section">
    <h2>📋 Clipboard</h2>
    <div class="btn-group">
      <button class="btn" onclick={copyToClipboard}>📋 Copy</button>
      <button class="btn" onclick={pasteFromClipboard}>📄 Paste</button>
    </div>
    <input bind:value={clipboardText} placeholder="Clipboard content" class="input" />
  </section>

  <section class="section">
    <h2>⚙️ Preferences</h2>
    <div class="btn-group">
      <button class="btn" onclick={setPreference}>💾 Set</button>
      <button class="btn" onclick={getPreference}>📖 Get</button>
    </div>
  </section>

  <section class="section">
    <h2>🌐 Browser</h2>
    <div class="btn-group">
      <button class="btn" onclick={openBrowser}>🔗 System Browser</button>
      <button class="btn" onclick={openInAppBrowser}>📱 In-App Browser</button>
    </div>
  </section>

  <section class="section danger">
    <h2>⚠️ Danger Zone</h2>
    <button class="btn danger" onclick={exitApp}>🚪 Exit App</button>
  </section>
</div>

<style>
  .demo-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 1rem;
    font-family: system-ui, sans-serif;
  }

  .demo-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
  }

  .platform-badge {
    font-size: 0.9rem;
    opacity: 0.9;
  }

  .section {
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }

  .section.danger {
    border: 2px solid #ff4444;
  }

  .section h2 {
    margin: 0 0 1rem;
    font-size: 1.1rem;
    color: var(--text-primary, #333);
  }

  .btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .btn {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    background: #667eea;
    color: white;
  }

  .btn:hover {
    background: #5568d3;
    transform: translateY(-1px);
  }

  .btn:active {
    transform: translateY(0);
  }

  .btn.danger {
    background: #ff4444;
  }

  .btn.danger:hover {
    background: #cc0000;
  }

  .capabilities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.5rem;
  }

  .capability-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem;
    border-radius: 8px;
    background: var(--bg-secondary, #f5f5f5);
  }

  .capability-item.granted {
    background: #e8f5e9;
  }

  .capability-item.denied {
    background: #ffebee;
  }

  .photo-preview {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .json-output {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 1rem;
    border-radius: 8px;
    overflow-x: auto;
    font-size: 0.75rem;
    max-height: 300px;
  }

  .status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
  }

  .status-badge.online,
  .status-badge.active {
    background: #e8f5e9;
    color: #2e7d32;
  }

  .status-badge.offline,
  .status-badge.background {
    background: #fff3e0;
    color: #e65100;
  }

  .notifications {
    max-height: 200px;
    overflow-y: auto;
  }

  .notification-item {
    padding: 0.5rem;
    margin: 0.25rem 0;
    background: var(--bg-secondary, #f5f5f5);
    border-radius: 6px;
    font-size: 0.85rem;
  }

  .input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color, #ddd);
    border-radius: 8px;
    font-size: 0.9rem;
  }
</style>