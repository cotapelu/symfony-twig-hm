<script lang="ts">
	import Typography from './atoms/Typography.svelte';

	export let number = 0;
	export let decimals = 0;
	export let showCommas = true;
	export let className = '';

	function formatNumber(): string {
		const formatter = new Intl.NumberFormat('en-US', {
			minimumFractionDigits: decimals,
			maximumFractionDigits: decimals,
			useGrouping: showCommas,
		});

		return formatter.format(number);
	}

	function getNumberColor(): string {
		const absNumber = Math.abs(number);
		if (absNumber >= 1000000) return 'var(--color-danger, #ef4444)';
		if (absNumber >= 1000) return 'var(--color-warning, #f59e0b)';
		if (absNumber >= 0) return 'var(--color-success, #10b981)';
		return 'var(--color-text-primary, #111827)';
	}
</script>

<div class="number-formatter {className}" role="text" aria-label="Formatted number">
	<span style="color: {getNumberColor()}">
		<Typography variant="body" weight={500}>
			{formatNumber()}
		</Typography>
	</span>
	<slot />
</div>

<style>
	.number-formatter {
		display: inline-block;
		font-family: monospace;
	}
</style>
