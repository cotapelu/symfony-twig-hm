<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Icon from './atoms/Icon.svelte';

  interface Props {
    data?: number[][];
    labels?: { x?: string[]; y?: string[] };
    valueRange?: [number, number];
    colors?: { min: string; mid: string; max: string };
    showLabels?: boolean;
    showValues?: boolean;
    size?: 'small' | 'medium' | 'large';
    interactive?: boolean;
    className?: string;
    onCellClick?: (detail: { x: number; y: number; value: number }) => void;
  }

  const {
    data = [],
    labels = {},
    valueRange = [0, 100],
    colors = {
      min: '#e0f2fe',
      mid: '#3b82f6',
      max: '#1e40af'
    },
    showLabels = true,
    showValues = false,
    size = 'medium',
    interactive = true,
    className = '',
    onCellClick
  } = $props();

  const dispatch = createEventDispatcher();

  let hoveredCell: { x: number; y: number } | null = null;

  function getColor(value: number): string {
    const normalized = (value - valueRange[0]) / (valueRange[1] - valueRange[0]);
    if (normalized < 0.5) {
      return interpolateColor(colors.min, colors.mid, normalized * 2);
    }
    return interpolateColor(colors.mid, colors.max, (normalized - 0.5) * 2);
  }

  function interpolateColor(color1: string, color2: string, factor: number): string {
    const r1 = parseInt(color1.slice(1, 3), 16);
    const g1 = parseInt(color1.slice(3, 5), 16);
    const b1 = parseInt(color1.slice(5, 7), 16);
    const r2 = parseInt(color2.slice(1, 3), 16);
    const g2 = parseInt(color2.slice(3, 5), 16);
    const b2 = parseInt(color2.slice(5, 7), 16);
    const r = Math.round(r1 + (r2 - r1) * factor);
    const g = Math.round(g1 + (g2 - g1) * factor);
    const b = Math.round(b1 + (b2 - b1) * factor);
    return `rgb(${r}, ${g}, ${b})`;
  }

  function handleCellClick(x: number, y: number, value: number) {
    dispatch('cellClick', { x, y, value });
    onCellClick?.({ x, y, value });
  }

  function getSizeClasses() {
    const classes = {
      small: { cell: 'w-8 h-8', font: 'text-xs' },
      medium: { cell: 'w-12 h-12', font: 'text-sm' },
      large: { cell: 'w-16 h-16', font: 'text-base' }
    };
    return classes[size];
  }

  let sizeClasses = $derived.by(() => getSizeClasses());
</script>

<div class="heatmap-container" class:sizeClasses.cell class:className>
  {#if labels.y || showLabels}
    <div class="heatmap-grid heatmap-header-row">
      {#if labels.y}
        <div class="heatmap-cell heatmap-label-cell"></div>
      {/if}
      {#each (labels.x || Array(data[0]?.length || 0).fill('')) as xLabel, i}
        <div class="heatmap-cell heatmap-label">
          {xLabel || i + 1}
        </div>
      {/each}
    </div>
  {/if}

  {#each data as row, y}
    <div class="heatmap-grid">
      {#if labels.y && labels.y[y]}
        <div class="heatmap-cell heatmap-label">
          {labels.y[y]}
        </div>
      {/if}
      {#each row as value, x}
        <div
          class="heatmap-cell"
          class:interactive
          class:hovered={hoveredCell?.x === x && hoveredCell?.y === y}
          style="background-color: {getColor(value)}"
          onmouseenter={() => hoveredCell = { x, y }}
          onmouseleave={() => hoveredCell = null}
          onclick={() => interactive && handleCellClick(x, y, value)}
          onkeydown={(e) => { if (interactive && (e.key === 'Enter' || e.key === ' ')) { handleCellClick(x, y, value); e.preventDefault(); } }}
          role="gridcell"
          tabindex={interactive ? 0 : -1}
          aria-label="{labels.x?.[x] || x + 1}, {labels.y?.[y] || y + 1}: {value}"
        >
          {#if showValues}
            <span class="heatmap-value">{value}</span>
          {/if}
        </div>
      {/each}
    </div>
  {/each}

  {#if interactive && hoveredCell}
    <div class="heatmap-tooltip">
      <div class="tooltip-content">
        {#if labels.x?.[hoveredCell.x]}
          <strong>{labels.x[hoveredCell.x]}</strong>
        {:else}
          <strong>X: {hoveredCell.x + 1}</strong>
        {/if}
        {#if labels.y?.[hoveredCell.y]}
          <strong>{labels.y[hoveredCell.y]}</strong>
        {:else}
          <strong>Y: {hoveredCell.y + 1}</strong>
        {/if}
        <div class="tooltip-value">
          {data[hoveredCell.y][hoveredCell.x]}
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  :global(:root) {
    --heatmap-border: rgba(0, 0, 0, 0.1);
    --heatmap-hover: rgba(0, 0, 0, 0.2);
    --heatmap-text: var(--text-primary, #1f2937);
    --heatmap-tooltip-bg: rgba(0, 0, 0, 0.9);
    --heatmap-tooltip-text: #ffffff;
  }

  .heatmap-container {
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow: auto;
    max-height: 400px;
  }

  .heatmap-grid {
    display: flex;
    gap: 2px;
  }

  .heatmap-header-row {
    font-weight: 600;
  }

  .heatmap-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 2px;
    transition: transform 0.15s ease;
  }

  .heatmap-cell.interactive {
    cursor: pointer;
  }

  .heatmap-cell.interactive:hover,
  .heatmap-cell.hovered {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    border: 2px solid var(--heatmap-hover);
  }

  .heatmap-label {
    background: transparent;
    color: var(--heatmap-text);
  }

  .heatmap-label-cell {
    background: transparent;
  }

  .heatmap-value {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--heatmap-text);
  }

  .heatmap-tooltip {
    position: fixed;
    pointer-events: none;
    z-index: 1000;
    padding: 8px 12px;
    background: var(--heatmap-tooltip-bg);
    color: var(--heatmap-tooltip-text);
    border-radius: 6px;
    font-size: 0.875rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  }

  .tooltip-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .tooltip-value {
    font-size: 1.125rem;
    font-weight: 700;
  }
</style>