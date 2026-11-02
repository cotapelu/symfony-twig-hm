<script lang="ts">
	import { onMount } from 'svelte';
	import Card from '$lib/components/molecules/Card.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import Badge from '$lib/components/atoms/Badge.svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Progress from '$lib/components/atoms/Progress.svelte';
	import Icon from '$lib/components/atoms/Icon.svelte';
	import { showToast } from '$lib/stores/global';

	interface DashboardStatsData {
		messages: { total: number; unread: number };
		groups: { total: number; active: number };
		files: { total: number; shared: number };
		calls: { total: number; missed: number };
		activities: ActivityItem[];
		quickStats: QuickStat[];
	}

	interface ActivityItem {
		id: string;
		type: 'message' | 'post' | 'friend' | 'group' | 'file' | 'call';
		title: string;
		description: string;
		timestamp: Date;
		unread: boolean;
	}

	interface QuickStat {
		label: string;
		value: string | number;
		change: number;
		icon: string;
		color: string;
	}

	const stats: DashboardStatsData = {
		messages: { total: 128, unread: 12 },
		groups: { total: 24, active: 8 },
		files: { total: 567, shared: 89 },
		calls: { total: 45, missed: 3 },
		activities: [
			{
				id: '1',
				type: 'message',
				title: 'New message from Sarah',
				description: 'Hey, did you see the new designs?',
				timestamp: new Date(Date.now() - 5 * 60000),
				unread: true
			},
			{
				id: '2',
				type: 'friend',
				title: 'John Doe accepted your request',
				description: 'You are now friends',
				timestamp: new Date(Date.now() - 30 * 60000),
				unread: true
			},
			{
				id: '3',
				type: 'file',
				title: 'File shared in Project Alpha',
				description: 'document_v2.pdf uploaded',
				timestamp: new Date(Date.now() - 2 * 3600000),
				unread: false
			},
			{
				id: '4',
				type: 'group',
				title: 'New post in Dev Team',
				description: 'Mike shared an update',
				timestamp: new Date(Date.now() - 5 * 3600000),
				unread: false
			},
			{
				id: '5',
				type: 'call',
				title: 'Missed call from Mom',
				description: 'Yesterday at 8:30 PM',
				timestamp: new Date(Date.now() - 12 * 3600000),
				unread: true
			}
		],
		quickStats: [
			{ label: 'Messages', value: 128, change: 12, icon: '💬', color: '#00ffff' },
			{ label: 'Groups', value: 24, change: 3, icon: '👥', color: '#ff6b6b' },
			{ label: 'Files', value: 567, change: 23, icon: '📄', color: '#4ecdc4' },
			{ label: 'Calls', value: 45, change: -2, icon: '📞', color: '#ffe66d' }
		]
	};

	let loading = true;

	onMount(async () => {
		await loadStats();
	});

	async function loadStats() {
		try {
			loading = true;
			await new Promise(resolve => setTimeout(resolve, 800));
		} catch (err) {
			showToast('Failed to load dashboard stats', 'error');
		} finally {
			loading = false;
		}
	}

	function formatTime(date: Date): string {
		const now = new Date();
		const diff = now.getTime() - date.getTime();
		const minutes = Math.floor(diff / 60000);
		const hours = Math.floor(diff / 3600000);
		const days = Math.floor(diff / 86400000);

		if (minutes < 1) return 'Just now';
		if (minutes < 60) return `${minutes}m ago`;
		if (hours < 24) return `${hours}h ago`;
		return `${days}d ago`;
	}

	function getActivityIcon(type: string): string {
		const icons: Record<string, string> = {
			message: '💬',
			post: '📝',
			friend: '👤',
			group: '👥',
			file: '📄',
			call: '📞'
		};
		return icons[type] || '📌';
	}

	function getActivityColor(type: string): string {
		const colors: Record<string, string> = {
			message: '#00ffff',
			post: '#4ecdc4',
			friend: '#ff6b6b',
			group: '#ffe66d',
			file: '#a8e6cf',
			call: '#dda0dd'
		};
		return colors[type] || '#888';
	}
</script>

<div class="dashboard-stats">
	<!-- Quick Stats Grid -->
	<div class="quick-stats">
		{#each stats.quickStats as stat}
			<Card hoverable class="stat-card">
				<div class="stat-content">
					<div class="stat-icon" style="background: {stat.color}20; color: {stat.color}">
						<span class="icon">{stat.icon}</span>
					</div>
					<div class="stat-info">
						<Typography variant="body" color="secondary">{stat.label}</Typography>
						<Typography variant="h2">{stat.value}</Typography>
						<div class="stat-change" class:positive={stat.change >= 0} class:negative={stat.change < 0}>
							{stat.change >= 0 ? '+' : ''}{stat.change}% this week
						</div>
					</div>
				</div>
			</Card>
		{/each}
	</div>

	<!-- Main Stats Cards -->
	<div class="main-stats">
		<div class="stat-row">
			<Card hoverable class="main-card messages">
				<div class="card-header">
					<Icon name="message" size="lg" />
					<Typography variant="h4">Messages</Typography>
				</div>
				<div class="card-body">
					<div class="stat-large">
						<Typography variant="h1">{stats.messages.total}</Typography>
						<Typography variant="body" color="secondary">total</Typography>
					</div>
					<div class="stat-detail">
						<Badge variant="error">{stats.messages.unread} unread</Badge>
					</div>
					<Progress percent={(stats.messages.unread / stats.messages.total) * 100} color="#00ffff" />
				</div>
			</Card>

			<Card hoverable class="main-card groups">
				<div class="card-header">
					<Icon name="group" size="lg" />
					<Typography variant="h4">Groups</Typography>
				</div>
				<div class="card-body">
					<div class="stat-large">
						<Typography variant="h1">{stats.groups.total}</Typography>
						<Typography variant="body" color="secondary">total</Typography>
					</div>
					<div class="stat-detail">
						<Badge variant="success">{stats.groups.active} active</Badge>
					</div>
					<Progress percent={(stats.groups.active / stats.groups.total) * 100} color="#ff6b6b" />
				</div>
			</Card>

			<Card hoverable class="main-card files">
				<div class="card-header">
					<Icon name="folder" size="lg" />
					<Typography variant="h4">Files</Typography>
				</div>
				<div class="card-body">
					<div class="stat-large">
						<Typography variant="h1">{stats.files.total}</Typography>
						<Typography variant="body" color="secondary">total</Typography>
					</div>
					<div class="stat-detail">
						<Badge variant="info">{stats.files.shared} shared</Badge>
					</div>
					<Progress percent={(stats.files.shared / stats.files.total) * 100} color="#4ecdc4" />
				</div>
			</Card>

			<Card hoverable class="main-card calls">
				<div class="card-header">
					<Icon name="phone" size="lg" />
					<Typography variant="h4">Calls</Typography>
				</div>
				<div class="card-body">
					<div class="stat-large">
						<Typography variant="h1">{stats.calls.total}</Typography>
						<Typography variant="body" color="secondary">this month</Typography>
					</div>
					<div class="stat-detail">
						<Badge variant="warning">{stats.calls.missed} missed</Badge>
					</div>
					<Progress percent={(stats.calls.missed / stats.calls.total) * 100} color="#ffe66d" />
				</div>
			</Card>
		</div>
	</div>

	<!-- Activity Feed -->
	<div class="activity-section">
		<div class="section-header">
			<Typography variant="h3">Recent Activity</Typography>
			<Button variant="ghost" size="sm">View All</Button>
		</div>

		<Card>
			<div class="activity-feed">
				{#each stats.activities as activity}
					<div class="activity-item" class:unread={activity.unread}>
						<div class="activity-icon" style="background: {getActivityColor(activity.type)}20; color: {getActivityColor(activity.type)}">
							{getActivityIcon(activity.type)}
						</div>
						<div class="activity-content">
							<div class="activity-title">
								<Typography variant="body" fontWeight="medium">{activity.title}</Typography>
								{#if activity.unread}
									<Badge variant="error" size="sm">New</Badge>
								{/if}
							</div>
							<Typography variant="caption" color="secondary">{activity.description}</Typography>
						</div>
						<div class="activity-time">
							<Typography variant="caption" color="tertiary">{formatTime(activity.timestamp)}</Typography>
						</div>
					</div>
				{/each}
			</div>
		</Card>
	</div>
</div>

<style>
	.dashboard-stats {
		display: flex;
		flex-direction: column;
		gap: 2rem;
	}

	/* Quick Stats */
	.quick-stats {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
		gap: 1.5rem;
	}

	.stat-card {
		padding: 1.5rem;
	}

	.stat-content {
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.stat-icon {
		width: 56px;
		height: 56px;
		border-radius: 12px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.5rem;
	}

	.stat-info {
		flex: 1;
	}

	.stat-info h2 {
		margin: 0;
		font-size: 2rem;
		font-weight: 700;
		color: #fff;
	}

	.stat-change {
		font-size: 0.85rem;
		margin-top: 0.25rem;
	}

	.stat-change.positive {
		color: #4ecdc4;
	}

	.stat-change.negative {
		color: #ff6b6b;
	}

	/* Main Stats */
	.stat-row {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
		gap: 1.5rem;
	}

	.main-card {
		padding: 1.5rem;
	}

	.card-header {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		margin-bottom: 1.5rem;
	}

	.card-body {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.stat-large {
		display: flex;
		align-items: baseline;
		gap: 0.5rem;
	}

	.stat-large h1 {
		margin: 0;
		font-size: 3rem;
		font-weight: 700;
		background: linear-gradient(135deg, #00ffff, #4ecdc4);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		background-clip: text;
	}

	/* Activity Section */
	.activity-section {
		margin-top: 1rem;
	}

	.section-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1rem;
	}

	.activity-feed {
		display: flex;
		flex-direction: column;
	}

	.activity-item {
		display: flex;
		align-items: flex-start;
		gap: 1rem;
		padding: 1rem;
		border-bottom: 1px solid rgba(255, 255, 255, 0.1);
		transition: background 0.2s ease;
	}

	.activity-item:last-child {
		border-bottom: none;
	}

	.activity-item:hover {
		background: rgba(255, 255, 255, 0.02);
	}

	.activity-item.unread {
		background: rgba(0, 255, 255, 0.05);
	}

	.activity-icon {
		width: 40px;
		height: 40px;
		border-radius: 10px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
		flex-shrink: 0;
	}

	.activity-content {
		flex: 1;
		min-width: 0;
	}

	.activity-title {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.activity-time {
		flex-shrink: 0;
		color: #666;
	}

	@media (max-width: 768px) {
		.quick-stats {
			grid-template-columns: 1fr;
		}

		.stat-row {
			grid-template-columns: 1fr;
		}

		.activity-item {
			flex-wrap: wrap;
		}

		.activity-time {
			width: 100%;
			margin-top: 0.5rem;
			padding-left: 56px;
		}
	}
</style>
