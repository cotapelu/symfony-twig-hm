<script lang="ts">
	import { onMount } from 'svelte';

	export let src = '';
	export let alt = '';
	export let threshold = 200;
	export let placeholder = '';
	export let className = '';
	export let loadCallback: (() => void) | undefined = undefined;

	let inView = false;
	let hasLoaded = false;
	let imgElement: HTMLImageElement;

	onMount(() => {
		const observer = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						inView = true;
						if (!hasLoaded) loadImage();
					}
				});
			},
			{ rootMargin: `${threshold}px` }
		);

		observer.observe(imgElement);

		return () => {
			observer.disconnect();
		};
	});

	function loadImage() {
		if (hasLoaded) return;
		hasLoaded = true;
		if (loadCallback) loadCallback();
	}
</script>

<div class="lazy-image {className}">
	<img
		bind:this={imgElement}
		{src}
		{alt}
		data-src={src}
		class="lazy-image-img"
		loading="lazy"
		on:load={loadImage}
	/>
	{#if !hasLoaded && placeholder}
		<div class="lazy-image-placeholder">
			{placeholder}
		</div>
	{/if}
</div>

<style>
	.lazy-image {
		position: relative;
		overflow: hidden;
		background: var(--color-gray-100, #f3f4f6);
		border-radius: var(--radius-md, 0.5rem);
		min-height: 100px;
	}

	.lazy-image-img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: opacity 0.3s ease;
		opacity: 0;
	}

	.lazy-image-img.loaded {
		opacity: 1;
	}

	.lazy-image-placeholder {
		position: absolute;
		inset: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 1rem;
		color: var(--color-text-secondary, #6b7280);
		text-align: center;
	}

	@media (prefers-color-scheme: dark) {
		.lazy-image {
			background: var(--color-gray-800, #1f2937);
		}

		.lazy-image-placeholder {
			color: var(--color-text-secondary-dark, #9ca3af);
		}
	}
</style>
