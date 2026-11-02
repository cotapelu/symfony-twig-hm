<script lang="ts">
	import { onMount } from 'svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import { fetchConversations } from '$lib/api';
	import { showToast } from '$lib/stores/global';

	interface ConversationData {
		id: string;
		title: string;
		lastMessage?: string;
		unread: number;
	}

	export let conversations: ConversationData[] = [];

	let loading = true;
	let error: string | null = null;

	onMount(async () => {
		await loadConversations();
	});

	async function loadConversations() {
		try {
			loading = true;
			const data = await fetchConversations() as any;
			conversations = Array.isArray(data) ? data : (data.conversations || []);
		} catch (err) {
			error = (err as Error)?.message || 'Unknown error';
			showToast('Failed to load conversations', 'error');
		} finally {
			loading = false;
		}
	}

	function handleConversationClick(conversation: ConversationData) {
		showToast(`Open conversation: ${conversation.title}`, 'info');
	}
</script>

<Typography variant="h2">Messages</Typography>
{#if loading}
	<p>Loading conversations...</p>
{:else if error}
	<p>Error: {error}</p>
{:else}
	<div class="messages-list">
		{#each conversations as conversation}
			<div
				class="message-item"
				onclick={() => handleConversationClick(conversation)}
				onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); handleConversationClick(conversation); } }}
				role="button"
				tabindex="0"
				aria-label={`Open conversation: ${conversation.title}, ${conversation.unread} unread messages`}
			>
				<h3>{conversation.title}</h3>
				<p>{conversation.lastMessage || 'No messages yet'}</p>
				<span class="unread-count" aria-label={`${conversation.unread} unread messages`}>{conversation.unread} unread</span>
			</div>
		{/each}
	</div>
{/if}

<style>
	.messages-list {
		display: grid;
		gap: 1rem;
	}

	.message-item {
		background: rgba(20, 20, 20, 0.8);
		border: 1px solid rgba(255, 0, 255, 0.3);
		border-radius: 10px;
		padding: 1rem;
		transition: all 0.3s ease;
	}

	.message-item:hover {
		transform: translateY(-3px);
		box-shadow: 0 5px 15px rgba(255, 0, 255, 0.3);
		border-color: #ff00ff;
	}

	.message-item h3 {
		color: #ff00ff;
		margin: 0 0 0.5rem 0;
		font-size: 1.1rem;
		text-shadow: 0 0 5px #ff00ff;
	}

	.message-item p {
		color: #ffffff;
		margin: 0 0 0.5rem 0;
		font-size: 0.9rem;
	}

	.message-item span {
		color: #00ffff;
		font-size: 0.8rem;
		background: rgba(0, 255, 255, 0.1);
		padding: 0.25rem 0.5rem;
		border-radius: 4px;
		border: 1px solid rgba(0, 255, 255, 0.3);
	}
</style>
