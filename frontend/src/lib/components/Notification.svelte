<script lang="ts">
	import { createEventDispatcher } from 'svelte';
	import Button from './atoms/Button.svelte';
	import Icon from './atoms/Icon.svelte';
	import Typography from './atoms/Typography.svelte';
	import Avatar from './Avatar.svelte';
	import Badge from './atoms/Badge.svelte';

	const dispatch = createEventDispatcher();

	export let id = '';
	export let title = '';
	export let message = '';
	export let type: 'info' | 'success' | 'warning' | 'error' = 'info';
	export let avatar = '';
	export let timestamp: Date | string = new Date();
	export let read = false;
	export let link = '';
	export let actionText = '';
	export let dismissible = true;
	export let duration: number | null = null;
	export let position: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right' = 'top-right';
	export let className = '';
	export let onClick: (() => void) | undefined = undefined;
	export let onDismiss: ((id: string) => void) | undefined = undefined;
	export let onAction: (() => void) | undefined = undefined;

	let timeoutId: ReturnType<typeof setTimeout>;

	function formatTime(ts: Date | string): string {
		const date = ts instanceof Date ? ts : new Date(ts);
		return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
	}

	function handleDismiss() {
		if (onDismiss) onDismiss(id);
		dispatch('dismiss', { id });
	}

	function handleClick() {
		if (onClick) onClick();
		dispatch('click', { id });
	}

	function handleAction() {
		if (onAction) onAction();
		dispatch('action', { id });
	}

	function getIcon(): string {
		switch (type) {
			case 'success': return 'check-circle';
			case 'warning': return 'exclamation-circle';
			case 'error': return 'x-circle';
			default: return 'info-circle';
		}
	}

	function getTypeClass(): string {
		return `notification-${type}`;
	}

	$: if (duration !== null && duration > 0) {
		if (timeoutId) clearTimeout(timeoutId);
		timeoutId = setTimeout(() => {
			handleDismiss();
		}, duration);
	}
</script>

<div class="notification {getTypeClass()} {className}" role="alert" aria-live="polite">
	<div class="notification-avatar">
		{#if avatar}
			<Avatar src={avatar} size="sm" />
		{:else}
			<Icon name={getIcon()} size="md" />
		{/if}
	</div>

	<div class="notification-content">
		<div class="notification-header">
			<Typography variant="h6" weight={600}>{title}</Typography>
			<Typography variant="caption" className="notification-time">
				{formatTime(timestamp)}
			</Typography>
		</div>
		<Typography variant="body" className="notification-message">
			{message}
		</Typography>
		{#if actionText}
			<Button variant="ghost" size="sm" onclick={handleAction}>
				{actionText}
			</Button>
		{/if}
	</div>

	{#if dismissible}
		<button
			class="notification-close"
			onclick={handleDismiss}
			aria-label="Close notification"
		>
			<Icon name="x" size="sm" />
		</button>
	{/if}

	{#if link}
		<a href={link} class="notification-link" aria-label="Open link">
			<Icon name="external-link" size="sm" />
		</a>
	{/if}
</div>

<style>
	.notification {
		display: flex;
		align-items: flex-start;
		gap: 0.75rem;
		padding: 0.75rem 1rem;
		border-radius: 0.5rem;
		background: var(--color-bg-surface, #ffffff);
		border-left: 3px solid var(--color-info, #3b82f6);
		box-shadow: var(--shadow-sm, 0 1px 2px rgba(0, 0, 0, 0.05));
		transition: all 0.15s;
		position: relative;
	}

	.notification-info {
		border-left-color: var(--color-info, #3b82f6);
	}

	.notification-success {
		border-left-color: var(--color-success, #10b981);
	}

	.notification-warning {
		border-left-color: var(--color-warning, #f59e0b);
	}

	.notification-error {
		border-left-color: var(--color-danger, #ef4444);
	}

	.notification-avatar {
		flex-shrink: 0;
		color: var(--color-text-secondary, #6b7280);
	}

	.notification-content {
		flex: 1;
		min-width: 0;
	}

	.notification-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 0.25rem;
	}

	.notification-time {
		opacity: 0.7;
		font-size: 0.75rem;
	}

	.notification-message {
		margin-bottom: 0.5rem;
		line-height: 1.4;
	}

	.notification-close {
		flex-shrink: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0.25rem;
		background: transparent;
		border: none;
		border-radius: 0.25rem;
		cursor: pointer;
		color: var(--color-text-secondary, #6b7280);
		transition: all 0.15s;
	}

	.notification-close:hover {
		background: var(--color-gray-100, #f3f4f6);
		color: var(--color-text-primary, #111827);
	}

	.notification-link {
		flex-shrink: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0.25rem;
		background: transparent;
		border: none;
		border-radius: 0.25rem;
		cursor: pointer;
		color: var(--color-text-secondary, #6b7280);
		transition: all 0.15s;
		text-decoration: none;
	}

	.notification-link:hover {
		background: var(--color-gray-100, #f3f4f6);
		color: var(--color-text-primary, #111827);
	}

	@media (prefers-color-scheme: dark) {
		.notification {
			background: var(--color-gray-800, #1f2937);
		}

		.notification-close,
		.notification-link {
			color: var(--color-text-secondary-dark, #9ca3af);
		}

		.notification-close:hover,
		.notification-link:hover {
			background: var(--color-gray-700, #374151);
			color: var(--color-text-primary-dark, #f9fafb);
		}
	}
</style>
