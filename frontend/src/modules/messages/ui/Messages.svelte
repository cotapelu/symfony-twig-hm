<script lang="ts">
	import { onMount } from 'svelte';
	import { _ } from 'svelte-i18n';
	import ConversationInsights from '$lib/components/ConversationInsights.svelte';
	import ConversationList from './ConversationList.svelte';
	import MessageInput from './MessageInput.svelte';
	import MessageList from './MessageList.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import Icon from '$lib/components/atoms/Icon.svelte';
	import Empty from '$lib/components/Empty.svelte';

	let loading = false;

	function scrollToBottom() {
		const messageList = document.querySelector('.message-list');
		if (messageList) {
			messageList.scrollTop = messageList.scrollHeight;
		}
	}

	onMount(async () => {
		loading = true;
		await new Promise(resolve => setTimeout(resolve, 500));
		loading = false;
	});
</script>

<div class="messages-page">
	<aside class="sidebar">
		<div class="sidebar-header">
			<Typography variant="h3">Messages</Typography>
			<button class="new-chat-btn">
				<Icon name="plus" size="sm" />
			</button>
		</div>
		<div class="sidebar-search">
			<input type="text" placeholder="Search conversations..." />
		</div>
		<ConversationList />
	</aside>

	<main class="main-content">
		{#if loading}
			<div class="loading-overlay">
				<div class="spinner"></div>
				<Typography variant="body" color="secondary">Loading conversations...</Typography>
			</div>
		{:else}
			<MessageList {scrollToBottom} />
			<MessageInput />
			<section class="conversation-insights">
				<ConversationInsights conversationId="demo" isVisible={true} />
			</section>
		{/if}
	</main>
</div>

<style>
	.messages-page {
		display: flex;
		height: 100vh;
		background: #0a0a0a;
	}

	.sidebar {
		width: 320px;
		border-right: 1px solid rgba(255, 255, 255, 0.1);
		display: flex;
		flex-direction: column;
		background: rgba(15, 15, 15, 0.95);
	}

	.sidebar-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1rem 1.25rem;
		border-bottom: 1px solid rgba(255, 255, 255, 0.1);
	}

	.sidebar-header h3 {
		color: #00ffff;
		margin: 0;
		font-size: 1.25rem;
	}

	.new-chat-btn {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		background: rgba(0, 255, 255, 0.1);
		border: 1px solid rgba(0, 255, 255, 0.3);
		color: #00ffff;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.2s ease;
	}

	.new-chat-btn:hover {
		background: rgba(0, 255, 255, 0.2);
	}

	.sidebar-search {
		padding: 0.75rem 1.25rem;
		border-bottom: 1px solid rgba(255, 255, 255, 0.1);
	}

	.sidebar-search input {
		width: 100%;
		padding: 0.75rem 1rem;
		background: rgba(255, 255, 255, 0.05);
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 10px;
		color: #fff;
		font-size: 0.9rem;
	}

	.sidebar-search input:focus {
		outline: none;
		border-color: #00ffff;
	}

	.main-content {
		flex: 1;
		display: flex;
		flex-direction: column;
		position: relative;
	}

	.loading-overlay {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 1rem;
	}

	.spinner {
		width: 40px;
		height: 40px;
		border: 3px solid rgba(255, 255, 255, 0.1);
		border-top-color: #00ffff;
		border-radius: 50%;
		animation: spin 1s linear infinite;
	}

	@keyframes spin {
		to {
			transform: rotate(360deg);
		}
	}

	.conversation-insights {
		position: absolute;
		right: 0;
		top: 0;
		bottom: 80px;
		width: 320px;
		background: rgba(15, 15, 15, 0.95);
		border-left: 1px solid rgba(255, 255, 255, 0.1);
		overflow-y: auto;
	}

	@media (max-width: 1024px) {
		.sidebar {
			width: 280px;
		}

		.conversation-insights {
			display: none;
		}
	}

	@media (max-width: 768px) {
		.sidebar {
			position: fixed;
			left: 0;
			top: 0;
			bottom: 0;
			z-index: 100;
			transform: translateX(-100%);
			transition: transform 0.3s ease;
		}

		.sidebar.open {
			transform: translateX(0);
		}
	}
</style>