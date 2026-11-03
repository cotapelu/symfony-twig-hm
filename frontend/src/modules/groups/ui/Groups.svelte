<script lang="ts">
	import { onMount } from 'svelte';
	import Card from '$lib/components/molecules/Card.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Input from '$lib/components/atoms/Input.svelte';
	import Avatar from '$lib/components/Avatar.svelte';
	import Badge from '$lib/components/atoms/Badge.svelte';
	import Tabs from '$lib/components/Tabs.svelte';
	import Icon from '$lib/components/atoms/Icon.svelte';
	import Empty from '$lib/components/Empty.svelte';
	import Modal from '$lib/components/molecules/Modal.svelte';
	import { showToast } from '$lib/stores/global';

	interface Group {
		id: string;
		name: string;
		description: string;
		avatar?: string;
		members: GroupMember[];
		memberCount: number;
		admins: string[];
		isPrivate: boolean;
		createdBy: string;
		createdAt: string;
		activity: 'high' | 'medium' | 'low';
	}

	interface GroupMember {
		id: string;
		name: string;
		avatar?: string;
		role: 'admin' | 'moderator' | 'member';
		joinedAt: string;
	}

	let groups: Group[] = [
		{
			id: '1',
			name: 'Dev Team Alpha',
			description: 'Main development team for the core platform',
			avatar: '',
			members: [
				{ id: '1', name: 'Nguyễn Văn A', role: 'admin', joinedAt: '2024-01-15' },
				{ id: '2', name: 'Trần Thị B', role: 'moderator', joinedAt: '2024-01-16' },
				{ id: '3', name: 'Lê Văn C', role: 'member', joinedAt: '2024-01-17' }
			],
			memberCount: 24,
			admins: ['1'],
			isPrivate: false,
			createdBy: 'Nguyễn Văn A',
			createdAt: '2024-01-15',
			activity: 'high'
		},
		{
			id: '2',
			name: 'Design Community',
			description: 'Share designs, get feedback, and collaborate',
			avatar: '',
			members: [
				{ id: '4', name: 'Phạm Thị D', role: 'admin', joinedAt: '2024-02-01' },
				{ id: '5', name: 'Hoàng Văn E', role: 'member', joinedAt: '2024-02-05' }
			],
			memberCount: 156,
			admins: ['4'],
			isPrivate: false,
			createdBy: 'Phạm Thị D',
			createdAt: '2024-02-01',
			activity: 'medium'
		},
		{
			id: '3',
			name: 'Product Managers',
			description: 'Product roadmap discussions and planning',
			avatar: '',
			members: [
				{ id: '6', name: 'Đặng Văn F', role: 'admin', joinedAt: '2024-01-20' }
			],
			memberCount: 18,
			admins: ['6'],
			isPrivate: true,
			createdBy: 'Đặng Văn F',
			createdAt: '2024-01-20',
			activity: 'low'
		},
		{
			id: '4',
			name: 'QA Testing Hub',
			description: 'Bug reports, testing strategies, and quality assurance',
			avatar: '',
			members: [],
			memberCount: 32,
			admins: [],
			isPrivate: false,
			createdBy: 'Nguyễn Văn A',
			createdAt: '2024-02-10',
			activity: 'high'
		}
	];

	let activeTab = 'discover';
	let searchQuery = '';
	let showCreateModal = false;
	let viewMode: 'grid' | 'list' = 'grid';
	let loading = false;

	let newGroup = {
		name: '',
		description: '',
		isPrivate: false
	};

	const tabs = [
		{ id: 'discover', label: 'Discover', icon: '🔍' },
		{ id: 'mygroups', label: 'My Groups', icon: '👥' },
		{ id: 'joined', label: 'Joined', icon: '✅' },
		{ id: 'pending', label: 'Pending', icon: '⏳' }
	];

	const activityColors: Record<string, string> = {
		high: '#4ecdc4',
		medium: '#ffe66d',
		low: '#ff6b6b'
	};

	function formatMemberCount(count: number): string {
		if (count >= 1000000) return `${(count / 1000000).toFixed(1)}M`;
		if (count >= 1000) return `${(count / 1000).toFixed(1)}K`;
		return count.toString();
	}

	function formatDate(date: string): string {
		return new Date(date).toLocaleDateString('vi-VN', {
			year: 'numeric',
			month: 'short',
			day: 'numeric'
		});
	}

	function getFilteredGroups(): Group[] {
		return groups.filter(g => {
			if (searchQuery && !g.name.toLowerCase().includes(searchQuery.toLowerCase())) return false;
			if (activeTab === 'mygroups' && !g.createdBy) return false;
			if (activeTab === 'private' && !g.isPrivate) return false;
			return true;
		});
	}

	async function handleCreateGroup() {
		if (!newGroup.name.trim()) {
			showToast('Please enter a group name', 'error');
			return;
		}

		loading = true;
		try {
			await new Promise(resolve => setTimeout(resolve, 800));

			const group: Group = {
				id: Math.random().toString(36).substr(2, 9),
				name: newGroup.name,
				description: newGroup.description,
				members: [{ id: '1', name: 'Nguyễn Văn A', role: 'admin', joinedAt: new Date().toISOString().split('T')[0] }],
				memberCount: 1,
				admins: ['1'],
				isPrivate: newGroup.isPrivate,
				createdBy: 'Nguyễn Văn A',
				createdAt: new Date().toISOString().split('T')[0],
				activity: 'low'
			};

			groups = [group, ...groups];
			showCreateModal = false;
			newGroup = { name: '', description: '', isPrivate: false };
			showToast('Group created successfully', 'success');
		} catch (err) {
			showToast('Failed to create group', 'error');
		} finally {
			loading = false;
		}
	}

	async function handleJoinGroup(group: Group) {
		showToast(`Request sent to join ${group.name}`, 'success');
	}

	async function handleLeaveGroup(group: Group) {
		if (confirm(`Leave ${group.name}?`)) {
			groups = groups.filter(g => g.id !== group.id);
			showToast(`You left ${group.name}`, 'success');
		}
	}

	$: filteredGroups = getFilteredGroups();
</script>

<div class="groups-page">
	<div class="groups-header">
		<div class="header-left">
			<Typography variant="h2">Groups</Typography>
			<Typography variant="body" color="secondary">
				{groups.length} groups
			</Typography>
		</div>
		<div class="header-actions">
			<Button variant="outline" onclick={() => viewMode = viewMode === 'grid' ? 'list' : 'grid'}>
				<Icon name={viewMode === 'grid' ? 'list' : 'grid'} />
			</Button>
			<Button variant="primary" onclick={() => showCreateModal = true}>
				<Icon name="plus" /> Create Group
			</Button>
		</div>
	</div>

	<div class="search-section">
		<Input
			bind:value={searchQuery}
			placeholder="Search groups..."
			icon="search"
		/>
	</div>

	<Tabs {tabs} bind:activeTab variant="pills" />

	<div class="groups-content">
		{#if filteredGroups.length === 0}
			<Empty
				icon="users"
				title="No groups found"
				description={searchQuery ? 'Try a different search term' : 'Be the first to create a group!'}
			/>
		{:else if viewMode === 'grid'}
			<div class="groups-grid">
				{#each filteredGroups as group}
					<Card hoverable className="group-card">
						<div class="group-cover" style="background: linear-gradient(135deg, {activityColors[group.activity]}40, transparent)">
							<Badge variant={group.isPrivate ? 'warning' : 'secondary'}>
								{group.isPrivate ? '🔒 Private' : '🌐 Public'}
							</Badge>
						</div>
						<div class="group-avatar">
							<Avatar src={group.avatar} name={group.name} size="lg" />
						</div>
						<div class="group-info">
							<Typography variant="h4" ellipsis>{group.name}</Typography>
							<Typography variant="body" color="secondary" ellipsis>{group.description}</Typography>
						</div>
						<div class="group-stats">
							<div class="stat">
								<Typography variant="h4">{formatMemberCount(group.memberCount)}</Typography>
								<Typography variant="caption" color="secondary">Members</Typography>
							</div>
							<div class="activity-indicator">
								<span class="dot" style="background: {activityColors[group.activity]}"></span>
								<Typography variant="caption" color="secondary">{group.activity}</Typography>
							</div>
						</div>
						<div class="group-members-preview">
							{#each group.members.slice(0, 3) as member}
								<Avatar src={member.avatar} name={member.name} size="sm" />
							{/each}
							{#if group.memberCount > 3}
								<div class="more-members">
									+{group.memberCount - 3}
								</div>
							{/if}
						</div>
						<div class="group-footer">
							<Button variant="ghost" size="sm" onclick={() => handleJoinGroup(group)}>
								<Icon name="user-plus" /> Join
							</Button>
							<Button variant="ghost" size="sm">
								<Icon name="eye" /> Preview
							</Button>
						</div>
					</Card>
				{/each}
			</div>
		{:else}
			<div class="groups-list">
				{#each filteredGroups as group}
					<div class="group-row">
						<div class="row-avatar">
							<Avatar src={group.avatar} name={group.name} size="lg" />
						</div>
						<div class="row-info">
							<div class="row-title">
								<Typography variant="body" fontWeight="medium">{group.name}</Typography>
								<Badge variant={group.isPrivate ? 'warning' : 'secondary'} size="sm">
									{group.isPrivate ? '🔒' : '🌐'}
								</Badge>
							</div>
							<Typography variant="caption" color="secondary">{group.description}</Typography>
						</div>
						<div class="row-stats">
							<Typography variant="body">{formatMemberCount(group.memberCount)} members</Typography>
							<Typography variant="caption" color="secondary">Created {formatDate(group.createdAt)}</Typography>
						</div>
						<div class="row-actions">
							<Button variant="primary" size="sm" onclick={() => handleJoinGroup(group)}>Join</Button>
							<Button variant="ghost" size="sm">
								<Icon name="more" />
							</Button>
						</div>
					</div>
				{/each}
			</div>
		{/if}
	</div>
</div>

{#if showCreateModal}
	<Modal bind:visible={showCreateModal} title="Create New Group">
		<div class="create-form">
			<div class="form-group">
				<Typography variant="body" fontWeight="medium">Group Name *</Typography>
				<Input bind:value={newGroup.name} placeholder="Enter group name" />
			</div>
			<div class="form-group">
				<Typography variant="body" fontWeight="medium">Description</Typography>
				<textarea
					bind:value={newGroup.description}
					placeholder="What is this group about?"
					rows="3"
				></textarea>
			</div>
			<div class="form-group">
				<label class="checkbox-label">
					<input type="checkbox" bind:checked={newGroup.isPrivate} />
					<span>Make this group private</span>
					<Typography variant="caption" color="secondary">Only invited members can join</Typography>
				</label>
			</div>
		</div>
		<div class="modal-footer">
			<Button variant="ghost" onclick={() => showCreateModal = false}>Cancel</Button>
			<Button variant="primary" loading={loading} onclick={handleCreateGroup}>
				{loading ? 'Creating...' : 'Create Group'}
			</Button>
		</div>
	</Modal>
{/if}

<style>
	.groups-page {
		display: flex;
		flex-direction: column;
		gap: 1.5rem;
	}

	.groups-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.header-left {
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
	}

	.header-actions {
		display: flex;
		gap: 0.75rem;
	}

	.search-section {
		max-width: 400px;
	}

	.groups-content {
		margin-top: 1rem;
	}

	.groups-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
		gap: 1.5rem;
	}

	.group-card {
		overflow: hidden;
	}

	.group-cover {
		height: 80px;
		position: relative;
	}

	.group-cover :global(.badge) {
		position: absolute;
		top: 0.75rem;
		right: 0.75rem;
	}

	.group-avatar {
		margin: -30px 1rem 0.75rem;
	}

	.group-info {
		padding: 0 1rem;
		text-align: center;
	}

	.group-stats {
		display: flex;
		justify-content: center;
		align-items: center;
		gap: 1.5rem;
		padding: 0.75rem 1rem;
		border-top: 1px solid rgba(255, 255, 255, 0.1);
		border-bottom: 1px solid rgba(255, 255, 255, 0.1);
	}

	.stat {
		text-align: center;
	}

	.activity-indicator {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.dot {
		width: 8px;
		height: 8px;
		border-radius: 50%;
	}

	.group-members-preview {
		display: flex;
		justify-content: center;
		align-items: center;
		padding: 0.75rem;
		gap: 0.25rem;
	}

	.more-members {
		width: 28px;
		height: 28px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.1);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.7rem;
		color: #888;
	}

	.group-footer {
		display: flex;
		gap: 0.5rem;
		padding: 0.75rem 1rem;
	}

	/* List View */
	.groups-list {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.group-row {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 1rem;
		background: rgba(255, 255, 255, 0.02);
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 12px;
		transition: all 0.2s ease;
	}

	.group-row:hover {
		background: rgba(255, 255, 255, 0.05);
		border-color: rgba(0, 255, 255, 0.3);
	}

	.row-avatar {
		flex-shrink: 0;
	}

	.row-info {
		flex: 1;
		min-width: 0;
	}

	.row-title {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.row-stats {
		text-align: center;
		min-width: 120px;
	}

	.row-actions {
		display: flex;
		gap: 0.5rem;
	}

	/* Modal */
	.create-form {
		display: flex;
		flex-direction: column;
		gap: 1.25rem;
	}

	.form-group {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
	}

	.form-group textarea {
		width: 100%;
		padding: 0.75rem;
		background: rgba(255, 255, 255, 0.05);
		border: 1px solid rgba(255, 255, 255, 0.2);
		border-radius: 8px;
		color: #fff;
		font-family: inherit;
		resize: vertical;
	}

	.form-group textarea:focus {
		outline: none;
		border-color: #00ffff;
	}

	.checkbox-label {
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
		cursor: pointer;
	}

	.checkbox-label input {
		width: 18px;
		height: 18px;
		accent-color: #00ffff;
	}

	.checkbox-label span {
		font-weight: 500;
	}

	.modal-footer {
		display: flex;
		justify-content: flex-end;
		gap: 0.75rem;
		margin-top: 1.5rem;
		padding-top: 1.5rem;
		border-top: 1px solid rgba(255, 255, 255, 0.1);
	}

	@media (max-width: 768px) {
		.groups-grid {
			grid-template-columns: 1fr;
		}

		.group-row {
			flex-wrap: wrap;
		}

		.row-stats {
			width: 100%;
			text-align: left;
			padding: 0.5rem 0;
		}

		.row-actions {
			width: 100%;
			justify-content: flex-end;
		}
	}
</style>