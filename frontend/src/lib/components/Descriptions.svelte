<script lang="ts">
	import Icon from './atoms/Icon.svelte';
	import Typography from './atoms/Typography.svelte';
	import Button from './atoms/Button.svelte';

	export let open = false;
	export let title = '';
	export let content = '';
	export let type: 'info' | 'success' | 'warning' | 'error' = 'info';
	export let showIcon = true;
	export let showAction = false;
	export let actionText = '';
	export let onAction: (() => void) | undefined = undefined;
	export let onClose: (() => void) | undefined = undefined;
	export let className = '';

	function getIcon(): string {
		switch (type) {
			case 'success': return 'check-circle';
			case 'warning': return 'exclamation-circle';
			case 'error': return 'x-circle';
			default: return 'info-circle';
		}
	}

	function close() {
		open = false;
		if (onClose) onClose();
	}

	function handleActionClick() {
		if (onAction) onAction();
	}
</script>

{#if open}
	<!-- svelte-ignore a11y_click_events_have_key_events -->
	<!-- svelte-ignore a11y_no_static_element_interactions -->
	<div class="modal-overlay" role="presentation" on:click={close} on:keydown={(e) => { if (e.key === 'Escape') close(); }}>
		<!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
		<div class="modal {type} {className}" role="document" on:click={(e) => e.stopPropagation()}>
			<div class="modal-header">
				<div class="modal-title-wrapper">
					{#if showIcon}
						<Icon name={getIcon()} size="md" />
					{/if}
					<Typography variant="h3" weight={600} className="modal-title">
						{title}
					</Typography>
				</div>
				<button class="modal-close" on:click={close} aria-label="Close modal">
					<Icon name="x" size="sm" />
				</button>
			</div>

			<div class="modal-body">
				<Typography variant="body">
					{content}
				</Typography>
			</div>

			{#if showAction && actionText}
				<div class="modal-footer">
					<Button variant="primary" onclick={handleActionClick} text={actionText} />
				</div>
			{/if}
		</div>
	</div>
{/if}

<style>
	.modal-overlay {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, 0.5);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 2000;
		animation: fadeIn 0.2s ease-out;
	}

	@keyframes fadeIn {
		from {
			opacity: 0;
		}
		to {
			opacity: 1;
		}
	}

	.modal {
		background: var(--color-bg-surface, #ffffff);
		border-radius: 0.5rem;
		max-width: 500px;
		width: 90%;
		max-height: 90vh;
		display: flex;
		flex-direction: column;
		box-shadow: var(--shadow-xl, 0 20px 25px rgba(0, 0, 0, 0.1));
		animation: slideIn 0.3s ease-out;
	}

	@keyframes slideIn {
		from {
			opacity: 0;
			transform: translateY(-20px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	.modal.info .modal-icon {
		/* svelte-ignore css_unused_selector */
		color: var(--color-info, #3b82f6);
	}

	.modal.success .modal-icon {
		/* svelte-ignore css_unused_selector */
		color: var(--color-success, #10b981);
	}

	.modal.warning .modal-icon {
		/* svelte-ignore css_unused_selector */
		color: var(--color-warning, #f59e0b);
	}

	.modal.error .modal-icon {
		/* svelte-ignore css_unused_selector */
		color: var(--color-danger, #ef4444);
	}

	.modal-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 1rem 1.5rem;
		border-bottom: 1px solid var(--color-border, #e5e7eb);
	}

	.modal-title-wrapper {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.modal-title {
		margin: 0;
	}

	.modal-close {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0.375rem;
		background: transparent;
		border: none;
		border-radius: 0.25rem;
		cursor: pointer;
		color: var(--color-text-secondary, #6b7280);
		transition: all 0.15s;
	}

	.modal-close:hover {
		background: var(--color-gray-100, #f3f4f6);
		color: var(--color-text-primary, #111827);
	}

	.modal-body {
		flex: 1;
		padding: 1.5rem;
		overflow-y: auto;
	}

	.modal-footer {
		padding: 1rem 1.5rem;
		border-top: 1px solid var(--color-border, #e5e7eb);
		display: flex;
		justify-content: flex-end;
	}

	@media (prefers-color-scheme: dark) {
		.modal {
			background: var(--color-gray-800, #1f2937);
			border-color: var(--color-gray-700, #374151);
		}

		.modal-header,
		.modal-footer {
			border-color: var(--color-gray-700, #374151);
		}

		.modal-close {
			color: var(--color-text-secondary-dark, #9ca3af);
		}

		.modal-close:hover {
			background: var(--color-gray-700, #374151);
			color: var(--color-text-primary-dark, #f9fafb);
		}
	}
</style>
