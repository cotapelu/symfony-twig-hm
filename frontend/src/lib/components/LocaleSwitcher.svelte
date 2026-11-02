<script lang="ts">
	import Icon from './atoms/Icon.svelte';
	import Typography from './atoms/Typography.svelte';

	export let locales = ['en', 'vi', 'es'];
	export let currentLocale = 'en';
	export let className = '';
	type LocaleChangeFn = (locale: string) => void;
	export let changeCallback: LocaleChangeFn | undefined = undefined;

	function handleLocaleChange(locale: string) {
		currentLocale = locale;
		if (changeCallback) changeCallback(locale);
	}
</script>

<div class="locale-switcher {className}" role="navigation" aria-label="Language switcher">
	<Typography variant="caption" className="locale-label">
			Language:
	</Typography>
	{#each locales as locale}
		<button
				class="locale-button {currentLocale === locale ? 'active' : ''}"
				onclick={() => handleLocaleChange(locale)}
				aria-label={`Switch to ${locale}`}
				aria-current={currentLocale === locale}
			>
			<Typography variant="caption" transform="uppercase">
					{locale}
			</Typography>
		</button>
	{/each}
</div>

<style>
	.locale-switcher {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.locale-label {
		color: var(--color-text-secondary, #6b7280);
		margin-right: 0.5rem;
	}

	.locale-button {
		padding: 0.375rem 0.75rem;
		background: transparent;
		border: 1px solid var(--color-border, #d1d5db);
		border-radius: 0.375rem;
		cursor: pointer;
		transition: all 0.15s;
		color: var(--color-text-primary, #111827);
	}

	.locale-button:hover {
		background: var(--color-gray-100, #f3f4f6);
	}

	.locale-button.active {
		background: var(--color-primary, #3b82f6);
		color: white;
	}

	@media (prefers-color-scheme: dark) {
		.locale-button {
			border-color: var(--color-gray-700, #374151);
			color: var(--color-text-primary-dark, #f9fafb);
		}

		.locale-button:hover {
			background: var(--color-gray-700, #374151);
		}

		.locale-button.active {
			background: var(--color-primary, #3b82f6);
			color: white;
		}
	}
</style>
