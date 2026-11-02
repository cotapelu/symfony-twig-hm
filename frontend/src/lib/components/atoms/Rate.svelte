<script lang="ts">
  export let value = 0;
  export let count = 5;
  export let allowHalf = false;
  export let disabled = false;
  export let readonly = false;
  export let size = 'default'; // small, default, large
  export let character = '★'; // rating character
  export let className = '';

  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  let hoverValue = 0;

  function handleMouseEnter(index, isHalf = false) {
    if (disabled || readonly) return;
    hoverValue = index + (isHalf ? 0.5 : 1);
  }

  function handleMouseLeave() {
    if (disabled || readonly) return;
    hoverValue = 0;
  }

  function handleClick(index, isHalf = false) {
    if (disabled || readonly) return;
    const newValue = index + (isHalf ? 0.5 : 1);
    value = newValue;
    dispatch('change', { value: newValue });
    hoverValue = 0;
  }

  $: displayValue = hoverValue || value;

  $: classes = [
    'rate',
    `rate-${size}`,
    disabled ? 'rate-disabled' : '',
    readonly ? 'rate-readonly' : '',
    className
  ].filter(Boolean).join(' ');
</script>

<div class={classes} role="radiogroup" aria-label="Rate">
  {#each Array(count) as _, index}
    {@const starIndex = index}
    {@const isActive = displayValue > starIndex}
    {@const isHalf = allowHalf && displayValue === starIndex + 0.5}

    <div class="rate-star" class:active={isActive} class:half={isHalf}>
      {#if allowHalf}
        <div
          class="rate-star-half rate-star-left"
          onmouseenter={() => handleMouseEnter(starIndex, false)}
          onmouseleave={handleMouseLeave}
          onclick={() => handleClick(starIndex, false)}
          role="radio"
          aria-checked={displayValue >= starIndex + 1}
          tabindex={disabled || readonly ? -1 : (starIndex === 0 ? 0 : -1)}
        >
          {character}
        </div>
        <div
          class="rate-star-half rate-star-right"
          onmouseenter={() => handleMouseEnter(starIndex, true)}
          onmouseleave={handleMouseLeave}
          onclick={() => handleClick(starIndex, true)}
          role="radio"
          aria-checked={displayValue >= starIndex + 0.5}
          tabindex={-1}
        >
          {character}
        </div>
      {:else}
        <div
          class="rate-star-full"
          onmouseenter={() => handleMouseEnter(starIndex)}
          onmouseleave={handleMouseLeave}
          onclick={() => handleClick(starIndex)}
          role="radio"
          aria-checked={displayValue >= starIndex + 1}
          tabindex={disabled || readonly ? -1 : (starIndex === 0 ? 0 : -1)}
        >
          {character}
        </div>
      {/if}
    </div>
  {/each}
</div>

<style>
  .rate {
    display: inline-flex;
    align-items: center;
    font-size: 1.25rem;
    user-select: none;
  }

  .rate-star {
    position: relative;
    display: inline-block;
    color: var(--gray-300, #d1d5db);
    transition: color 0.2s ease;
    cursor: pointer;
  }

  .rate-star.active {
    color: var(--warning-500, #f59e0b);
  }

  .rate-star.half .rate-star-left {
    color: var(--warning-500, #f59e0b);
  }

  .rate-star.half .rate-star-right {
    color: var(--gray-300, #d1d5db);
  }

  .rate-star-half,
  .rate-star-full {
    display: inline-block;
    position: relative;
  }

  .rate-star-half {
    width: 50%;
    overflow: hidden;
    position: absolute;
    top: 0;
    bottom: 0;
  }

  .rate-star-left {
    left: 0;
  }

  .rate-star-right {
    right: 0;
  }

  .rate-disabled .rate-star,
  .rate-readonly .rate-star {
    cursor: default;
  }

  .rate-disabled {
    opacity: 0.5;
  }

  /* Sizes */
  .rate-small {
    font-size: 1rem;
  }

  .rate-large {
    font-size: 1.5rem;
  }

  /* Focus styles */
  .rate-star:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
    border-radius: 2px;
  }

  /* Hover effects */
  .rate:not(.rate-disabled):not(.rate-readonly) .rate-star:hover {
    transform: scale(1.1);
  }

  /* Mobile */
  @media (max-width: 640px) {
    .rate {
      font-size: 1.5rem;
    }

    .rate-small {
      font-size: 1.25rem;
    }

    .rate-large {
      font-size: 2rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .rate-star.active {
      color: var(--warning-700, #b45309);
    }

    .rate-star.half .rate-star-left {
      color: var(--warning-700, #b45309);
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .rate-star {
      transition: none;
    }

    .rate:not(.rate-disabled):not(.rate-readonly) .rate-star:hover {
      transform: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .rate-star {
      color: var(--gray-600, #4b5563);
    }

    .rate-star.active {
      color: var(--warning-400, #fbbf24);
    }

    .rate-star.half .rate-star-left {
      color: var(--warning-400, #fbbf24);
    }

    .rate-star.half .rate-star-right {
      color: var(--gray-600, #4b5563);
    }
  }
</style>