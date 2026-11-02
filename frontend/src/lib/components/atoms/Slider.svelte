<script lang="ts">
  export let value: number | number[] = 0;
  export let min = 0;
  export let max = 100;
  export let step = 1;
  export let range = false;
  export let disabled = false;
  export let vertical = false;
  export let reverse = false;
  export let marks: Record<string, string> = {};
  export let tooltip = true;
  export let className = '';

  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  let sliderElement: HTMLElement;
  let isDragging = false;
  let activeHandle = 0;

  $: currentValue = range ? (Array.isArray(value) ? value : [min, max]) : [value] as number[];
  $: percentages = currentValue.map(v => ((v - min) / (max - min)) * 100);

  function handleMouseDown(event: MouseEvent, index = 0) {
    if (disabled) return;
    isDragging = true;
    activeHandle = index;
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
    handleMouseMove(event);
  }

  function handleMouseMove(event: MouseEvent) {
    if (!isDragging || disabled) return;

    const rect = sliderElement.getBoundingClientRect();
    let percent: number;

    if (vertical) {
      const y = reverse ? event.clientY - rect.top : rect.bottom - event.clientY;
      percent = (y / rect.height) * 100;
    } else {
      const x = reverse ? rect.right - event.clientX : event.clientX - rect.left;
      percent = (x / rect.width) * 100;
    }

    percent = Math.max(0, Math.min(100, percent));
    const newValue = min + ((max - min) * percent / 100);
    const steppedValue = Math.round(newValue / step) * step;

    if (range) {
      const newValues: number[] = [...currentValue];
      newValues[activeHandle] = steppedValue;
      if (activeHandle === 0 && steppedValue > currentValue[1]) {
        newValues[0] = currentValue[1];
      } else if (activeHandle === 1 && steppedValue < currentValue[0]) {
        newValues[1] = currentValue[0];
      }
      value = newValues;
      dispatch('change', { value: newValues });
    } else {
      value = steppedValue;
      dispatch('change', { value: steppedValue });
    }
  }

  function handleMouseUp() {
    isDragging = false;
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
  }

  function handleKeydown(event: KeyboardEvent, index = 0) {
    if (disabled) return;

    let newValue: number;
    const current = currentValue[index];

    switch (event.key) {
      case 'ArrowLeft':
      case 'ArrowDown':
        event.preventDefault();
        newValue = Math.max(min, current - step);
        break;
      case 'ArrowRight':
      case 'ArrowUp':
        event.preventDefault();
        newValue = Math.min(max, current + step);
        break;
      case 'Home':
        event.preventDefault();
        newValue = min;
        break;
      case 'End':
        event.preventDefault();
        newValue = max;
        break;
      default:
        return;
    }

    if (range) {
      const newValues: number[] = [...currentValue];
      newValues[index] = newValue;
      if (index === 0 && newValue > currentValue[1]) {
        newValues[0] = currentValue[1];
      } else if (index === 1 && newValue < currentValue[0]) {
        newValues[1] = currentValue[0];
      }
      value = newValues;
      dispatch('change', { value: newValues });
    } else {
      value = newValue;
      dispatch('change', { value: newValue });
    }
  }

  $: classes = [
    'slider',
    vertical ? 'slider-vertical' : 'slider-horizontal',
    reverse ? 'slider-reverse' : '',
    disabled ? 'slider-disabled' : '',
    range ? 'slider-range' : '',
    className
  ].filter(Boolean).join(' ');

  $: trackStyle = vertical
    ? `height: ${Math.abs(percentages[range ? 1 : 0] - (range ? percentages[0] : 0))}%`
    : `width: ${Math.abs(percentages[range ? 1 : 0] - (range ? percentages[0] : 0))}%`;

  $: trackPosition = vertical
    ? `bottom: ${Math.min(...percentages)}%`
    : `left: ${Math.min(...percentages)}%`;
</script>

<div class={classes} bind:this={sliderElement} role="slider" aria-valuemin={min} aria-valuemax={max} aria-valuenow={currentValue[0]} tabindex={disabled ? -1 : 0} onkeydown={(e) => handleKeydown(e)}>
  <div class="slider-rail"></div>
  <div class="slider-track" style="{trackStyle}; {trackPosition}"></div>

  {#each currentValue as val, index}
    <div
      class="slider-handle"
      class:active={isDragging && activeHandle === index}
      style="{vertical ? `bottom: ${percentages[index]}%` : `left: ${percentages[index]}%`}"
      onmousedown={(e) => handleMouseDown(e, index)}
      onkeydown={(e) => handleKeydown(e, index)}
      role="slider"
      aria-valuemin={min}
      aria-valuemax={max}
      aria-valuenow={val}
      tabindex={disabled ? -1 : 0}
    >
      {#if tooltip}
        <div class="slider-tooltip">
          {val}
        </div>
      {/if}
    </div>
  {/each}

  {#if Object.keys(marks).length > 0}
    <div class="slider-marks">
      {#each Object.entries(marks) as [key, label]}
        {@const percent = ((parseFloat(key) - min) / (max - min)) * 100}
        <div class="slider-mark" style="{vertical ? `bottom: ${percent}%` : `left: ${percent}%`}">
          <span class="slider-mark-label">{label}</span>
        </div>
      {/each}
    </div>
  {/if}
</div>

<style>
  .slider {
    position: relative;
    height: 12px;
    cursor: pointer;
    touch-action: none;
    user-select: none;
  }

  .slider-vertical {
    width: 12px;
    height: 200px;
  }

  .slider-disabled {
    cursor: not-allowed;
    opacity: 0.5;
  }

  .slider-rail {
    position: absolute;
    background: var(--gray-300, #d1d5db);
    border-radius: 6px;
  }

  .slider-horizontal .slider-rail {
    width: 100%;
    height: 4px;
    top: 50%;
    transform: translateY(-50%);
  }

  .slider-vertical .slider-rail {
    height: 100%;
    width: 4px;
    left: 50%;
    transform: translateX(-50%);
  }

  .slider-track {
    position: absolute;
    background: var(--primary-500, #3b82f6);
    border-radius: 6px;
  }

  .slider-horizontal .slider-track {
    height: 4px;
    top: 50%;
    transform: translateY(-50%);
  }

  .slider-vertical .slider-track {
    width: 4px;
    left: 50%;
    transform: translateX(-50%);
  }

  .slider-handle {
    position: absolute;
    width: 12px;
    height: 12px;
    background: white;
    border: 2px solid var(--primary-500, #3b82f6);
    border-radius: 50%;
    cursor: grab;
    transform: translate(-50%, -50%);
    outline: none;
    transition: all 0.2s ease;
  }

  .slider-handle:hover,
  .slider-handle.active {
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
    transform: translate(-50%, -50%) scale(1.2);
  }

  .slider-handle:focus {
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
  }

  .slider-handle:active {
    cursor: grabbing;
  }

  .slider-tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--gray-800, #1f2937);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
    margin-bottom: 8px;
  }

  .slider-handle:hover .slider-tooltip,
  .slider-handle.active .slider-tooltip {
    opacity: 1;
  }

  .slider-marks {
    position: absolute;
  }

  .slider-horizontal .slider-marks {
    top: 20px;
    width: 100%;
  }

  .slider-vertical .slider-marks {
    left: 20px;
    height: 100%;
  }

  .slider-mark {
    position: absolute;
    transform: translateX(-50%);
  }

  .slider-vertical .slider-mark {
    transform: translateY(50%);
  }

  .slider-mark-label {
    display: inline-block;
    color: var(--text-secondary, #6b7280);
    font-size: 12px;
    background: white;
    padding: 2px 4px;
    border-radius: 2px;
    border: 1px solid var(--border-primary, #d1d5db);
  }

  /* Focus styles */
  .slider:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .slider {
      height: 16px;
    }

    .slider-vertical {
      width: 16px;
    }

    .slider-handle {
      width: 16px;
      height: 16px;
    }

    .slider-tooltip {
      display: none;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .slider-rail {
      border: 1px solid;
    }

    .slider-track {
      border: 1px solid;
    }

    .slider-handle {
      border-width: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .slider-handle,
    .slider-tooltip {
      transition: none;
    }

    .slider-handle:hover,
    .slider-handle.active {
      transform: translate(-50%, -50%);
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .slider-rail {
      background: var(--gray-600, #4b5563);
    }

    .slider-track {
      background: var(--primary-400, #60a5fa);
    }

    .slider-handle {
      background: var(--gray-800, #1f2937);
      border-color: var(--primary-400, #60a5fa);
    }

    .slider-handle:hover,
    .slider-handle.active {
      box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.2);
    }

    .slider-tooltip {
      background: var(--gray-700, #374151);
    }

    .slider-mark-label {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
      color: var(--text-secondary-dark, #9ca3af);
    }
  }
</style>