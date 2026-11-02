<script lang="ts">
	import { onMount } from 'svelte';
	import Card from '$lib/components/molecules/Card.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Input from '$lib/components/atoms/Input.svelte';
	import Textarea from '$lib/components/Textarea.svelte';
	import Tabs from '$lib/components/Tabs.svelte';
	import Avatar from '$lib/components/Avatar.svelte';
	import Badge from '$lib/components/atoms/Badge.svelte';
	import Switch from '$lib/components/atoms/Switch.svelte';
	import Icon from '$lib/components/atoms/Icon.svelte';
	import { showToast } from '$lib/stores/global';

	interface UserProfile {
		id: string;
		name: string;
		email: string;
		bio: string;
		avatar: string;
		phone: string;
		location: string;
		website: string;
		socialLinks: Record<string, string>;
		createdAt: string;
		settings: ProfileSettings;
	}

	interface ProfileSettings {
		emailNotifications: boolean;
		pushNotifications: boolean;
		twoFactorAuth: boolean;
		privacyMode: boolean;
		showOnlineStatus: boolean;
	}

	let profile: UserProfile = {
		id: 'usr_001',
		name: 'Nguyễn Văn A',
		email: 'nguyenvana@example.com',
		bio: 'Software Engineer | Open Source Enthusiast | Coffee Lover',
		avatar: '',
		phone: '+84 123 456 789',
		location: 'Ho Chi Minh City, Vietnam',
		website: 'https://nguyenvana.dev',
		socialLinks: {
			github: 'https://github.com/nguyenvana',
			linkedin: 'https://linkedin.com/in/nguyenvana',
			twitter: 'https://twitter.com/nguyenvana'
		},
		createdAt: '2024-01-15',
		settings: {
			emailNotifications: true,
			pushNotifications: true,
			twoFactorAuth: false,
			privacyMode: false,
			showOnlineStatus: true
		}
	};

	let loading = false;
	let saving = false;
	let activeTab = 'profile';
	let editForm = { ...profile };
	let showPasswordModal = false;
	let newPassword = '';
	let confirmPassword = '';

	const tabs = [
		{ id: 'profile', label: 'Profile', icon: '👤' },
		{ id: 'security', label: 'Security', icon: '🔒' },
		{ id: 'settings', label: 'Settings', icon: '⚙️' }
	];

	onMount(async () => {
		await loadProfile();
	});

	async function loadProfile() {
		loading = true;
		try {
			await new Promise(resolve => setTimeout(resolve, 600));
		} catch (err) {
			showToast('Failed to load profile', 'error');
		} finally {
			loading = false;
		}
	}

	function startEditing() {
		editForm = { ...profile };
	}

	async function saveProfile() {
		saving = true;
		try {
			await new Promise(resolve => setTimeout(resolve, 800));
			profile = { ...editForm };
			showToast('Profile updated successfully', 'success');
		} catch (err) {
			showToast('Failed to update profile', 'error');
		} finally {
			saving = false;
		}
	}

	function cancelEditing() {
		editForm = { ...profile };
	}

	async function updatePassword() {
		if (newPassword !== confirmPassword) {
			showToast('Passwords do not match', 'error');
			return;
		}
		if (newPassword.length < 8) {
			showToast('Password must be at least 8 characters', 'error');
			return;
		}
		saving = true;
		try {
			await new Promise(resolve => setTimeout(resolve, 800));
			showPasswordModal = false;
			newPassword = '';
			confirmPassword = '';
			showToast('Password updated successfully', 'success');
		} catch (err) {
			showToast('Failed to update password', 'error');
		} finally {
			saving = false;
		}
	}

	function toggleSetting(key: keyof ProfileSettings) {
		profile.settings[key] = !profile.settings[key];
		showToast(`${key} updated`, 'success');
	}

	function formatDate(date: string): string {
		return new Date(date).toLocaleDateString('vi-VN', {
			year: 'numeric',
			month: 'long',
			day: 'numeric'
		});
	}
</script>

<div class="profile-page">
	<div class="profile-header-section">
		<div class="profile-cover"></div>
		<div class="profile-info-header">
			<div class="avatar-container">
				<Avatar src={profile.avatar} name={profile.name} size="xl" />
				<button class="avatar-edit-btn">
					<Icon name="camera" size="sm" />
				</button>
			</div>
			<div class="profile-title">
				<Typography variant="h2">{profile.name}</Typography>
				<Typography variant="body" color="secondary">{profile.email}</Typography>
				<div class="profile-badges">
					<Badge variant="info">Pro Member</Badge>
					<Badge variant="success">Verified</Badge>
				</div>
			</div>
			<div class="profile-actions">
				<Button variant="primary" onclick={startEditing}>
					<Icon name="edit" /> Edit Profile
				</Button>
			</div>
		</div>
	</div>

	<Tabs {tabs} bind:activeTab variant="pills" />

	<div class="tab-content">
		{#if activeTab === 'profile'}
			<Card>
				<div class="section-header">
					<Typography variant="h4">Personal Information</Typography>
				</div>
				<div class="form-grid">
					<div class="form-group">
						<Typography variant="body" fontWeight="medium">Full Name</Typography>
						<Input bind:value={editForm.name} placeholder="Enter your name" />
					</div>
					<div class="form-group">
						<Typography variant="body" fontWeight="medium">Email</Typography>
						<Input bind:value={editForm.email} type="email" placeholder="Enter your email" />
					</div>
					<div class="form-group">
						<Typography variant="body" fontWeight="medium">Phone</Typography>
						<Input bind:value={editForm.phone} placeholder="Enter your phone" />
					</div>
					<div class="form-group">
						<Typography variant="body" fontWeight="medium">Location</Typography>
						<Input bind:value={editForm.location} placeholder="Enter your location" />
					</div>
					<div class="form-group full-width">
						<Typography variant="body" fontWeight="medium">Bio</Typography>
						<Textarea bind:value={editForm.bio} rows="4" placeholder="Tell us about yourself" />
					</div>
					<div class="form-group full-width">
						<Typography variant="body" fontWeight="medium">Website</Typography>
						<Input bind:value={editForm.website} placeholder="https://yourwebsite.com" />
					</div>
				</div>
				<div class="form-actions">
					<Button variant="ghost" onclick={cancelEditing}>Cancel</Button>
					<Button variant="primary" loading={saving} onclick={saveProfile}>
						{saving ? 'Saving...' : 'Save Changes'}
					</Button>
				</div>
			</Card>

			<Card>
				<div class="section-header">
					<Typography variant="h4">Social Links</Typography>
				</div>
				<div class="social-links">
					{#each Object.entries(profile.socialLinks) as [platform, url]}
						<div class="social-link-item">
							<Icon name={platform} size="lg" />
							<Input bind:value={editForm.socialLinks[platform]} placeholder={`Enter ${platform} URL`} />
						</div>
					{/each}
				</div>
			</Card>

		{:else if activeTab === 'security'}
			<Card>
				<div class="section-header">
					<Typography variant="h4">Password</Typography>
					<Typography variant="body" color="secondary">Last changed: Never</Typography>
				</div>
				<Button variant="outline" onclick={() => showPasswordModal = true}>
					<Icon name="lock" /> Change Password
				</Button>
			</Card>

			<Card>
				<div class="section-header">
					<Typography variant="h4">Two-Factor Authentication</Typography>
				</div>
				<div class="security-item">
					<div class="security-info">
						<Typography variant="body">Enable 2FA for enhanced security</Typography>
						<Typography variant="caption" color="secondary">Requires authenticator app</Typography>
					</div>
					<Switch checked={profile.settings.twoFactorAuth} onchange={() => toggleSetting('twoFactorAuth')} />
				</div>
			</Card>

			<Card>
				<div class="section-header">
					<Typography variant="h4">Active Sessions</Typography>
				</div>
				<div class="session-list">
					<div class="session-item current">
						<Icon name="laptop" size="lg" />
						<div class="session-info">
							<Typography variant="body" fontWeight="medium">Current Session</Typography>
							<Typography variant="caption" color="secondary">Chrome on Windows • Ho Chi Minh City</Typography>
						</div>
						<Badge variant="success">Active</Badge>
					</div>
				</div>
			</Card>

		{:else if activeTab === 'settings'}
			<Card>
				<div class="section-header">
					<Typography variant="h4">Notifications</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Email Notifications</Typography>
							<Typography variant="caption" color="secondary">Receive updates via email</Typography>
						</div>
						<Switch checked={profile.settings.emailNotifications} onchange={() => toggleSetting('emailNotifications')} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Push Notifications</Typography>
							<Typography variant="caption" color="secondary">Browser push notifications</Typography>
						</div>
						<Switch checked={profile.settings.pushNotifications} onchange={() => toggleSetting('pushNotifications')} />
					</div>
				</div>
			</Card>

			<Card>
				<div class="section-header">
					<Typography variant="h4">Privacy</Typography>
				</div>
				<div class="settings-list">
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Show Online Status</Typography>
							<Typography variant="caption" color="secondary">Let others see when you're online</Typography>
						</div>
						<Switch checked={profile.settings.showOnlineStatus} onchange={() => toggleSetting('showOnlineStatus')} />
					</div>
					<div class="setting-item">
						<div class="setting-info">
							<Typography variant="body">Privacy Mode</Typography>
							<Typography variant="caption" color="secondary">Hide profile from non-friends</Typography>
						</div>
						<Switch checked={profile.settings.privacyMode} onchange={() => toggleSetting('privacyMode')} />
					</div>
				</div>
			</Card>

			<Card variant="danger">
				<div class="section-header">
					<Typography variant="h4" color="error">Danger Zone</Typography>
				</div>
				<div class="danger-actions">
					<Button variant="danger">
						<Icon name="trash" /> Delete Account
					</Button>
					<Typography variant="caption" color="secondary">
						Permanently delete your account and all data. This action cannot be undone.
					</Typography>
				</div>
			</Card>
		{/if}
	</div>
</div>

{#if showPasswordModal}
	<div class="modal-overlay" onclick={() => showPasswordModal = false}>
		<div class="modal" onclick={(e) => e.stopPropagation()}>
			<div class="modal-header">
				<Typography variant="h4">Change Password</Typography>
				<button class="close-btn" onclick={() => showPasswordModal = false}>×</button>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<Input bind:value={newPassword} type="password" placeholder="New password" />
				</div>
				<div class="form-group">
					<Input bind:value={confirmPassword} type="password" placeholder="Confirm password" />
				</div>
			</div>
			<div class="modal-footer">
				<Button variant="ghost" onclick={() => showPasswordModal = false}>Cancel</Button>
				<Button variant="primary" loading={saving} onclick={updatePassword}>Update Password</Button>
			</div>
		</div>
	</div>
{/if}

<style>
	.profile-page {
		max-width: 900px;
		margin: 0 auto;
	}

	.profile-header-section {
		margin-bottom: 2rem;
	}

	.profile-cover {
		height: 200px;
		background: linear-gradient(135deg, #00ffff 0%, #4ecdc4 50%, #44a08d 100%);
		border-radius: 16px 16px 0 0;
	}

	.profile-info-header {
		display: flex;
		align-items: flex-end;
		gap: 2rem;
		padding: 0 2rem 2rem;
		background: rgba(15, 15, 15, 0.95);
		border-radius: 0 0 16px 16px;
		margin-top: -60px;
		position: relative;
	}

	.avatar-container {
		position: relative;
		margin-top: -40px;
	}

	.avatar-edit-btn {
		position: absolute;
		bottom: 0;
		right: 0;
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: #00ffff;
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.profile-title {
		flex: 1;
		padding-top: 1rem;
	}

	.profile-badges {
		display: flex;
		gap: 0.5rem;
		margin-top: 0.5rem;
	}

	.profile-actions {
		padding-top: 1rem;
	}

	.tab-content {
		display: flex;
		flex-direction: column;
		gap: 1.5rem;
		margin-top: 2rem;
	}

	.section-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1.5rem;
	}

	.form-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 1.5rem;
	}

	.form-group {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
	}

	.form-group.full-width {
		grid-column: span 2;
	}

	.form-actions {
		display: flex;
		justify-content: flex-end;
		gap: 1rem;
		margin-top: 2rem;
		padding-top: 1.5rem;
		border-top: 1px solid rgba(255, 255, 255, 0.1);
	}

	.social-links {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.social-link-item {
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.security-item,
	.setting-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1rem 0;
		border-bottom: 1px solid rgba(255, 255, 255, 0.1);
	}

	.security-item:last-child,
	.setting-item:last-child {
		border-bottom: none;
	}

	.security-info,
	.setting-info {
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
	}

	.session-list {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.session-item {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 1rem;
		background: rgba(255, 255, 255, 0.02);
		border-radius: 12px;
	}

	.session-item.current {
		background: rgba(0, 255, 255, 0.1);
		border: 1px solid rgba(0, 255, 255, 0.3);
	}

	.session-info {
		flex: 1;
	}

	.danger-actions {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	/* Modal */
	.modal-overlay {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.8);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 1000;
	}

	.modal {
		background: #1a1a2e;
		border-radius: 16px;
		width: 100%;
		max-width: 400px;
		overflow: hidden;
	}

	.modal-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1.5rem;
		border-bottom: 1px solid rgba(255, 255, 255, 0.1);
	}

	.close-btn {
		background: none;
		border: none;
		color: #888;
		font-size: 1.5rem;
		cursor: pointer;
	}

	.modal-body {
		padding: 1.5rem;
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.modal-footer {
		display: flex;
		justify-content: flex-end;
		gap: 1rem;
		padding: 1.5rem;
		border-top: 1px solid rgba(255, 255, 255, 0.1);
	}

	@media (max-width: 768px) {
		.profile-info-header {
			flex-direction: column;
			align-items: center;
			text-align: center;
		}

		.form-grid {
			grid-template-columns: 1fr;
		}

		.form-group.full-width {
			grid-column: span 1;
		}
	}
</style>