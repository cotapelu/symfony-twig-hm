<script lang="ts">
	import { createEventDispatcher } from 'svelte';

	const dispatch = createEventDispatcher();

	export let options: Array<{ value: string; label: string }> = [];
	export let value = '';
	export let className = '';

	function handleSelect(optionValue: string) {
		value = optionValue;
		dispatch('change', { value: optionValue });
	}
</script>

<div class="segmented {className}">
	{#each options as option}
		<button
			class="segmented-item {value === option.value ? 'active' : ''}"
			onclick={() => handleSelect(option.value)}
			aria-label={option.label}
			aria-selected={value === option.value}
		>
			{option.label}
		</button>
	{/each}
</div>

<style>
	.segmented {
		display: flex;
		background: var(--color-gray-100, #f3f4f6);
		border-radius: 0.375rem;
		overflow: hidden;
	}

	.segmented-item {
		flex: 1;
		padding: 0.5rem 1rem;
		background: transparent;
		border: none;
		cursor: pointer;
		color: var(--color-text-primary, #111827);
		transition: all 0.15s;
		font-weight: 500;
		position: relative;
	}

	.segmented-item:first-child {
		border-radius: 0.375rem 0 0 0 0.375rem;
	}

	.segmented-item:last-child {
		border-radius: 0 0 0.375rem 0.375rem;
	}

	.segmented-item.active {
		background: var(--color-bg-surface, #ffffff);
		box-shadow: var(--shadow-sm, 0 1px 2px rgba(0, 0, 0, 0.05));
		z-index: 1;
	}

	.segmented-item:not(:first-child):not(:last-child):not(.active) {
		margin-left: -1px;
	}

	.segmented-item:not(:last-child):not(.active) {
		border-left: 1px solid var(--color-border, #e5e7eb);
	}

	.segmented-item.active:not(:first-child):not(:last-child) {
		margin-left: -1px;
		border-left: 1px solid var(--color-border, #e5e7eb);
	}

	.segmented-item:hover:not(.active) {
		background: var(--color-gray-50, #f9fafb);
	}

	@media (prefers-color-scheme: dark) {
		.segmented {
			background: var(--color-gray-800, #1f2937);
		}

		.segmented-item.active {
			background: var(--color-gray-700, #374151);
		}

		.segmented-item:hover:not(.active) {
			background: var(--color-gray-700, #374151);
		}

		.segmented-item:not(:first-child):not(:last-child):not(.active) {
			border-left-color: var(--color-gray-600, #4b5563);
		}

		.segmented-item.active:not(:first-child):not(:last-child) {
			border-left-color: var(--color-gray-600, #4b5563);
		}
	}
</style>
