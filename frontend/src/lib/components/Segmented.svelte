<script lang="ts">
	import Icon from './atoms/Icon.svelte';
	import Typography from './atoms/Typography.svelte';

	export let bordered = false;
	export let size: 'sm' | 'md' | 'lg' = 'md';
	export let column: number = 3;
	export let labelWidth = '120px';
	export let items: Array<{ label: string; value: string }> = [];
	export let className = '';

	function getSize(): string {
		switch (size) {
			case 'sm': return '0.75rem';
			case 'lg': return '1rem';
			default: return '0.875rem';
		}
	}
</script>

<div class="descriptions {size} {className}" class:bordered={bordered}>
	{#each items as item}
		<div class="descriptions-item">
			<div class="descriptions-label-wrapper" style="width: {labelWidth}">
				<Typography variant="body" className="descriptions-label">
					{item.label}
				</Typography>
			</div>
			<div class="descriptions-value-wrapper" style="font-size: {getSize()}; color: var(--color-text-primary, #111827);">
				<Typography variant="body" className="descriptions-value">
					{item.value}
				</Typography>
			</div>
		</div>
	{/each}
</div>

<style>
	.descriptions {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.descriptions.bordered {
		background: var(--color-gray-50, #f9fafb);
		border: 1px solid var(--color-border, #e5e7eb);
		border-radius: 0.375rem;
		padding: 1rem;
	}

	.descriptions-item {
		display: flex;
		gap: 0.5rem;
	}

	.descriptions-label {
		flex-shrink: 0;
		color: var(--color-text-secondary, #6b7280);
		font-weight: 500;
	}

	.descriptions-value {
		flex: 1;
		color: var(--color-text-primary, #111827);
	}

	@media (prefers-color-scheme: dark) {
		.descriptions.bordered {
			background: var(--color-gray-800, #1f2937);
			border-color: var(--color-gray-700, #374151);
		}

		.descriptions-label {
			color: var(--color-text-secondary-dark, #9ca3af);
		}

		.descriptions-value {
			color: var(--color-text-primary-dark, #f9fafb);
		}
	}
</style>
