<script lang="ts">

	import Icon from './atoms/Icon.svelte';
	import Typography from './atoms/Typography.svelte';



  export let direction: 'ltr' | 'rtl' = 'ltr';
  let rootElement: HTMLElement;
  export let className = '';
  export let onChange: ((direction: 'ltr' | 'rtl') => void) | undefined = undefined;

	function toggleDirection() {
		direction = direction === 'ltr' ? 'rtl' : 'ltr';
		if (onChange) onChange(direction);
		rootElement?.dispatchEvent(new CustomEvent('toggle', { detail: { direction }, bubbles: true }));
	}
</script>

<div class="rtl-support {direction} {className}" dir={direction} role="button" on:click={toggleDirection} aria-label="Toggle RTL/LTR" title="Toggle RTL/LTR" bind:this={rootElement}>
	<Typography variant="caption">
			{direction === 'rtl' ? 'RTL' : 'LTR'}
	</Typography>
	<Icon name="repeat" size="sm" />
</div>

<style>
	.rtl-support {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.375rem 0.75rem;
		background: var(--color-gray-50, #f9fafb);
		border: 1px solid var(--color-border, #d1d5db);
		border-radius: 0.375rem;
		cursor: pointer;
		transition: all 0.15s;
	}

	.rtl-support:hover {
		background: var(--color-gray-100, #f3f4f6);
	}

	@media (prefers-color-scheme: dark) {
		.rtl-support {
			background: var(--color-gray-800, #1f2937);
			border-color: var(--color-gray-700, #374151);
		}

		.rtl-support:hover {
			background: var(--color-gray-700, #374151);
		}
	}
</style>
