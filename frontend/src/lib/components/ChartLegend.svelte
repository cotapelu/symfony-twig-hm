<script lang="ts">
	import { createEventDispatcher } from 'svelte';
	import Typography from './atoms/Typography.svelte';

	const dispatch = createEventDispatcher();

	export let data: Array<{ name: string; value: number; color: string }> = [];
	export let type: 'bar' | 'line' | 'pie' = 'bar';
	export let horizontal = false;
	export const showLegend = true;
	export const height = 300;
	export let className = '';

	function getChartType(): string {
		return `chart-${type}`;
	}
</script>

<div class="chart-legend {className}" class:horizontal>
	{#each data as item}
		<div class="legend-item">
			<span class="legend-color" style="background: {item.color}"></span>
			<Typography variant="caption">{item.name}</Typography>
		</div>
	{/each}
</div>

<style>
	.chart-legend {
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
		padding: 0.5rem;
	}

	.chart-legend.horizontal {
		flex-direction: row;
	}

	.legend-item {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.legend-color {
		width: 12px;
		height: 12px;
		border-radius: 50%;
		flex-shrink: 0;
	}

	.legend-item :global(.typography) {
		margin: 0;
	}

	.legend-item:not(:first-child):not(:last-child) {
		margin-left: 0.5rem;
		border-left: 1px solid var(--color-border, #d1d5db);
		padding-left: 0.5rem;
	}
</style>
