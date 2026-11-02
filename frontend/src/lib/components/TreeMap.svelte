<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Icon from './atoms/Icon.svelte';

  interface TreeNode {
    id: string;
    label: string;
    value: number;
    color?: string;
    children?: TreeNode[];
  }

  export let data: TreeNode = { id: 'root', label: '', value: 0 };
  export let width: number = 800;
  export let height: number = 600;
  export let colors: string[] = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
  export let showLabels: boolean = true;
  export let showValues: boolean = false;
  export let interactive: boolean = true;
  export let gap: number = 2;

  const dispatch = createEventDispatcher();

  let hoveredNode: TreeNode | null = null;
  let rectangles: Array<{ node: TreeNode; x: number; y: number; width: number; height: number }> = [];

  function calculateRectangles(
    node: TreeNode,
    x: number,
    y: number,
    width: number,
    height: number
  ) {
    if (!node.children || node.children.length === 0) {
      rectangles.push({ node, x, y, width, height });
      return;
    }

    const total = node.children.reduce((sum, child) => sum + child.value, 0);
    let currentX = x;
    let currentY = y;

    if (width > height) {
      node.children.forEach(child => {
        const childWidth = (child.value / total) * width;
        calculateRectangles(child, currentX, y, childWidth, height);
        currentX += childWidth;
      });
    } else {
      node.children.forEach(child => {
        const childHeight = (child.value / total) * height;
        calculateRectangles(child, x, currentY, width, childHeight);
        currentY += childHeight;
      });
    }
  }

  $: {
    rectangles = [];
    calculateRectangles(data, 0, 0, width, height);
  }

  function getColor(node: TreeNode, index: number): string {
    if (node.color) return node.color;
    return colors[index % colors.length];
  }

  function handleRectClick(node: TreeNode, rect: { x: number; y: number; width: number; height: number }) {
    dispatch('nodeClick', { node, rect });
  }

  function getDepth(node: TreeNode): number {
    if (!node.children || node.children.length === 0) return 0;
    return 1 + Math.max(...node.children.map(getDepth));
  }

  function getAncestor(node: TreeNode, targetId: string): TreeNode | null {
    if (node.id === targetId) return node;
    if (!node.children) return null;
    for (const child of node.children) {
      const result = getAncestor(child, targetId);
      if (result) return result;
    }
    return null;
  }
</script>

<div class="treemap-container" data-testid="treemap-container" style="width: {width}px; height: {height}px;">
  <svg viewBox="0 0 {width} {height}" class="treemap-svg" role="img">
    {#each rectangles as rect, i}
      {#if rect.node.children && rect.node.children.length > 0}
        <rect
          x={rect.x + gap}
          y={rect.y + gap}
          width={Math.max(0, rect.width - 2 * gap)}
          height={Math.max(0, rect.height - 2 * gap)}
          fill={getColor(rect.node, i)}
          fill-opacity="0.3"
          class:interactive={interactive}
          class:hovered={hoveredNode?.id === rect.node.id}
          onmouseenter={() => hoveredNode = rect.node}
          onmouseleave={() => hoveredNode = null}
          onclick={() => interactive && handleRectClick(rect.node, rect)}
          role="graphics-symbol"
          aria-label="{rect.node.label}: {rect.node.value}"
        />
      {:else}
        <rect
          x={rect.x + gap}
          y={rect.y + gap}
          width={Math.max(0, rect.width - 2 * gap)}
          height={Math.max(0, rect.height - 2 * gap)}
          fill={getColor(rect.node, i)}
          class:interactive={interactive}
          class:hovered={hoveredNode?.id === rect.node.id}
          onmouseenter={() => hoveredNode = rect.node}
          onmouseleave={() => hoveredNode = null}
          onclick={() => interactive && handleRectClick(rect.node, rect)}
          role="graphics-symbol"
          aria-label="{rect.node.label}: {rect.node.value}"
        />

        {#if showLabels || showValues}
          <foreignObject
            x={rect.x + gap + 8}
            y={rect.y + gap + 8}
            width={Math.max(0, rect.width - 2 * gap - 16)}
            height={Math.max(0, rect.height - 2 * gap - 16)}
          >
            <div class="treemap-label" xmlns="http://www.w3.org/1999/xhtml">
              {#if showLabels}
                <div class="treemap-label-text">{rect.node.label}</div>
              {/if}
              {#if showValues}
                <div class="treemap-value">{rect.node.value}</div>
              {/if}
            </div>
          </foreignObject>
        {/if}
      {/if}
    {/each}
  </svg>

  {#if interactive && hoveredNode}
    <div class="treemap-tooltip">
      <div class="tooltip-content">
        <strong>{hoveredNode.label}</strong>
        <div>Value: {hoveredNode.value}</div>
        {#if hoveredNode.children}
          <div>Children: {hoveredNode.children.length}</div>
        {/if}
      </div>
    </div>
  {/if}
</div>

<style>
  :global(:root) {
    --treemap-tooltip-bg: rgba(0, 0, 0, 0.9);
    --treemap-tooltip-text: #ffffff;
    --treemap-text: #1f2937;
  }

  .treemap-container {
    position: relative;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
  }

  .treemap-svg {
    width: 100%;
    height: 100%;
  }

  .treemap-svg rect {
    transition: opacity 0.15s ease;
  }

  .treemap-svg rect.interactive {
    cursor: pointer;
  }

  .treemap-svg rect.interactive:hover,
  .treemap-svg rect.hovered {
    opacity: 0.8;
  }

  .treemap-label {
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow: hidden;
  }

  .treemap-label-text {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--treemap-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .treemap-value {
    font-size: 0.6875rem;
    color: var(--treemap-text);
    opacity: 0.8;
  }

  .treemap-tooltip {
    position: absolute;
    pointer-events: none;
    z-index: 1000;
    padding: 8px 12px;
    background: var(--treemap-tooltip-bg);
    color: var(--treemap-tooltip-text);
    border-radius: 6px;
    font-size: 0.875rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  }

  .tooltip-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
</style>
