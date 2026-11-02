<script lang="ts">
	import Icon from './Icon.svelte';
	import Typography from './Typography.svelte';

	export let percent = 0;
	export let color = ''; // custom bar color; falls back to the status color
	export let status: 'active' | 'success' | 'exception' = 'active';
	export let showInfo = true;
	export let infoText = '';
	export let className = '';
	export let strokeWidth = 6;
	export let trailColor = '';
	export let size: 'sm' | 'md' | 'lg' = 'md';

	function getSize(): string {
		switch (size) {
			case 'sm': return '40px';
			case 'lg': return '100px';
			default: return '60px';
		}
	}

	function getStatusColor(): string {
		switch (status) {
			case 'success': return 'var(--color-success, #10b981)';
			case 'exception': return 'var(--color-danger, #ef4444)';
			default: return 'var(--color-primary, #3b82f6)';
		}
	}
</script>

<div class="progress {status} {size} {className}" role="progressbar" aria-label="Progress indicator" aria-valuenow={percent} aria-valuemin="0" aria-valuemax="100">
	<div class="progress-circle">
		<svg
			class="progress-svg"
			viewBox="0 0 100 100"
			style="--size: {getSize()}; --stroke-width: {strokeWidth}; --progress: {percent}; --color: {color || getStatusColor()}"
		>
			<circle class="progress-trail" cx="50" cy="50" r={50 - strokeWidth/2} />
			<circle class="progress-path" cx="50" cy="50" r={50 - strokeWidth/2} />
		</svg>
		{#if showInfo}
			<div class="progress-text">
				<Typography variant="caption" weight={700}>{percent}%</Typography>
			</div>
		{/if}
	</div>
	{#if infoText}
		<Typography variant="caption" className="progress-info">
			{infoText}
		</Typography>
	{/if}
</div>

<style>
	.progress {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.5rem;
	}

	.progress-circle {
		position: relative;
		width: var(--size);
		height: var(--size);
	}

	.progress-svg {
		width: 100%;
		height: 100%;
		transform: rotate(-90deg);
	}

	.progress-trail,
	.progress-path {
		fill: none;
		stroke-width: var(--stroke-width);
	}

	.progress-trail {
		stroke: var(--color-gray-200, #e5e7eb);
	}

	.progress-path {
		stroke: var(--color);
		stroke-linecap: round;
		transition: stroke-dashoffset 0.3s ease;
		stroke-dasharray: calc(2 * PI * (50 - var(--stroke-width) / 2));
		stroke-dashoffset: calc(2 * PI * (50 - var(--stroke-width) / 2) * (100 - var(--progress)) / 100);
	}

	.progress-text {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}

	.progress-info {
		text-align: center;
	}

	.progress.success .progress-path {
		stroke: var(--color-success, #10b981);
	}

	.progress.exception .progress-path {
		stroke: var(--color-danger, #ef4444);
	}

	@media (prefers-color-scheme: dark) {
		.progress-trail {
			stroke: var(--color-gray-700, #374151);
		}
	}
</style>
