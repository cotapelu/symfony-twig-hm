<script lang="ts">
	import Icon from './atoms/Icon.svelte';
	import Button from './atoms/Button.svelte';
	import Typography from './atoms/Typography.svelte';

	export let status: 'info' | 'success' | 'warning' | 'error' = 'info';
	export let title = '';
	export let description = '';
	export let icon = '';
	export let buttonText = '';
	export let actionCallback: (() => void) | undefined = undefined;
	export let className = '';
	export let showAction = false;

	function getIcon(): string {
		if (icon) return icon;
		switch (status) {
			case 'success': return 'check-circle';
			case 'warning': return 'exclamation-circle';
			case 'error': return 'x-circle';
			default: return 'info-circle';
		}
	}

	function getStatusClass(): string {
		return `result-${status}`;
	}

	function handleAction() {
		if (actionCallback) actionCallback();
	}
</script>

<div class="result {getStatusClass()} {className}" role="alert">
	<div class="result-icon">
		<Icon name={getIcon()} size="xl" />
	</div>

	<div class="result-content">
		<Typography variant="h3" weight={600}>{title}</Typography>
		<Typography variant="body" className="result-description">
			{description}
		</Typography>
		{#if showAction && buttonText}
			<Button variant="primary" onclick={handleAction} text={buttonText} />
		{/if}
	</div>
</div>

<style>
	.result {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 2rem;
		text-align: center;
		border-radius: 0.5rem;
		min-height: 200px;
	}

	.result-icon {
		margin-bottom: 1rem;
	}

	.result.info .result-icon {
		color: var(--color-info, #3b82f6);
	}

	.result.success .result-icon {
		color: var(--color-success, #10b981);
	}

	.result.warning .result-icon {
		color: var(--color-warning, #f59e0b);
	}

	.result.error .result-icon {
		color: var(--color-danger, #ef4444);
	}

	.result-content {
		max-width: 400px;
	}

	.result-description {
		margin-bottom: 1rem;
		line-height: 1.5;
	}

	@media (prefers-color-scheme: dark) {
		.result {
			background: var(--color-gray-800, #1f2937);
		}
	}
</style>
