<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { userStore } from '$lib/stores';

  // Settings state
  let emailNotifications = true;
  let pushNotifications = false;
  let smsNotifications = false;
  let bookingReminders = true;
  let priceAlerts = true;
  let promotionalEmails = false;
  let travelDeals = true;
  let newsletter = false;

  // Preferences
  let currency = 'USD';
  let language = 'en';
  let timezone = 'America/New_York';
  let dateFormat = 'MM/DD/YYYY';
  let temperature = '°F';
  let distance = 'miles';

  // Privacy
  let shareData = false;
  let personalizedAds = false;
  let analytics = true;

  // Account
  let twoFactorAuth = false;
  let loginAlerts = true;

  // Saved preferences
  let savedCards: any[] = [];
  let savedAddresses: any[] = [];

  let saving = false;

  onMount(() => {
    loadSettings();
  });

  function loadSettings() {
    try {
      const saved = localStorage.getItem('user_settings');
      if (saved) {
        const settings = JSON.parse(saved);
        emailNotifications = settings.emailNotifications ?? true;
        pushNotifications = settings.pushNotifications ?? false;
        smsNotifications = settings.smsNotifications ?? false;
        bookingReminders = settings.bookingReminders ?? true;
        priceAlerts = settings.priceAlerts ?? true;
        promotionalEmails = settings.promotionalEmails ?? false;
        travelDeals = settings.travelDeals ?? true;
        newsletter = settings.newsletter ?? false;
        currency = settings.currency ?? 'USD';
        language = settings.language ?? 'en';
        timezone = settings.timezone ?? 'America/New_York';
        dateFormat = settings.dateFormat ?? 'MM/DD/YYYY';
        temperature = settings.temperature ?? '°F';
        distance = settings.distance ?? 'miles';
        shareData = settings.shareData ?? false;
        personalizedAds = settings.personalizedAds ?? false;
        analytics = settings.analytics ?? true;
        twoFactorAuth = settings.twoFactorAuth ?? false;
        loginAlerts = settings.loginAlerts ?? true;
      }

      // Load saved payment methods
      const cards = localStorage.getItem('saved_cards');
      if (cards) {
        savedCards = JSON.parse(cards);
      }

      const addresses = localStorage.getItem('saved_addresses');
      if (addresses) {
        savedAddresses = JSON.parse(addresses);
      }
    } catch (err) {
      console.error('Failed to load settings:', err);
    }
  }

  async function saveSettings() {
    saving = true;
    try {
      const settings = {
        emailNotifications,
        pushNotifications,
        smsNotifications,
        bookingReminders,
        priceAlerts,
        promotionalEmails,
        travelDeals,
        newsletter,
        currency,
        language,
        timezone,
        dateFormat,
        temperature,
        distance,
        shareData,
        personalizedAds,
        analytics,
        twoFactorAuth,
        loginAlerts
      };
      localStorage.setItem('user_settings', JSON.stringify(settings));
      alert('Settings saved successfully!');
    } catch (err) {
      alert('Failed to save settings');
    } finally {
      saving = false;
    }
  }

  function exportData() {
    const data = {
      settings: JSON.parse(localStorage.getItem('user_settings') || '{}'),
      wishlist: JSON.parse(localStorage.getItem('wishlist') || '[]'),
      bookings: JSON.parse(localStorage.getItem('user_bookings') || '[]'),
      exportedAt: new Date().toISOString()
    };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'travel-app-data.json';
    a.click();
    URL.revokeObjectURL(url);
  }

  function deleteAccount() {
    if (!confirm('Are you sure you want to delete your account? This action cannot be undone.')) return;
    if (!confirm('All your data will be permanently deleted. Continue?')) return;
    
    localStorage.clear();
    goto('/auth');
  }
</script>

<svelte:head>
  <title>Settings - Travel App</title>
</svelte:head>

<div class="settings-page">
  <header class="header">
    <div class="header-content">
      <button class="back-btn" onclick={() => history.back()}>
        ← Back
      </button>
      <h1>⚙️ Settings</h1>
    </div>
  </header>

  <main class="main-content">
    <!-- Notifications -->
    <section class="settings-section">
      <h2>🔔 Notifications</h2>
      
      <div class="setting-item">
        <div class="setting-info">
          <h3>Email Notifications</h3>
          <p>Receive booking confirmations and updates via email</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={emailNotifications} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Push Notifications</h3>
          <p>Get instant alerts on your device</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={pushNotifications} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>SMS Notifications</h3>
          <p>Receive text messages for important updates</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={smsNotifications} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Booking Reminders</h3>
          <p>Get reminded about upcoming trips</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={bookingReminders} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Price Alerts</h3>
          <p>Notify when prices drop for saved items</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={priceAlerts} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Promotional Emails</h3>
          <p>Receive special offers and promotions</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={promotionalEmails} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Travel Deals</h3>
          <p>Get notified about exclusive travel deals</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={travelDeals} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Newsletter</h3>
          <p>Weekly travel inspiration and tips</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={newsletter} />
          <span class="slider"></span>
        </label>
      </div>
    </section>

    <!-- Preferences -->
    <section class="settings-section">
      <h2>🌍 Preferences</h2>
      
      <div class="setting-item">
        <div class="setting-info">
          <h3>Currency</h3>
          <p>Default currency for prices</p>
        </div>
        <select bind:value={currency}>
          <option value="USD">USD ($)</option>
          <option value="EUR">EUR (€)</option>
          <option value="GBP">GBP (£)</option>
          <option value="JPY">JPY (¥)</option>
          <option value="VND">VND (₫)</option>
        </select>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Language</h3>
          <p>App language</p>
        </div>
        <select bind:value={language}>
          <option value="en">English</option>
          <option value="vi">Tiếng Việt</option>
          <option value="zh">中文</option>
          <option value="ja">日本語</option>
          <option value="ko">한국어</option>
        </select>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Timezone</h3>
          <p>Your local timezone</p>
        </div>
        <select bind:value={timezone}>
          <option value="America/New_York">Eastern Time (ET)</option>
          <option value="America/Chicago">Central Time (CT)</option>
          <option value="America/Denver">Mountain Time (MT)</option>
          <option value="America/Los_Angeles">Pacific Time (PT)</option>
          <option value="Europe/London">London (GMT)</option>
          <option value="Europe/Paris">Paris (CET)</option>
          <option value="Asia/Tokyo">Tokyo (JST)</option>
          <option value="Asia/Ho_Chi_Minh">Ho Chi Minh (ICT)</option>
        </select>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Date Format</h3>
          <p>How dates are displayed</p>
        </div>
        <select bind:value={dateFormat}>
          <option value="MM/DD/YYYY">MM/DD/YYYY</option>
          <option value="DD/MM/YYYY">DD/MM/YYYY</option>
          <option value="YYYY-MM-DD">YYYY-MM-DD</option>
        </select>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Temperature Unit</h3>
          <p>Weather temperature display</p>
        </div>
        <select bind:value={temperature}>
          <option value="°F">Fahrenheit (°F)</option>
          <option value="°C">Celsius (°C)</option>
        </select>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Distance Unit</h3>
          <p>Distance measurements</p>
        </div>
        <select bind:value={distance}>
          <option value="miles">Miles</option>
          <option value="km">Kilometers</option>
        </select>
      </div>
    </section>

    <!-- Privacy -->
    <section class="settings-section">
      <h2>🔒 Privacy & Security</h2>
      
      <div class="setting-item">
        <div class="setting-info">
          <h3>Share Usage Data</h3>
          <p>Help us improve by sharing anonymous usage data</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={shareData} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Personalized Ads</h3>
          <p>Show ads based on your interests</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={personalizedAds} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Analytics</h3>
          <p>Help us understand app performance</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={analytics} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Two-Factor Authentication</h3>
          <p>Add an extra layer of security</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={twoFactorAuth} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <h3>Login Alerts</h3>
          <p>Get notified of new sign-ins</p>
        </div>
        <label class="toggle">
          <input type="checkbox" bind:checked={loginAlerts} />
          <span class="slider"></span>
        </label>
      </div>
    </section>

    <!-- Saved Payment Methods -->
    <section class="settings-section">
      <h2>💳 Saved Payment Methods</h2>
      
      {#if savedCards.length === 0}
        <p class="empty-text">No saved cards</p>
      {:else}
        {#each savedCards as card}
          <div class="saved-item">
            <span class="card-icon">💳</span>
            <div class="card-info">
              <p>•••• •••• •••• {card.last4}</p>
              <span class="card-expiry">Expires {card.expiry}</span>
            </div>
            <button class="remove-btn">Remove</button>
          </div>
        {/each}
      {/if}
    </section>

    <!-- Data Management -->
    <section class="settings-section">
      <h2>📁 Data Management</h2>
      
      <button class="action-btn" onclick={exportData}>
        📥 Export My Data
      </button>
      
      <button class="action-btn danger" onclick={deleteAccount}>
        🗑️ Delete My Account
      </button>
    </section>

    <!-- Save Button -->
    <button class="save-btn" onclick={saveSettings} disabled={saving}>
      {saving ? 'Saving...' : 'Save Settings'}
    </button>
  </main>
</div>

<style>
  .settings-page {
    min-height: 100vh;
    background: #f8f9fa;
  }

  .header {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }

  .header-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .back-btn {
    background: none;
    border: none;
    font-size: 1rem;
    color: #666;
    cursor: pointer;
  }

  h1 {
    font-size: 1.25rem;
    margin: 0;
  }

  .main-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
  }

  .settings-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .settings-section h2 {
    font-size: 1.125rem;
    color: #1a1a2e;
    margin: 0 0 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #eee;
  }

  .setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f5f5f5;
  }

  .setting-item:last-child {
    border-bottom: none;
  }

  .setting-info h3 {
    font-size: 1rem;
    font-weight: 500;
    color: #1a1a2e;
    margin: 0 0 0.25rem;
  }

  .setting-info p {
    font-size: 0.875rem;
    color: #666;
    margin: 0;
  }

  .toggle {
    position: relative;
    width: 50px;
    height: 28px;
    cursor: pointer;
  }

  .toggle input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #ccc;
    border-radius: 28px;
    transition: 0.3s;
  }

  .slider:before {
    content: '';
    position: absolute;
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: 0.3s;
  }

  .toggle input:checked + .slider {
    background: #667eea;
  }

  .toggle input:checked + .slider:before {
    transform: translateX(22px);
  }

  select {
    padding: 0.5rem 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 0.875rem;
    background: white;
    cursor: pointer;
  }

  .saved-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 0.5rem;
  }

  .card-icon {
    font-size: 1.5rem;
  }

  .card-info {
    flex: 1;
  }

  .card-info p {
    margin: 0;
    font-weight: 500;
  }

  .card-expiry {
    font-size: 0.75rem;
    color: #666;
  }

  .remove-btn {
    background: none;
    border: none;
    color: #ff4757;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .empty-text {
    color: #999;
    font-style: italic;
  }

  .action-btn {
    width: 100%;
    padding: 1rem;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    margin-bottom: 0.75rem;
    text-align: left;
  }

  .action-btn:hover {
    background: #eee;
  }

  .action-btn.danger {
    color: #ff4757;
    border-color: #ff4757;
  }

  .action-btn.danger:hover {
    background: #fff5f5;
  }

  .save-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 1rem;
  }

  .save-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
</style>
