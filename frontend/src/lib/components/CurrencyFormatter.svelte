<script lang="ts">
	import { createEventDispatcher } from 'svelte';
	import Icon from './atoms/Icon.svelte';
	import Typography from './atoms/Typography.svelte';

	const dispatch = createEventDispatcher();

	export let amount = 0;
	export let currency = 'USD';
	export let display = 'symbol';
	export let className = '';
	export let onClick: ((amount: number) => void) | undefined = undefined;

	function formatCurrency(): string {
		const formatter = new Intl.NumberFormat('en-US', {
			style: 'currency',
			currency: currency,
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		});

		if (display === 'symbol') {
			return formatter.format(amount);
		} else if (display === 'code') {
			return `${currency} ${amount.toFixed(2)}`;
		} else {
			return amount.toFixed(2);
		}
	}

	function handleClick() {
		dispatch('click', { amount, currency });
		if (onClick) onClick(amount);
	}
</script>

<div
  class="currency-formatter {className}"
  onclick={handleClick}
  onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { handleClick(); e.preventDefault(); } }}
  role="button"
  tabindex="0"
  aria-label="Currency amount"
>
	<Typography variant="h6" weight={600}>
			{formatCurrency()}
	</Typography>
	<Typography variant="caption" className="currency-code">
			{currency}
	</Typography>
</div>

<style>
	.currency-formatter {
		display: inline-flex;
		flex-direction: column;
		align-items: flex-end;
		gap: 0.125rem;
		padding: 0.375rem 0.75rem;
		background: var(--color-gray-50, #f9fafb);
		border-radius: 0.375rem;
		cursor: pointer;
		transition: all 0.15s;
	}

	.currency-formatter:hover {
		background: var(--color-gray-100, #f3f4f6);
		transform: translateY(-1px);
	}

	.currency-formatter:active {
		transform: translateY(0);
	}

	.currency-code {
		color: var(--color-text-secondary, #6b7280);
		font-size: 0.75rem;
	}

	@media (prefers-color-scheme: dark) {
		.currency-formatter {
			background: var(--color-gray-800, #1f2937);
		}

		.currency-formatter:hover {
			background: var(--color-gray-700, #374151);
		}

		.currency-code {
			color: var(--color-text-secondary-dark, #9ca3af);
		}
	}
</style>
