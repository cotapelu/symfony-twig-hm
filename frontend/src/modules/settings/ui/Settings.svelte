<script lang="ts">
	import { onMount } from 'svelte';
	import Card from '$lib/components/molecules/Card.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Input from '$lib/components/atoms/Input.svelte';
	import Switch from '$lib/components/atoms/Switch.svelte';
	import Select from '$lib/components/atoms/Select.svelte';
	import Tabs from '$lib/components/Tabs.svelte';
	import Icon from '$lib/components/atoms/Icon.svelte';
	import ThemeSwitcher from '$lib/components/ThemeSwitcher.svelte';
	import ThemeToggle from '$lib/components/ThemeToggle.svelte';
	import { showToast } from '$lib/stores/global';

	type Theme = 'light' | 'dark' | 'cyberpunk';

	let activeTab = 'appearance';
	let saving = false;

	let settings = {
		appearance: {
			theme: 'cyberpunk' as Theme,
			compactMode: false,
			animations: true,
			highContrast: false
		},
		notifications: {
			email: true,
			push: true,
			sms: false,
			marketing: false,
			quietHours: false,
			quietHoursStart: '22:00',
			quietHoursEnd: '07:00'
		},
		privacy: {
			showOnlineStatus: true,
			showReadReceipts: true,
			allowSearchByEmail: true,
			allowFriendRequests: true,
			dataCollection: false
		},
		language: {
			current: 'vi',
			autoDetect: false,
			dateFormat: 'DD/MM/YYYY',
			timezone: 'Asia/Ho_Chi_Minh'
		},
		accessibility: {
			fontSize: 'medium',
			reduceMotion: false,
			screenReader: false,
			keyboardNavigation: true
		}
	};

	const tabs = [
		{ id: 'appearance', label: 'Appearance', icon: '🎨' },
		{ id: 'notifications', label: 'Notifications', icon: '🔔' },
		{ id: 'privacy', label: 'Privacy', icon: '🔒' },
		{ id: 'language', label: 'Language', icon: '🌐' },
		{ id: 'accessibility', label: 'Accessibility', icon: '♿' }
	];

	const languages = [
		{ value: 'vi', label: 'Tiếng Việt' },
		{ value: 'en', label: 'English' },
		{ value: 'ja', label: '日本語' },
		{ value: 'ko', label: '한국어' },
		{ value: 'zh', label: '中文' }
	];

	const timezones = [
		{ value: 'Asia/Ho_Chi_Minh', label: 'Ho Chi Minh City (UTC+7)' },
		{ value: 'Asia/Hanoi', label: 'Hanoi (UTC+7)' },
		{ value: 'Asia/Tokyo', label: 'Tokyo (UTC+9)' },
		{ value: 'Asia/Seoul', label: 'Seoul (UTC+9)' },
		{ value: 'Europe/London', label: 'London (UTC+0)' },
		{ value: 'America/New_York', label: 'New York (UTC-5)' }
	];

	const fontSizes = [
		{ value: 'small', label: 'Small' },
		{ value: 'medium', label: 'Medium' },
		{ value: 'large', label: 'Large' },
		{ value: 'xlarge', label: 'Extra Large' }
	];

	const dateFormats = [
		{ value: 'DD/MM/YYYY', label: 'DD/MM/YYYY' },
		{ value: 'MM/DD/YYYY', label: 'MM/DD/YYYY' },
		{ value: 'YYYY-MM-DD', label: 'YYYY-MM-DD' }
	];

	onMount(() => {
		loadSettings();
	});

	function loadSettings() {
		if (typeof localStorage !== 'undefined') {
			try {
				const saved = localStorage.getItem('userSettings');
				if (saved) {
					settings = { ...settings, ...JSON.parse(saved) };
				}
			} catch (e) {
				console.warn('Failed to load settings');
			}
		}
	}

	async function saveSettings() {
		saving = true;
		try {
			await new Promise(resolve => setTimeout(resolve, 800));
			if (typeof localStorage !== 'undefined') {
				localStorage.setItem('userSettings', JSON.stringify(settings));
			}
			showToast('Settings saved successfully', 'success');
		} catch (err) {
			showToast('Failed to save settings', 'error');
		} finally {
			saving = false;
		}
	}

	function resetSettings() {
		settings = {
			appearance: { theme: 'cyberpunk', compactMode: false, animations: true, highContrast: false },
			notifications: { email: true, push: true, sms: false, marketing: false, quietHours: false, quietHoursStart: '22:00', quietHoursEnd: '07:00' },
			privacy: { showOnlineStatus: true, showReadReceipts: true, allowSearchByEmail: true, allowFriendRequests: true, dataCollection: false },
			language: { current: 'vi', autoDetect: false, dateFormat: 'DD/MM/YYYY', timezone: 'Asia/Ho_Chi_Minh' },
			accessibility: { fontSize: 'medium', reduceMotion: false, screenReader: false, keyboardNavigation: true }
		};
		showToast('Settings reset to defaults', 'success');
	}

	function exportData() {
		const dataStr = JSON.stringify(settings, null, 2);
		const blob = new Blob([dataStr], { type: 'application/json' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = 'settings-export.json';
		a.click();
		URL.revokeObjectURL(url);
		showToast('Settings exported', 'success');
	}
</script>

<div class="settings-page">
	<div class="settings-header">
		<Typography variant="h2">Settings</Typography>
		<Typography variant="body" color="secondary">Manage your preferences and account settings</Typography>
	</div>

	<Tabs {tabs} bind:activeTab variant="pills" />

	<div class="settings-content">
		{#if activeTab === 'appearance'}
			<Card>
				<div class="section-header">
					<Typography variant="h4">Theme</Typography>
				</div>
				<div class="theme-options">
					<button
						class="theme-option"
						class:active={settings.appearance.theme === 'cyberpunk'}
						onclick={() => settings.appearance.theme = 'cyberpunk'}
					>
						<div class="theme-preview cyberpunk">
							<div class="preview-bar"></div>
							<div class="preview-content"></div>
						</div>
						<Typography variant="body" fontWeight="medium">Cyberpunk</Typography>
					</button>
					<button
						class="theme-option"
						class:active={settings.appearance.theme === 'dark'}
						onclick={() => settings.appearance.theme = 'dark'}
					>
						<div class="theme-preview dark">
							<div class="preview-bar"></div>
							<div class="preview-content"></div>
						</div>
						<Typography variant="body" fontWeight="medium">Dark</Typography>
					</button>
					<button
						class="theme-option"
						class:active={settings.appearance.theme === 'light'}
						onclick={() => settings.appearance.theme = 'light'}
					>
						<div class="theme-preview light">
							<div class="preview-bar"></div>
							<div class="preview-content"></div>
						</div>
						<Typography variant="body" fontWeight="medium">Light</Typography>
					</button>
				</div>
			</Card>

			<Card>
				<div class="section-header">
					<Typography variant="h4">Display</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Compact Mode</Typography>
							<Typography variant="caption" color="secondary">Reduce spacing and padding</Typography>
						</div>
						<Switch checked={settings.appearance.compactMode} onchange={() => settings.appearance.compactMode = !settings.appearance.compactMode} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Animations</Typography>
							<Typography variant="caption" color="secondary">Enable UI animations and transitions</Typography>
						</div>
						<Switch checked={settings.appearance.animations} onchange={() => settings.appearance.animations = !settings.appearance.animations} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">High Contrast</Typography>
							<Typography variant="caption" color="secondary">Increase color contrast for accessibility</Typography>
						</div>
						<Switch checked={settings.appearance.highContrast} onchange={() => settings.appearance.highContrast = !settings.appearance.highContrast} />
					</div>
				</div>
			</Card>

		{:else if activeTab === 'notifications'}
			<Card>
				<div class="section-header">
					<Typography variant="h4">Notification Channels</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Email Notifications</Typography>
							<Typography variant="caption" color="secondary">Receive updates via email</Typography>
						</div>
						<Switch checked={settings.notifications.email} onchange={() => settings.notifications.email = !settings.notifications.email} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Push Notifications</Typography>
							<Typography variant="caption" color="secondary">Browser and mobile push notifications</Typography>
						</div>
						<Switch checked={settings.notifications.push} onchange={() => settings.notifications.push = !settings.notifications.push} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">SMS Notifications</Typography>
							<Typography variant="caption" color="secondary">Receive text messages for urgent alerts</Typography>
						</div>
						<Switch checked={settings.notifications.sms} onchange={() => settings.notifications.sms = !settings.notifications.sms} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Marketing</Typography>
							<Typography variant="caption" color="secondary">Promotional content and offers</Typography>
						</div>
						<Switch checked={settings.notifications.marketing} onchange={() => settings.notifications.marketing = !settings.notifications.marketing} />
					</div>
				</div>
			</Card>

			<Card>
				<div class="section-header">
					<Typography variant="h4">Quiet Hours</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Enable Quiet Hours</Typography>
							<Typography variant="caption" color="secondary">Mute notifications during specified hours</Typography>
						</div>
						<Switch checked={settings.notifications.quietHours} onchange={() => settings.notifications.quietHours = !settings.notifications.quietHours} />
					</div>
					{#if settings.notifications.quietHours}
						<div class="quiet-hours-inputs">
							<div class="form-group">
								<label>From</label>
								<Input type="time" bind:value={settings.notifications.quietHoursStart} />
							</div>
							<div class="form-group">
								<label>To</label>
								<Input type="time" bind:value={settings.notifications.quietHoursEnd} />
							</div>
						</div>
					{/if}
				</div>
			</Card>

		{:else if activeTab === 'privacy'}
			<Card>
				<div class="section-header">
					<Typography variant="h4">Activity Status</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Show Online Status</Typography>
							<Typography variant="caption" color="secondary">Let others see when you're online</Typography>
						</div>
						<Switch checked={settings.privacy.showOnlineStatus} onchange={() => settings.privacy.showOnlineStatus = !settings.privacy.showOnlineStatus} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Show Read Receipts</Typography>
							<Typography variant="caption" color="secondary">Display when messages have been read</Typography>
						</div>
						<Switch checked={settings.privacy.showReadReceipts} onchange={() => settings.privacy.showReadReceipts = !settings.privacy.showReadReceipts} />
					</div>
				</div>
			</Card>

			<Card>
				<div class="section-header">
					<Typography variant="h4">Discovery</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Search by Email</Typography>
							<Typography variant="caption" color="secondary">Allow others to find you by email</Typography>
						</div>
						<Switch checked={settings.privacy.allowSearchByEmail} onchange={() => settings.privacy.allowSearchByEmail = !settings.privacy.allowSearchByEmail} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Friend Requests</Typography>
							<Typography variant="caption" color="secondary">Allow friend requests from anyone</Typography>
						</div>
						<Switch checked={settings.privacy.allowFriendRequests} onchange={() => settings.privacy.allowFriendRequests = !settings.privacy.allowFriendRequests} />
					</div>
				</div>
			</Card>

			<Card variant="danger">
				<div class="section-header">
					<Typography variant="h4" color="error">Data & Privacy</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Data Collection</Typography>
							<Typography variant="caption" color="secondary">Help improve the app by sharing usage data</Typography>
						</div>
						<Switch checked={settings.privacy.dataCollection} onchange={() => settings.privacy.dataCollection = !settings.privacy.dataCollection} />
					</div>
				</div>
				<div class="danger-actions">
					<Button variant="danger">
						<Icon name="download" /> Download My Data
					</Button>
					<Button variant="danger">
						<Icon name="trash" /> Delete Account
					</Button>
				</div>
			</Card>

		{:else if activeTab === 'language'}
			<Card>
				<div class="section-header">
					<Typography variant="h4">Language & Region</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Language</Typography>
						</div>
						<Select bind:value={settings.language.current} options={languages} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Auto-detect Location</Typography>
							<Typography variant="caption" color="secondary">Automatically set timezone based on location</Typography>
						</div>
						<Switch checked={settings.language.autoDetect} onchange={() => settings.language.autoDetect = !settings.language.autoDetect} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Timezone</Typography>
						</div>
						<Select bind:value={settings.language.timezone} options={timezones} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Date Format</Typography>
						</div>
						<Select bind:value={settings.language.dateFormat} options={dateFormats} />
					</div>
				</div>
			</Card>

		{:else if activeTab === 'accessibility'}
			<Card>
				<div class="section-header">
					<Typography variant="h4">Display</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Font Size</Typography>
						</div>
						<Select bind:value={settings.accessibility.fontSize} options={fontSizes} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Reduce Motion</Typography>
							<Typography variant="caption" color="secondary">Minimize animations and transitions</Typography>
						</div>
						<Switch checked={settings.accessibility.reduceMotion} onchange={() => settings.accessibility.reduceMotion = !settings.accessibility.reduceMotion} />
					</div>
				</div>
			</Card>

			<Card>
				<div class="section-header">
					<Typography variant="h4">Navigation</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Screen Reader Support</Typography>
							<Typography variant="caption" color="secondary">Optimize for screen readers</Typography>
						</div>
						<Switch checked={settings.accessibility.screenReader} onchange={() => settings.accessibility.screenReader = !settings.accessibility.screenReader} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Keyboard Navigation</Typography>
							<Typography variant="caption" color="secondary">Enable full keyboard control</Typography>
						</div>
						<Switch checked={settings.accessibility.keyboardNavigation} onchange={() => settings.accessibility.keyboardNavigation = !settings.accessibility.keyboardNavigation} />
					</div>
				</div>
			</Card>
		{/if}
	</div>

	<div class="settings-footer">
		<div class="footer-left">
			<Button variant="ghost" onclick={exportData}>
				<Icon name="download" /> Export
			</Button>
		</div>
		<div class="footer-right">
			<Button variant="ghost" onclick={resetSettings}>Reset to Defaults</Button>
			<Button variant="primary" loading={saving} onclick={saveSettings}>
				{saving ? 'Saving...' : 'Save Changes'}
			</Button>
		</div>
	</div>
</div>

<style>
	.settings-page {
		max-width: 800px;
		margin: 0 auto;
	}

	.settings-header {
		margin-bottom: 2rem;
	}

	.settings-content {
		display: flex;
		flex-direction: column;
		gap: 1.5rem;
		margin-top: 1.5rem;
	}

	.section-header {
		margin-bottom: 1.5rem;
	}

	.theme-options {
		display: flex;
		gap: 1rem;
	}

	.theme-option {
		flex: 1;
		padding: 1rem;
		background: rgba(255, 255, 255, 0.02);
		border: 2px solid rgba(255, 255, 255, 0.1);
		border-radius: 12px;
		cursor: pointer;
		transition: all 0.2s ease;
		text-align: center;
	}

	.theme-option:hover {
		border-color: rgba(0, 255, 255, 0.3);
	}

	.theme-option.active {
		border-color: #00ffff;
		background: rgba(0, 255, 255, 0.1);
	}

	.theme-preview {
		width: 100%;
		height: 60px;
		border-radius: 8px;
		margin-bottom: 0.75rem;
		overflow: hidden;
		display: flex;
		flex-direction: column;
	}

	.theme-preview .preview-bar {
		height: 15px;
	}

	.theme-preview .preview-content {
		flex: 1;
		padding: 0.5rem;
	}

	.theme-preview.cyberpunk .preview-bar {
		background: linear-gradient(90deg, #00ffff, #ff00ff);
	}

	.theme-preview.cyberpunk .preview-content {
		background: #1a1a2e;
	}

	.theme-preview.dark .preview-bar {
		background: #333;
	}

	.theme-preview.dark .preview-content {
		background: #1a1a1a;
	}

	.theme-preview.light .preview-bar {
		background: #ddd;
	}

	.theme-preview.light .preview-content {
		background: #f5f5f5;
	}

	.settings-list {
		display: flex;
		flex-direction: column;
	}

	.setting-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1rem 0;
		border-bottom: 1px solid rgba(255, 255, 255, 0.1);
	}

	.setting-item:last-child {
		border-bottom: none;
	}

	.setting-info {
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
	}

	.quiet-hours-inputs {
		display: flex;
		gap: 1rem;
		padding: 1rem 0;
	}

	.form-group {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
	}

	.form-group label {
		color: #888;
		font-size: 0.85rem;
	}

	.danger-actions {
		display: flex;
		gap: 1rem;
		margin-top: 1.5rem;
	}

	.settings-footer {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 2rem;
		padding-top: 1.5rem;
		border-top: 1px solid rgba(255, 255, 255, 0.1);
	}

	.footer-right {
		display: flex;
		gap: 1rem;
	}

	@media (max-width: 768px) {
		.theme-options {
			flex-direction: column;
		}

		.settings-footer {
			flex-direction: column;
			gap: 1rem;
		}

		.footer-left,
		.footer-right {
			width: 100%;
			justify-content: center;
		}
	}
</style>