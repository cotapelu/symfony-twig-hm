<script lang="ts">
	import { createEventDispatcher, onMount } from 'svelte';

	const dispatch = createEventDispatcher();

	export let href = '';
	export let title = '';
	export let target: '_blank' | '_self' | '_parent' | '_top' = '_blank';
	export let className = '';
	export let children: string = '';

	function handleClick() {
		dispatch('click', { href });
	}

	onMount(() => {
		const link = document.querySelector(`a[href="${href}"]`);
		if (link && !title) {
			link.setAttribute('title', href);
		}
	});
</script>

<a
	href={href}
	target={target}
	class="anchor {className}"
	title={title}
	onclick={handleClick}
	rel="noopener noreferrer"
>
	{#if children}{children}{:else}<slot />{/if}
</a>

<style>
	.anchor {
		color: var(--color-primary, #3b82f6);
		text-decoration: none;
		transition: color 0.15s;
	}

	.anchor:hover {
		color: var(--color-primary-dark, #1e40af);
		text-decoration: underline;
	}

	.anchor:active {
		color: var(--color-primary-dark, #1e40af);
	}

	@media (prefers-color-scheme: dark) {
		.anchor {
			color: var(--color-primary-light, #60a5fa);
		}

		.anchor:hover {
			color: var(--color-primary, #3b82f6);
		}

		.anchor:active {
			color: var(--color-primary, #3b82f6);
		}
	}
</style>
