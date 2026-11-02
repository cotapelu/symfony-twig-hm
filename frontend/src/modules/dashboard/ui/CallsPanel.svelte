<script lang="ts">
	import { onMount } from 'svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import { fetchCalls, joinCall, startCall } from '$lib/api';
	import { showToast } from '$lib/stores/global';

	interface CallData {
		id: string;
		status: string;
		participants?: number;
	}

	export let calls: CallData[] = [];

	let loading = true;
	let error: string | null = null;

	onMount(async () => {
		await loadCalls();
	});

	async function loadCalls() {
		try {
			loading = true;
			const data = await fetchCalls() as any;
			calls = Array.isArray(data) ? data : (data.active_calls || []);
		} catch (err) {
			error = (err as Error)?.message || 'Unknown error';
			showToast('Failed to load calls', 'error');
		} finally {
			loading = false;
		}
	}

	async function startNewCall() {
		try {
			await startCall('video' as 'audio' | 'video');
			showToast('Call started!', 'success');
			await loadCalls();
		} catch (err) {
			showToast('Failed to start call: ' + (err as Error).message, 'error');
		}
	}

	async function handleJoinCall(callId: string) {
		try {
			await joinCall(callId);
			showToast('Joining call...', 'info');
		} catch (err) {
			showToast('Failed to join call: ' + (err as Error).message, 'error');
		}
	}
</script>

<Typography variant="h2">Voice & Video Calls</Typography>
{#if loading}
	<p>Loading active calls...</p>
{:else}
	<div class="calls-list">
		{#each calls as call}
			<div
				class="call-item"
				onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showToast(`Joining call ${call.id}...`, 'info'); } }}
				role="button"
				tabindex="0"
				aria-label={`Call ${call.id}, status: ${call.status || 'active'}, ${call.participants || 0} participants`}
			>
				<h3>Call #{call.id}</h3>
				<p>Status: {call.status || 'active'}</p>
				<p>{call.participants || 0} participants</p>
				<Button
					onclick={() => handleJoinCall(call.id)}
				>
					Join Call
				</Button>
			</div>
		{/each}
		{#if calls.length === 0}
			<div class="no-calls">
				<p>No active calls</p>
				<Button onclick={startNewCall}>
					Start Call
				</Button>
			</div>
		{/if}
	</div>
{/if}

<style>
	.calls-list {
		display: grid;
		gap: 1rem;
	}

	.call-item {
		background: rgba(20, 20, 20, 0.8);
		border: 1px solid rgba(255, 0, 255, 0.3);
		border-radius: 10px;
		padding: 1rem;
		transition: all 0.3s ease;
	}

	.call-item h3 {
		color: #ff00ff;
		margin: 0 0 0.5rem 0;
		font-size: 1.1rem;
		text-shadow: 0 0 5px #ff00ff;
	}

	.call-item p {
		color: #ffffff;
		margin: 0 0 0.5rem 0;
		font-size: 0.9rem;
	}

	.no-calls {
		text-align: center;
		padding: 2rem;
		color: #cccccc;
	}
</style>
