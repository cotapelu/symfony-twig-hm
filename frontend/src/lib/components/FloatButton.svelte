<script lang="ts">
	import Icon from './atoms/Icon.svelte';

	export let icon = '';
	export let clickCallback: (() => void) | undefined = undefined;
	export let tooltip = '';
	export let position: 'top' | 'bottom' | 'left' | 'right' = 'top';
	export let disabled = false;
	export let className = '';

	function handleClick() {
		if (clickCallback) clickCallback();
	}
</script>

<div class="float-button-wrapper {position} {className}" title={tooltip}>
	<button
		type="button"
		class="btn btn-primary btn-lg float-button"
		{disabled}
		on:click={handleClick}
	>
		<Icon name={icon || 'plus'} size="md" />
	</button>
</div>

<style>
	.float-button-wrapper {
		position: fixed;
		z-index: 1000;
	}

	.float-button-wrapper.top {
		top: 20px;
	}

	.float-button-wrapper.bottom {
		bottom: 20px;
	}

	.float-button-wrapper.left {
		left: 20px;
	}

	.float-button-wrapper.right {
		right: 20px;
	}

	.float-button {
		box-shadow: var(--shadow-lg, 0 10px 15px rgba(0, 0, 0, 0.1));
		transition: transform 0.2s;
	}

	.float-button:hover {
		transform: scale(1.1);
	}

	.float-button:active {
		transform: scale(0.95);
	}
</style>
