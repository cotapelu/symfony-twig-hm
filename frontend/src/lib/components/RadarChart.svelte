<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Icon from './atoms/Icon.svelte';

  export let data: Array<{ label: string; dataPoints: number[]; color?: string }> = [];
  export let labels: string[] = [];
  export let width: number = 500;
  export let height: number = 500;
  export let colors: string[] = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
  export let showLabels: boolean = true;
  export let showValues: boolean = false;
  export let fillOpacity: number = 0.3;
  export let strokeOpacity: number = 0.8;
  export let strokeWidth: number = 2;
  export let maxValues: number[] | null = null;

  const dispatch = createEventDispatcher();

  let hoveredDataset: number | null = null;
  let hoveredPoint: number | null = null;

  const padding = 60;
  const size = Math.min(width, height) - 2 * padding;
  const centerX = width / 2;
  const centerY = height / 2;
  const numAxes = labels.length;

  $: maxValue = maxValues ? Math.max(...maxValues) : Math.max(...data.flatMap(d => d.dataPoints));

  function getAngle(index: number): number {
    return (2 * Math.PI * index) / numAxes - Math.PI / 2;
  }

  function getRadius(value: number): number {
    return (value / maxValue) * (size / 2);
  }

  function getPoint(value: number, index: number): { x: number; y: number } {
    const angle = getAngle(index);
    const radius = getRadius(value);
    return {
      x: centerX + radius * Math.cos(angle),
      y: centerY + radius * Math.sin(angle)
    };
  }

  function getDatasetPath(dataset: { label: string; dataPoints: number[]; color?: string }): string {
    const points = dataset.dataPoints.map((value, i) => {
      const point = getPoint(value, i);
      return `${point.x},${point.y}`;
    });
    return `M ${points.join(' L ')} Z`;
  }

  function handleDatasetClick(datasetIndex: number, valueIndex: number) {
    dispatch('datasetClick', { datasetIndex, valueIndex, data: data[datasetIndex] });
  }

  function getColor(datasetIndex: number): string {
    return data[datasetIndex].color || colors[datasetIndex % colors.length];
  }

  function formatValue(value: number): string {
    return value.toFixed(1);
  }
</script>

<div class="radar-chart-container" data-testid="radar-chart-container">
  <svg viewBox="0 0 {width} {height}" class="radar-chart-svg" role="img">
    <g transform="translate({centerX}, {centerY})">
      {#each [1, 0.75, 0.5, 0.25] as level}
        <polygon
          points={Array.from({ length: numAxes }, (_, i) => {
            const angle = getAngle(i);
            const r = (size / 2) * level;
            return `${r * Math.cos(angle)},${r * Math.sin(angle)}`;
          }).join(' ')}
          fill="none"
          stroke="#e5e7eb"
          stroke-width="1"
          stroke-dasharray="4 4"
        />
      {/each}

      {#each labels as label, i}
        <line
          x1="0"
          y1="0"
          x2={(size / 2) * Math.cos(getAngle(i))}
          y2={(size / 2) * Math.sin(getAngle(i))}
          stroke="#e5e7eb"
          stroke-width="1"
        />

        {#if showLabels}
          <text
            x={(size / 2 + 20) * Math.cos(getAngle(i))}
            y={(size / 2 + 20) * Math.sin(getAngle(i))}
            text-anchor="middle"
            dominant-baseline="middle"
            class="radar-label"
            font-size="12"
            fill="#374151"
          >
            {label}
          </text>
        {/if}
      {/each}

      {#each data as dataset, datasetIndex}
        <polygon
          points={getDatasetPath(dataset)}
          fill={getColor(datasetIndex)}
          fill-opacity={fillOpacity}
          stroke={getColor(datasetIndex)}
          stroke-width={strokeWidth}
          stroke-opacity={strokeOpacity}
          class:interactive={hoveredDataset !== null}
          class:hovered={hoveredDataset === datasetIndex}
          onmouseenter={() => hoveredDataset = datasetIndex}
          onmouseleave={() => { hoveredDataset = null; hoveredPoint = null; }}
          onclick={() => handleDatasetClick(datasetIndex, 0)}
          role="graphics-symbol"
          aria-label={dataset.label}
        />

        {#each dataset.dataPoints as value, valueIndex}
          <circle
            cx={getPoint(value, valueIndex).x - centerX}
            cy={getPoint(value, valueIndex).y - centerY}
            r="4"
            fill={getColor(datasetIndex)}
            stroke="#ffffff"
            stroke-width="2"
            class:interactive={hoveredPoint !== null}
            class:hovered={hoveredPoint === valueIndex && hoveredDataset === datasetIndex}
            onmouseenter={() => { hoveredDataset = datasetIndex; hoveredPoint = valueIndex; }}
            onclick={() => handleDatasetClick(datasetIndex, valueIndex)}
            role="graphics-symbol"
            aria-label={`${labels[valueIndex]}: ${value}`}
          />
        {/each}
      {/each}
    </g>
  </svg>

  {#if hoveredDataset !== null && hoveredPoint !== null}
    <div class="radar-tooltip">
      <div class="tooltip-content">
        <strong>{data[hoveredDataset].label}</strong>
        <div>{labels[hoveredPoint]}: {formatValue(data[hoveredDataset].dataPoints[hoveredPoint])}</div>
        {#if showValues}
          <div>
            {#each data[hoveredDataset].dataPoints as v, i}
              <div>{labels[i]}: {formatValue(v)}</div>
            {/each}
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>

<style>
  :global(:root) {
    --radar-tooltip-bg: rgba(0, 0, 0, 0.9);
    --radar-tooltip-text: #ffffff;
  }

  .radar-chart-container {
    position: relative;
    display: inline-block;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
  }

  .radar-chart-svg {
    width: 100%;
    height: 100%;
  }

  .radar-chart-svg polygon {
    transition: fill-opacity 0.15s ease, stroke-opacity 0.15s ease;
  }

  .radar-chart-svg polygon.interactive:hover,
  .radar-chart-svg polygon.hovered {
    fill-opacity: 0.6;
    stroke-opacity: 1;
  }

  .radar-chart-svg circle {
    transition: r 0.15s ease;
    cursor: pointer;
  }

  .radar-chart-svg circle.hovered {
    r: 6;
  }

  .radar-label {
    font-weight: 600;
  }

  .radar-tooltip {
    position: absolute;
    pointer-events: none;
    z-index: 1000;
    padding: 8px 12px;
    background: var(--radar-tooltip-bg);
    color: var(--radar-tooltip-text);
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
