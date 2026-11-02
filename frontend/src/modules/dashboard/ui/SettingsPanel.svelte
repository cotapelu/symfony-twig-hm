<script lang="ts">
	import Button from '$lib/components/atoms/Button.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import Select from '$lib/components/atoms/Select.svelte';
	import Checkbox from '$lib/components/atoms/Checkbox.svelte';
	import Input from '$lib/components/atoms/Input.svelte';
	import { showToast } from '$lib/stores/global';
	import { locale } from 'svelte-i18n';
	import { theme } from '$lib/stores/global';

	let currentLang = 'en';
	let pushNotifications = true;
	let quietHours = false;
	let showOnlineStatus = true;
	let readReceipts = true;

	function saveSettings() {
		localStorage.setItem('language', currentLang);
		localStorage.setItem('pushNotifications', pushNotifications.toString());
		localStorage.setItem('quietHours', quietHours.toString());
		localStorage.setItem('showOnlineStatus', showOnlineStatus.toString());
		localStorage.setItem('readReceipts', readReceipts.toString());

		showToast('Settings saved!', 'success');
	}

	function setLanguage(lang: string) {
		currentLang = lang;
		locale.set(lang);
	}
</script>

<div class="settings-section">
	<Typography variant="h2">Settings</Typography>
	<div class="settings-grid">
		<div class="setting-group">
			<Typography variant="h3">Appearance</Typography>
			<div class="setting-item">
				<Select
					label="Theme"
					options={[
						{ value: 'light', label: 'Light' },
						{ value: 'dark', label: 'Dark' },
						{ value: 'cyberpunk', label: 'Cyberpunk' }
					]}
					value={$theme}
					description="Choose your preferred theme for the application interface"
				/>
			</div>
			<div class="setting-item">
				<Select
					label="Language"
					options={[
						{ value: 'en', label: 'English' },
						{ value: 'vi', label: 'Tiếng Việt' },
						{ value: 'es', label: 'Español' }
					]}
					value={currentLang}
					description="Select your preferred language for the application"
				/>
			</div>
		</div>

		<div class="setting-group">
			<Typography variant="h3">Notifications</Typography>
			<div class="setting-item">
				<Checkbox bind:checked={pushNotifications} label="Push Notifications" />
			</div>
			<div class="setting-item">
				<Checkbox bind:checked={quietHours} label="Quiet Hours (10 PM - 8 AM)" />
			</div>
		</div>

		<div class="setting-group">
			<Typography variant="h3">Privacy</Typography>
			<div class="setting-item">
				<Checkbox bind:checked={showOnlineStatus} label="Show Online Status" />
			</div>
			<div class="setting-item">
				<Checkbox bind:checked={readReceipts} label="Read Receipts" />
			</div>
		</div>
	</div>
	<div class="settings-actions">
		<Button onclick={saveSettings}>Save Settings</Button>
	</div>
</div>

<style>
	.settings-section {
		padding: 2rem 0;
	}

	.settings-grid {
		display: grid;
		gap: 2rem;
		grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
		margin-bottom: 2rem;
	}

	.setting-group {
		background: rgba(20, 20, 20, 0.8);
		border: 1px solid rgba(0, 255, 255, 0.3);
		border-radius: 12px;
		padding: 1.5rem;
	}

	.setting-group h3 {
		color: #ff00ff;
		margin: 0 0 1rem 0;
		font-size: 1.3rem;
		text-shadow: 0 0 10px #ff00ff;
	}

	.setting-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1rem;
		padding: 0.5rem 0;
		border-bottom: 1px solid rgba(255, 255, 255, 0.1);
	}

	.setting-item:last-child {
		border-bottom: none;
		margin-bottom: 0;
	}

	.setting-item label {
		color: #ffffff;
		font-size: 0.9rem;
		cursor: pointer;
	}

	.settings-actions {
		text-align: center;
	}

	.settings-actions button {
		background: linear-gradient(45deg, #00ffff, #ff00ff);
		border: none;
		color: #000000;
		padding: 0.75rem 2rem;
		border-radius: 8px;
		font-size: 1rem;
		font-weight: bold;
		cursor: pointer;
		transition: all 0.3s ease;
		box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
	}

	.settings-actions button:hover {
		transform: translateY(-2px);
		box-shadow: 0 0 25px rgba(0, 255, 255, 0.8);
	}
</style>
