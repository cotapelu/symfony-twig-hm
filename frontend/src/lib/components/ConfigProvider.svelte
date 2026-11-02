<script lang="ts">
	import { setContext } from 'svelte';

	export let theme: 'light' | 'dark' = 'light';
	export let locale = 'en';
	export let primaryColor = '#3b82f6';
	export let borderRadius = '0.375rem';
	export let fontSize = '14px';

	setContext('config', {
		theme,
		locale,
		primaryColor,
		borderRadius,
		fontSize
	});

	$: document.documentElement.style.setProperty('--color-primary', primaryColor);
	$: document.documentElement.style.setProperty('--radius', borderRadius);
	$: document.documentElement.style.setProperty('--font-size-base', fontSize);
</script>

<slot />
