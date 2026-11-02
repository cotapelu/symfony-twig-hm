<script lang="ts">
	import { createEventDispatcher } from 'svelte';
	import Avatar from '../Avatar.svelte';
	import Icon from '../atoms/Icon.svelte';
	import Typography from '../atoms/Typography.svelte';
	import Badge from '../atoms/Badge.svelte';

	const dispatch = createEventDispatcher();

	export let id = '';
	export let content = '';
	export let sender = '';
	export let senderAvatar = '';
	export let timestamp: Date | string = new Date();
	export let read = true;
	export let sent = true;
	export let type: 'text' | 'image' | 'file' | 'voice' | 'video' = 'text';
	export let fileUrl = '';
	export let fileName = '';
	export let fileSize = '';
	export const thumbnail = '';
	export let isOwn = false;
	export let className = '';
	export let onReply: (id: string) => void = () => {};
	export let onForward: (id: string) => void = () => {};
	export let onDelete: (id: string) => void = () => {};
	export let onReaction: (id: string, emoji: string) => void = () => {};
	export let reactions: Array<{ emoji: string; count: number }> = [];

	function formatTimestamp(ts: Date | string): string {
		const date = ts instanceof Date ? ts : new Date(ts);
		const now = new Date();
		const diff = now.getTime() - date.getTime();

		if (diff < 60000) return 'Just now';
		if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
		if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
		if (diff < 604800000) return `${Math.floor(diff / 86400000)}d ago`;

		return date.toLocaleDateString();
	}

	function handleReply() {
		if (onReply) onReply(id);
		dispatch('reply', { id });
	}

	function handleForward() {
		if (onForward) onForward(id);
		dispatch('forward', { id });
	}

	function handleDelete() {
		if (onDelete) onDelete(id);
		dispatch('delete', { id });
	}

	function handleReaction(emoji: string) {
		if (onReaction) onReaction(id, emoji);
		dispatch('reaction', { id, emoji });
	}
</script>

<div class="message {type} {isOwn ? 'own' : 'other'} {className}" role="article" aria-label="Message">
	<div class="message-avatar">
		<Avatar src={senderAvatar} alt={sender} size="sm" />
	</div>

	<div class="message-content-wrapper">
		<div class="message-header">
			<Typography variant="caption" weight={600}>{sender}</Typography>
			<Typography variant="caption" className="message-time">
				{formatTimestamp(timestamp)}
			</Typography>
			{#if !sent}
				<Badge status="warning" size="sm">Sending...</Badge>
			{/if}
			{#if sent && !read && isOwn}
				<Badge status="info" size="sm">Unread</Badge>
			{/if}
		</div>

		<div class="message-content">
			{#if type === 'text'}
				<Typography>{content}</Typography>
			{:else if type === 'image'}
				<img src={fileUrl} alt={fileName} class="message-image" loading="lazy" />
			{:else if type === 'file'}
				<div class="message-file">
					<Icon name="file" size="lg" />
					<Typography>{fileName}</Typography>
					<Typography variant="caption">{fileSize}</Typography>
				</div>
			{:else if type === 'voice'}
				<audio src={fileUrl} controls class="message-audio"></audio>
			{:else if type === 'video'}
				<video src={fileUrl} class="message-video" controls preload="none"><track kind="captions" /></video>
			{/if}
		</div>

		{#if reactions.length > 0}
			<div class="message-reactions">
				{#each reactions as reaction}
					<button
						class="reaction-badge"
						onclick={() => handleReaction(reaction.emoji)}
						aria-label="React with {reaction.emoji}"
					>
						{reaction.emoji}
						<Badge size="sm">{reaction.count}</Badge>
					</button>
				{/each}
			</div>
		{/if}
	</div>

	<div class="message-actions">
		<button
			class="action-button"
			onclick={handleReply}
			aria-label="Reply"
			title="Reply"
		>
			<Icon name="reply" size="sm" />
		</button>
		<button
			class="action-button"
			onclick={handleForward}
			aria-label="Forward"
			title="Forward"
		>
			<Icon name="forward" size="sm" />
		</button>
		{#if isOwn}
			<button
				class="action-button danger"
				onclick={handleDelete}
				aria-label="Delete"
				title="Delete"
			>
				<Icon name="trash" size="sm" />
			</button>
		{/if}
	</div>
</div>

<style>
	.message {
		display: flex;
		gap: 0.5rem;
		padding: 0.75rem;
		margin-bottom: 0.5rem;
		border-radius: 0.75rem;
		max-width: 80%;
		align-self: flex-start;
	}

	.message.own {
		margin-left: auto;
		background: var(--color-primary-light, #e0e7ff);
	}

	.message.other {
		background: var(--color-gray-50, #f9fafb);
	}

	.message-avatar {
		flex-shrink: 0;
	}

	.message-content-wrapper {
		flex: 1;
		min-width: 0;
	}

	.message-header {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		margin-bottom: 0.25rem;
	}

	.message-time {
		opacity: 0.7;
		font-size: 0.75rem;
	}

	.message-content {
		word-wrap: break-word;
		word-break: break-word;
	}

	.message-image {
		max-width: 100%;
		border-radius: 0.5rem;
		cursor: pointer;
	}

	.message-file {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem;
		background: rgba(0, 0, 0, 0.05);
		border-radius: 0.5rem;
	}

	.message-audio,
	.message-video {
		max-width: 100%;
		border-radius: 0.5rem;
	}

	.message-reactions {
		display: flex;
		gap: 0.25rem;
		margin-top: 0.5rem;
		flex-wrap: wrap;
	}

	.reaction-badge {
		display: flex;
		align-items: center;
		gap: 0.25rem;
		padding: 0.25rem 0.5rem;
		border: 1px solid var(--color-border, #d1d5db);
		border-radius: 1rem;
		background: var(--color-bg-surface, #ffffff);
		cursor: pointer;
		transition: all 0.15s;
	}

	.reaction-badge:hover {
		background: var(--color-gray-100, #f3f4f6);
	}

	.message-actions {
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
		opacity: 0;
		transition: opacity 0.15s;
	}

	.message:hover .message-actions {
		opacity: 1;
	}

	.action-button {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0.375rem;
		background: transparent;
		border: none;
		border-radius: 0.375rem;
		cursor: pointer;
		color: var(--color-text-secondary, #6b7280);
		transition: all 0.15s;
	}

	.action-button:hover {
		background: rgba(0, 0, 0, 0.05);
		color: var(--color-text-primary, #111827);
	}

	.action-button.danger:hover {
		background: var(--color-danger-light, #fee2e2);
		color: var(--color-danger, #ef4444);
	}

	@media (prefers-color-scheme: dark) {
		.message.own {
			background: var(--color-primary-dark, #1e40af);
		}

		.message.other {
			background: var(--color-gray-800, #1f2937);
		}

		.reaction-badge {
			background: var(--color-gray-800, #1f2937);
			border-color: var(--color-gray-700, #374151);
		}

		.reaction-badge:hover {
			background: var(--color-gray-700, #374151);
		}

		.action-button {
			color: var(--color-text-secondary-dark, #9ca3af);
		}

		.action-button:hover {
			background: rgba(255, 255, 255, 0.05);
			color: var(--color-text-primary-dark, #f9fafb);
		}
	}
</style>
