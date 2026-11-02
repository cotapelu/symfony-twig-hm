<script lang="ts">
	import Icon from './atoms/Icon.svelte';
	import Typography from './atoms/Typography.svelte';
	import Checkbox from './atoms/Checkbox.svelte';

	export let treeData: Array<{
		id: string;
		label: string;
		expanded?: boolean;
		children?: Array<{ id: string; label: string }>;
	}> = [];
	export let checked = new Array<string>();
	export let expandible = false;
	export let checkable = false;
	export let className = '';

	function handleCheck(id: string) {
		if (!checkable) return;

		if (checked.includes(id)) {
			checked = checked.filter((i) => i !== id);
		} else {
			checked = [...checked, id];
		}
	}

	function handleExpand(id: string) {
		if (expandible) {
			treeData = treeData.map((item) => {
				if (item.id === id) {
					return { ...item, expanded: !item.expanded };
				}
				return item;
			});
		}
	}

	function getIcon(item: any): string {
		return item.expanded ? 'chevron-down' : 'chevron-right';
	}

	function hasChildren(item: any): boolean {
		return item.children && item.children.length > 0;
	}
</script>

<div class="tree {checkable ? 'checkable' : ''} {className}">
	<ul class="tree-list">
		{#each treeData as item}
			{#if item.children && item.children.length > 0}
				<li class="tree-node">
					<div class="tree-node-content">
						{#if checkable}
							<Checkbox
								checked={checked.includes(item.id)}
								on:change={() => handleCheck(item.id)}
							/>
						{/if}
						<Typography variant="body" className="tree-label">
							{item.label}
						</Typography>
						{#if hasChildren(item)}
							<button
								class="tree-expand"
								onclick={() => handleExpand(item.id)}
								aria-label="Toggle children"
							>
								<Icon name={getIcon(item)} size="sm" />
							</button>
						{/if}
					</div>
					<ul class="tree-children {item.expanded ? 'expanded' : ''}">
							{#each item.children as child}
								<li class="tree-child-node">
									<Typography variant="body" className="tree-child-label">
										{child.label}
									</Typography>
								</li>
							{/each}
					</ul>
				</li>
			{:else}
				<li class="tree-leaf">
					<div class="tree-leaf-content">
						{#if checkable}
							<Checkbox
								checked={checked.includes(item.id)}
								on:change={() => handleCheck(item.id)}
							/>
						{/if}
						<Typography variant="body" className="tree-leaf-label">
							{item.label}
						</Typography>
					</div>
				</li>
			{/if}
		{/each}
	</ul>
</div>

<style>
	.tree {
		font-size: 0.875rem;
	}

	.tree-list {
		list-style: none;
		padding: 0;
	}

	.tree-node,
	.tree-leaf {
		padding: 0.25rem 0;
	}

	.tree-node-content {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.tree-expand {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0.125rem;
		background: transparent;
		border: none;
		border-radius: 0.25rem;
		cursor: pointer;
		color: var(--color-text-secondary, #6b7280);
		transition: all 0.15s;
	}

	.tree-expand:hover {
		background: var(--color-gray-100, #f3f4f6);
		color: var(--color-text-primary, #111827);
	}

	.tree-label {
		flex: 1;
	}

	.tree-leaf-label {
		padding-left: 1.75rem;
	}

	.tree-children {
		list-style: none;
		padding-left: 1.5rem;
		margin-top: 0;
		overflow: hidden;
		max-height: 0;
		opacity: 0;
	}

	.tree-children.expanded {
		max-height: 500px;
		opacity: 1;
		animation: slideDown 0.2s ease-out;
	}

	@keyframes slideDown {
		from {
			max-height: 0;
		opacity: 0;
		}
		to {
			max-height: 500px;
			opacity: 1;
		}
	}

	.tree-child-node {
		padding: 0.25rem 0.5rem 0.25rem 0;
		color: var(--color-text-secondary, #6b7280);
	}

	.tree-child-label {
		display: flex;
		align-items: center;
		gap: 0.375rem;
	}

	.tree-checkbox {
		flex-shrink: 0;
	}

	@media (prefers-color-scheme: dark) {
		.tree-expand {
			color: var(--color-text-secondary-dark, #9ca3af);
		}

		.tree-expand:hover {
			background: var(--color-gray-800, #1f2937);
			color: var(--color-text-primary-dark, #f9fafb);
		}

		.tree-child-node {
			color: var(--color-text-secondary-dark, #9ca3af);
		}
	}
</style>
