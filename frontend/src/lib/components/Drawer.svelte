<script lang="ts">
	import { createEventDispatcher } from 'svelte';
	import Button from './atoms/Button.svelte';
	import Icon from './atoms/Icon.svelte';

	const dispatch = createEventDispatcher();

	interface Props {
		open?: boolean;
		position?: 'left' | 'right';
		size?: 'sm' | 'md' | 'lg' | 'xl';
		title?: string;
		showClose?: boolean;
		closeOnOverlay?: boolean;
		className?: string;
		closeCallback?: () => void;
		contentText?: string;
		onClose?: () => void;
	}

	const props = $props<Props>();
	
	let {
		open = false,
		position = 'right',
		size = 'md',
		title = '',
		showClose = true,
		closeOnOverlay = true,
		className = '',
		closeCallback,
		contentText = '',
		onClose
	} = props;

	function closeDrawer() {
		open = false;
		dispatch('close');
		if (closeCallback) closeCallback();
		onClose?.();
	}

	function handleOverlayClick() {
		if (closeOnOverlay) closeDrawer();
	}

	function handleKeyDown(e: KeyboardEvent) {
		if (e.key === 'Escape') closeDrawer();
	}

	$effect(() => {
		if (open) {
			document.body.style.overflow = 'hidden';
		} else {
			document.body.style.overflow = '';
		}
	});
</script>

{#if open}
	<div
    class="drawer-overlay"
    class:overlay-right={position === 'right'}
    role="presentation"
    onclick={handleOverlayClick}
    onkeydown={(e) => { if (e.key === 'Escape') closeDrawer(); }}
  >
		<div
      class="drawer {position} {size} {className}"
      role="dialog"
      aria-modal="true"
      tabindex="-1"
      onclick={(e) => e.stopPropagation()}
      onkeydown={(e) => e.stopPropagation()}
    >
			<div class="drawer-header">
				<slot name="header">
						<h2>{title}</h2>
				</slot>
				{#if showClose}
					<button class="drawer-close" onclick={closeDrawer} aria-label="Close drawer">
						<Icon name="x" size="md" />
					</button>
				{/if}
			</div>

			<div class="drawer-content">
				{#if contentText}
					{contentText}
				{:else}
					<slot />
				{/if}
			</div>

			<div class="drawer-footer">
				<slot name="footer">
						<Button variant="ghost" onclick={closeDrawer} on:click>Close</Button>
				</slot>
			</div>
		</div>
	</div>
{/if}

<style>
	.drawer-overlay {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, 0.5);
		z-index: 1000;
		display: flex;
		justify-content: flex-start;
		animation: fadeIn 0.2s ease-out;
	}

	.drawer-overlay.overlay-right {
		justify-content: flex-end;
	}

	@keyframes fadeIn {
		from {
			opacity: 0;
		}
		to {
			opacity: 1;
		}
	}

	.drawer {
		height: 100%;
		background: var(--color-bg-surface, #ffffff);
		box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
		display: flex;
		flex-direction: column;
		animation: slideInLeft 0.3s ease-out;
	}

	.drawer.right {
		animation: slideInRight 0.3s ease-out;
	}

	@keyframes slideInLeft {
		from {
			transform: translateX(-100%);
		}
		to {
			transform: translateX(0);
		}
	}

	@keyframes slideInRight {
		from {
			transform: translateX(100%);
		}
		to {
			transform: translateX(0);
		}
	}

	.drawer.left {
		width: 280px;
	}

	.drawer.right {
		width: 280px;
	}

	.drawer.sm {
		width: 240px;
	}

	.drawer.md {
		width: 280px;
	}

	.drawer.lg {
		width: 360px;
	}

	.drawer.xl {
		width: 480px;
	}

	.drawer-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 1rem 1.5rem;
		border-bottom: 1px solid var(--color-border, #e5e7eb);
	}

	.drawer-close {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0.25rem;
		background: transparent;
		border: none;
		border-radius: 0.25rem;
		cursor: pointer;
		color: var(--color-text-secondary, #6b7280);
		transition: all 0.15s;
	}

	.drawer-close:hover {
		background: var(--color-gray-100, #f3f4f6);
		color: var(--color-text-primary, #111827);
	}

	.drawer-content {
		flex: 1;
		padding: 1.5rem;
		overflow-y: auto;
	}

	.drawer-footer {
		padding: 1rem 1.5rem;
		border-top: 1px solid var(--color-border, #e5e7eb);
		display: flex;
		justify-content: flex-end;
		gap: 0.5rem;
	}

	@media (prefers-color-scheme: dark) {
		.drawer {
			background: var(--color-gray-800, #1f2937);
		}

		.drawer-header,
		.drawer-footer {
			border-color: var(--color-gray-700, #374151);
		}

		.drawer-close {
			color: var(--color-text-secondary-dark, #9ca3af);
		}

		.drawer-close:hover {
			background: var(--color-gray-700, #374151);
			color: var(--color-text-primary-dark, #f9fafb);
		}
	}

	@media (max-width: 768px) {
		.drawer.left,
		.drawer.right,
		.drawer.sm,
		.drawer.md,
		.drawer.lg {
			width: 100%;
			max-width: none;
		}
	}
</style>