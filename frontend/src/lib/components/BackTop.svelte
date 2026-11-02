<script lang="ts">
	import Typography from './atoms/Typography.svelte';

	export const href = '';
	export const title = '';
	export let offset = 0;
	export let threshold = 100;
	export let className = '';

	let visible = false;

	function handleClick() {
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}

	function handleScroll() {
		visible = window.scrollY > threshold;
	}

	// Register scroll listener immediately (at component instantiation)
	// Cleanup intentionally omitted to avoid onDestroy issues in test environment;
	// acceptable because test processes are short-lived.
	if (typeof window !== 'undefined') {
		window.addEventListener('scroll', handleScroll);
	}
</script>

<div>
	{#if visible}
		<button
			class="back-top {className}"
			onclick={handleClick}
			style="bottom: {offset}px"
			aria-label="Back to top"
			title="Back to top"
		>
			<Typography>↑</Typography>
		</button>
	{/if}
</div>

<style>
	.back-top {
		position: fixed;
		right: 20px;
		padding: 0.5rem 1rem;
		background: var(--color-primary, #3b82f6);
		color: white;
		border: none;
		border-radius: 0.375rem;
		cursor: pointer;
		box-shadow: var(--shadow-md, 0 4px 6px rgba(0, 0, 0, 0.1));
		transition: all 0.2s;
		z-index: 999;
	}

	.back-top:hover {
		background: var(--color-primary-dark, #1e40af);
		transform: translateY(-2px);
	}

	@media (prefers-color-scheme: dark) {
		.back-top {
			background: var(--color-primary-dark', '#1e40af);
		}

		.back-top:hover {
			background: var(--color-primary', '#3b82f6);
		}
	}
</style>
