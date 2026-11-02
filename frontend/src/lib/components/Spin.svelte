<script lang="ts">
	import Icon from './atoms/Icon.svelte';

	export let size: 'sm' | 'md' | 'lg' = 'md';
	export let color = 'currentColor';
	export let className = '';
</script>

<div class="spin {size} {className}" role="status" aria-label="Loading" aria-busy={true}>
	<Icon name="loader" size={size} color={color} spin={true} />
</div>

<style>
	.spin {
		display: inline-block;
	}
</style>
