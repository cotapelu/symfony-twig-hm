<script lang="ts">
	import { onMount } from 'svelte';
	import { user, activeSection, setActiveSection } from '$lib/stores/global';
	import Button from '$lib/components/atoms/Button.svelte';
	import DashboardStats from './DashboardStats.svelte';
	import MessagesPanel from './MessagesPanel.svelte';
	import GroupsPanel from './GroupsPanel.svelte';
	import CallsPanel from './CallsPanel.svelte';
	import FilesPanel from './FilesPanel.svelte';
	import SettingsPanel from './SettingsPanel.svelte';

	let sidebarCollapsed = false;

	onMount(() => {
		if (typeof window !== 'undefined') {
			sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
		}
	});

	function toggleSidebar() {
		sidebarCollapsed = !sidebarCollapsed;
		if (typeof window !== 'undefined') {
			localStorage.setItem('sidebarCollapsed', sidebarCollapsed.toString());
		}
	}

	function handleSectionClick(section: string) {
		setActiveSection(section);
	}

	function logout() {
		if (typeof window !== 'undefined') {
			localStorage.removeItem('auth_token');
			window.location.href = '/auth';
		}
	}
</script>

<main class="dashboard">
	<!-- Sidebar -->
	<aside class="sidebar" class:collapsed={sidebarCollapsed}>
		<div class="sidebar-header">
			<h2>{sidebarCollapsed ? 'C' : 'CommUnity'}</h2>
			<Button variant="ghost" onclick={toggleSidebar}>
				{sidebarCollapsed ? '→' : '←'}
			</Button>
		</div>

		<nav class="sidebar-nav">
			<Button onclick={() => handleSectionClick('dashboard')} variant={$activeSection === 'dashboard' ? 'primary' : 'ghost'}>
				🏠 {!sidebarCollapsed ? 'Dashboard' : ''}
			</Button>
			<Button onclick={() => handleSectionClick('messages')} variant={$activeSection === 'messages' ? 'primary' : 'ghost'}>
				💬 {!sidebarCollapsed ? 'Messages' : ''}
			</Button>
			<Button onclick={() => handleSectionClick('groups')} variant={$activeSection === 'groups' ? 'primary' : 'ghost'}>
				👥 {!sidebarCollapsed ? 'Groups' : ''}
			</Button>
			<Button onclick={() => handleSectionClick('calls')} variant={$activeSection === 'calls' ? 'primary' : 'ghost'}>
				📞 {!sidebarCollapsed ? 'Calls' : ''}
			</Button>
			<Button onclick={() => handleSectionClick('files')} variant={$activeSection === 'files' ? 'primary' : 'ghost'}>
				📄 {!sidebarCollapsed ? 'Files' : ''}
			</Button>
			<Button onclick={() => handleSectionClick('profile')} variant={$activeSection === 'profile' ? 'primary' : 'ghost'}>
				👤 {!sidebarCollapsed ? 'Profile' : ''}
			</Button>
			<Button onclick={() => handleSectionClick('settings')} variant={$activeSection === 'settings' ? 'primary' : 'ghost'}>
				⚙️ {!sidebarCollapsed ? 'Settings' : ''}
			</Button>
			<Button onclick={logout} variant="danger">
				🚪 {!sidebarCollapsed ? 'Logout' : ''}
			</Button>
		</nav>
	</aside>

	<!-- Main Content -->
	<div class="main-content">
		<header class="header">
			<h1>Dashboard</h1>
		</header>

		<div class="content">
			{#if $activeSection === 'dashboard'}
				<DashboardStats />
			{:else if $activeSection === 'messages'}
				<MessagesPanel />
			{:else if $activeSection === 'groups'}
				<GroupsPanel />
			{:else if $activeSection === 'calls'}
				<CallsPanel />
			{:else if $activeSection === 'files'}
				<FilesPanel />
			{:else if $activeSection === 'settings'}
				<SettingsPanel />
			{:else if $activeSection === 'profile'}
				<div class="placeholder">
					<p>Profile section coming soon...</p>
				</div>
			{/if}
		</div>
	</div>
</main>

<style>
	.dashboard {
		display: flex;
		min-height: 100vh;
		background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
		color: #ffffff;
	}

	.sidebar {
		width: 250px;
		background: rgba(15, 15, 15, 0.95);
		border-right: 1px solid #00ffff;
		display: flex;
		flex-direction: column;
		transition: width 0.3s ease;
	}

	.sidebar.collapsed {
		width: 60px;
	}

	.sidebar-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1rem;
		border-bottom: 1px solid rgba(0, 255, 255, 0.3);
	}

	.sidebar-header h2 {
		color: #00ffff;
		margin: 0;
		font-size: 1.5rem;
		text-shadow: 0 0 10px #00ffff;
	}

	.sidebar-toggle {
		background: none;
		border: 1px solid #00ffff;
		color: #00ffff;
		padding: 0.5rem;
		cursor: pointer;
		border-radius: 4px;
		transition: all 0.3s ease;
	}

	.sidebar-toggle:hover {
		background: rgba(0, 255, 255, 0.1);
	}

	.sidebar-nav {
		display: flex;
		flex-direction: column;
		padding: 1rem 0;
	}

	.sidebar-nav button {
		display: block;
		width: 100%;
		padding: 0.75rem 1rem;
		margin-bottom: 0.5rem;
		background: rgba(0, 255, 255, 0.1);
		border: 1px solid rgba(0, 255, 255, 0.3);
		border-radius: 8px;
		color: #00ffff;
		cursor: pointer;
		transition: all 0.3s ease;
		text-align: left;
		font-size: 1rem;
	}

	.sidebar-nav button:hover {
		background: rgba(0, 255, 255, 0.2);
		box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
		transform: translateX(5px);
	}

	.sidebar-nav button.active {
		background: rgba(0, 255, 255, 0.3);
		border-color: #00ffff;
		box-shadow: 0 0 20px rgba(0, 255, 255, 0.8);
	}

	.sidebar-nav button:last-child {
		margin-top: auto;
		background: rgba(255, 107, 107, 0.1);
		border-color: rgba(255, 107, 107, 0.3);
		color: #ff6b6b;
	}

	.sidebar-nav button:last-child:hover {
		background: rgba(255, 107, 107, 0.2);
		box-shadow: 0 0 15px rgba(255, 107, 107, 0.5);
	}

	.main-content {
		flex: 1;
		padding: 2rem;
		overflow-y: auto;
	}

	.header {
		margin-bottom: 2rem;
	}

	.header h1 {
		color: #00ffff;
		margin: 0;
		font-size: 2.5rem;
		text-shadow: 0 0 15px #00ffff;
	}

	.content {
		min-height: 400px;
	}

	.placeholder {
		text-align: center;
		padding: 2rem;
		color: #cccccc;
	}

	@media (max-width: 768px) {
		.dashboard {
			flex-direction: column;
		}

		.sidebar {
			width: 100%;
			height: auto;
		}

		.sidebar.collapsed {
			width: 100%;
		}

		.main-content {
			padding: 1rem;
		}

		.header h1 {
			font-size: 2rem;
		}
	}
</style>