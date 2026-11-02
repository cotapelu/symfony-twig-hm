<script lang="ts">
	import { onMount } from 'svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import { fetchGroups } from '$lib/api';
	import { showToast } from '$lib/stores/global';

	interface GroupData {
		id: string;
		title: string;
		participants?: number;
	}

	export let groups: GroupData[] = [];

	let loading = true;
	let error: string | null = null;

	onMount(async () => {
		await loadGroups();
	});

	async function loadGroups() {
		try {
			loading = true;
			const data = await fetchGroups() as any;
			groups = Array.isArray(data) ? data : (data.groups || []);
		} catch (err) {
			error = (err as Error)?.message || 'Unknown error';
			showToast('Failed to load groups', 'error');
		} finally {
			loading = false;
		}
	}

	function handleGroupClick(group: GroupData) {
		showToast(`Open group: ${group.title}`, 'info');
	}
</script>

<Typography variant="h2">Groups</Typography>
{#if loading}
	<p>Loading groups...</p>
{:else if error}
	<p>Error: {error}</p>
{:else}
	<div class="groups-list">
		{#each groups as group}
			<div
				class="group-item"
				onclick={() => handleGroupClick(group)}
				onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); handleGroupClick(group); } }}
				role="button"
				tabindex="0"
				aria-label={`Open group: ${group.title}, ${group.participants || 0} members`}
			>
				<h3>{group.title}</h3>
				<p>{group.participants || 0} members</p>
			</div>
		{/each}
	</div>
{/if}

<style>
	.groups-list {
		display: grid;
		gap: 1rem;
	}

	.group-item {
		background: rgba(20, 20, 20, 0.8);
		border: 1px solid rgba(255, 0, 255, 0.3);
		border-radius: 10px;
		padding: 1rem;
		transition: all 0.3s ease;
	}

	.group-item:hover {
		transform: translateY(-3px);
		box-shadow: 0 5px 15px rgba(255, 0, 255, 0.3);
		border-color: #ff00ff;
	}

	.group-item h3 {
		color: #ff00ff;
		margin: 0 0 0.5rem 0;
		font-size: 1.1rem;
		text-shadow: 0 0 5px #ff00ff;
	}

	.group-item p {
		color: #ffffff;
		margin: 0;
		font-size: 0.9rem;
	}
</style>
