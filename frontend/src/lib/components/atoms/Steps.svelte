<script lang="ts">
  export let current = 0;
  export let items = [];
  export let direction = 'horizontal'; // horizontal, vertical
  export let size = 'default'; // small, default
  export let status = 'process'; // wait, process, finish, error
  export let labelPlacement = 'horizontal'; // horizontal, vertical
  export let progressDot = false;
  export let className = '';

  import { createEventDispatcher } from 'svelte';
  const dispatch = createEventDispatcher();

  $: classes = [
    'steps',
    `steps-${direction}`,
    `steps-${size}`,
    `steps-label-${labelPlacement}`,
    progressDot ? 'steps-dot' : '',
    className
  ].filter(Boolean).join(' ');

  function getStepStatus(index) {
    if (index < current) return 'finish';
    if (index === current) return status;
    return 'wait';
  }

  function handleStepClick(index) {
    if (index < current) {
      dispatch('change', { current: index });
    }
  }
</script>

<div class={classes} role="tablist">
  {#each items as item, index}
    {@const stepStatus = getStepStatus(index)}
    {@const isClickable = index < current}
    <div
      class="step"
      class:step-finish={stepStatus === 'finish'}
      class:step-process={stepStatus === 'process'}
      class:step-wait={stepStatus === 'wait'}
      class:step-error={stepStatus === 'error'}
      class:step-clickable={isClickable}
      onclick={() => isClickable && handleStepClick(index)}
      role="tab"
      tabindex={isClickable ? 0 : -1}
      aria-current={index === current ? 'step' : undefined}
    >
      <div class="step-indicator">
        {#if progressDot}
          <span class="step-dot"></span>
        {:else if item.icon}
          <span class="step-icon">{item.icon}</span>
        {:else}
          {#if stepStatus === 'finish'}
            ✓
          {:else if stepStatus === 'error'}
            ✕
          {:else}
            {index + 1}
          {/if}
        {/if}
      </div>

      <div class="step-content">
        <div class="step-title">{item.title}</div>
        {#if item.description && (direction === 'horizontal' || labelPlacement === 'vertical')}
          <div class="step-description">{item.description}</div>
        {/if}
      </div>
    </div>
  {/each}
</div>

<style>
  .steps {
    display: flex;
    font-family: inherit;
  }

  .steps-horizontal {
    flex-direction: row;
  }

  .steps-vertical {
    flex-direction: column;
  }

  .step {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    position: relative;
    flex: 1;
  }

  .steps-horizontal .step {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .steps-horizontal.steps-label-vertical .step {
    flex-direction: row;
    text-align: left;
  }

  .steps-vertical .step {
    flex-direction: row;
    margin-bottom: 1rem;
  }

  .steps-vertical .step:last-child {
    margin-bottom: 0;
  }

  .step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    border: 2px solid var(--border-primary, #d1d5db);
    background: var(--bg-primary, white);
    color: var(--text-secondary, #6b7280);
    font-weight: 500;
    font-size: 0.875rem;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    transition: all 0.2s ease;
  }

  .step-icon {
    font-size: 1rem;
  }

  .step-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: var(--text-secondary, #6b7280);
  }

  .step-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
  }

  .steps-horizontal .step-content {
    margin-top: 0.5rem;
  }

  .steps-horizontal.steps-label-vertical .step-content {
    margin-top: 0;
    margin-left: 0.75rem;
  }

  .steps-vertical .step-content {
    margin-left: 0.75rem;
  }

  .step-title {
    font-weight: 500;
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
  }

  .step-description {
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
    line-height: 1.25;
  }

  /* Status styles */
  .step-finish .step-indicator {
    background: var(--success-500, #10b981);
    border-color: var(--success-500, #10b981);
    color: white;
  }

  .step-process .step-indicator {
    background: var(--primary-500, #3b82f6);
    border-color: var(--primary-500, #3b82f6);
    color: white;
  }

  .step-error .step-indicator {
    background: var(--error-500, #ef4444);
    border-color: var(--error-500, #ef4444);
    color: white;
  }

  .step-wait .step-indicator {
    background: var(--gray-50, #f9fafb);
    border-color: var(--border-primary, #d1d5db);
    color: var(--text-secondary, #6b7280);
  }

  .step-dot.step-finish .step-dot {
    background: var(--success-500, #10b981);
  }

  .step-dot.step-process .step-dot {
    background: var(--primary-500, #3b82f6);
  }

  .step-dot.step-error .step-dot {
    background: var(--error-500, #ef4444);
  }

  .step-dot.step-wait .step-dot {
    background: var(--text-secondary, #6b7280);
  }

  /* Connector line */
  .steps-horizontal .step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 1rem;
    left: calc(50% + 1rem);
    right: calc(-50% + 1rem);
    height: 2px;
    background: var(--border-primary, #d1d5db);
    z-index: 0;
  }

  .steps-horizontal .step-finish:not(:last-child)::after {
    background: var(--success-500, #10b981);
  }

  .steps-horizontal .step-process:not(:last-child)::after {
    background: var(--primary-500, #3b82f6);
  }

  .steps-horizontal .step-error:not(:last-child)::after {
    background: var(--error-500, #ef4444);
  }

  .steps-vertical .step:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 2rem;
    bottom: -1rem;
    width: 2px;
    background: var(--border-primary, #d1d5db);
    z-index: 0;
  }

  .steps-vertical .step-finish:not(:last-child)::before {
    background: var(--success-500, #10b981);
  }

  .steps-vertical .step-process:not(:last-child)::before {
    background: var(--primary-500, #3b82f6);
  }

  .steps-vertical .step-error:not(:last-child)::before {
    background: var(--error-500, #ef4444);
  }

  /* Clickable styles */
  .step-clickable {
    cursor: pointer;
  }

  .step-clickable:hover .step-indicator {
    transform: scale(1.1);
  }

  .step-clickable:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
    border-radius: 4px;
  }

  /* Sizes */
  .steps-small .step-indicator {
    width: 1.5rem;
    height: 1.5rem;
    font-size: 0.75rem;
  }

  .steps-small .step-dot {
    width: 0.375rem;
    height: 0.375rem;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .steps-horizontal {
      flex-direction: column;
      align-items: flex-start;
    }

    .steps-horizontal .step {
      flex-direction: row;
      text-align: left;
      width: 100%;
    }

    .steps-horizontal .step-content {
      margin-top: 0;
      margin-left: 0.75rem;
    }

    .steps-horizontal .step:not(:last-child)::after {
      display: none;
    }

    .steps-vertical .step:not(:last-child)::before {
      left: 0.75rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .step-indicator {
      border-width: 3px;
    }

    .steps-horizontal .step:not(:last-child)::after,
    .steps-vertical .step:not(:last-child)::before {
      height: 3px;
      width: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .step-indicator,
    .step-clickable:hover .step-indicator {
      transition: none;
      transform: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .step-indicator {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .step-wait .step-indicator {
      background: var(--gray-700, #374151);
    }

    .step-title {
      color: var(--text-primary-dark, #f9fafb);
    }

    .step-description {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .steps-horizontal .step:not(:last-child)::after,
    .steps-vertical .step:not(:last-child)::before {
      background: var(--gray-600, #4b5563);
    }

    .step-finish .step-indicator,
    .steps-horizontal .step-finish:not(:last-child)::after,
    .steps-vertical .step-finish:not(:last-child)::before {
      background: var(--success-400, #34d399);
      border-color: var(--success-400, #34d399);
    }

    .step-process .step-indicator,
    .steps-horizontal .step-process:not(:last-child)::after,
    .steps-vertical .step-process:not(:last-child)::before {
      background: var(--primary-400, #60a5fa);
      border-color: var(--primary-400, #60a5fa);
    }

    .step-error .step-indicator,
    .steps-horizontal .step-error:not(:last-child)::after,
    .steps-vertical .step-error:not(:last-child)::before {
      background: var(--error-400, #f87171);
      border-color: var(--error-400, #f87171);
    }
  }
</style>