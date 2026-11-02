<script lang="ts">
	import Icon from './Icon.svelte';
	import Typography from './Typography.svelte';

	export let date = new Date();
	export let format: 'short' | 'long' | 'relative' = 'relative';
	export let showTime = true;
	export let showDate = true;
	export let className = '';

	function formatDate(): string {
		const d = date instanceof Date ? date : new Date(date);
		const now = new Date();
		const diff = now.getTime() - d.getTime();
		const seconds = Math.floor(diff / 1000);
		const minutes = Math.floor(seconds / 60);
		const hours = Math.floor(minutes / 60);
		const days = Math.floor(hours / 24);

		if (format === 'relative') {
			if (seconds < 60) return 'Just now';
			if (minutes < 60) return `${minutes}m ago`;
			if (hours < 24) return `${hours}h ago`;
			return `${days}d ago`;
		}

		return d.toLocaleDateString([], {
			month: 'short',
			day: 'numeric',
			hour: '2-digit',
			minute: '2-digit'
		});
	}
</script>

<div class="date-time {className}">
	{#if showDate}
		<Typography variant="body" className="date-text">
			{formatDate()}
		</Typography>
	{/if}
	{#if showTime}
		<Typography variant="caption" className="time-text">
			{date instanceof Date ? date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''}
		</Typography>
	{/if}
</div>

<style>
	.date-time {
		display: flex;
		align-items: center;
		gap: 0.25rem;
	}

	.date-text {
		color: var(--color-text-primary, #111827);
	}

	.time-text {
		color: var(--color-text-secondary, #6b7280);
	}

	@media (prefers-color-scheme: dark) {
		.date-text {
			color: var(--color-text-primary-dark, #f9fafb);
		}

		.time-text {
			color: var(--color-text-secondary-dark, #9ca3af);
		}
	}
</style>
