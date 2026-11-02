<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount, onDestroy } from 'svelte';

  interface Props {
    title?: string;
    placement?: 'top' | 'bottom' | 'left' | 'right';
    trigger?: 'hover' | 'focus' | 'click';
    visible?: boolean;
    mouseEnterDelay?: number;
    mouseLeaveDelay?: number;
    className?: string;
  }

  let {
    title = '',
    placement = 'top',
    trigger = 'hover',
    visible = false,
    mouseEnterDelay = 100,
    mouseLeaveDelay = 100,
    className = ''
  }: Props = $props();

  const dispatch = createEventDispatcher();

  let tooltipElement: HTMLElement;
  let triggerElement: HTMLElement;
  let showTimer: ReturnType<typeof setTimeout>;
  let hideTimer: ReturnType<typeof setTimeout>;
  let isVisible = $state(visible);

  function handleMouseEnter() {
    if (trigger === 'hover') {
      clearTimeout(hideTimer);
      if (mouseEnterDelay <= 0) {
        isVisible = true;
        dispatch('visibleChange', true);
      } else {
        showTimer = setTimeout(() => {
          isVisible = true;
          dispatch('visibleChange', true);
        }, mouseEnterDelay);
      }
    }
  }

  function handleMouseLeave() {
    if (trigger === 'hover') {
      clearTimeout(showTimer);
      if (mouseLeaveDelay <= 0) {
        isVisible = false;
        dispatch('visibleChange', false);
      } else {
        hideTimer = setTimeout(() => {
          isVisible = false;
          dispatch('visibleChange', false);
        }, mouseLeaveDelay);
      }
    }
  }

  function handleFocus() {
    if (trigger === 'focus') {
      isVisible = true;
      dispatch('visibleChange', true);
    }
  }

  function handleBlur() {
    if (trigger === 'focus') {
      isVisible = false;
      dispatch('visibleChange', false);
    }
  }

  function handleClick() {
    if (trigger === 'click') {
      isVisible = !isVisible;
      dispatch('visibleChange', isVisible);
    }
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      isVisible = false;
      dispatch('visibleChange', false);
    }
  }

  function handleTriggerKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      isVisible = false;
      dispatch('visibleChange', false);
    }
  }

  onMount(() => {
    if (trigger === 'hover' || trigger === 'focus' || trigger === 'click') {
      document.addEventListener('keydown', handleKeydown);
    }
  });

  onDestroy(() => {
    if (showTimer) clearTimeout(showTimer);
    if (hideTimer) clearTimeout(hideTimer);
    try {
      document.removeEventListener('keydown', handleKeydown);
    } catch (e) {
      // ignore removal errors in test environment
    }
  });

  let tooltipClasses = $derived([
    'tooltip-content',
    `tooltip-${placement}`,
    className,
    isVisible ? 'tooltip-visible' : ''
  ].filter(Boolean).join(' '));
</script>

<div class="tooltip-wrapper" bind:this={triggerElement}>
  <div
    class="tooltip-trigger"
    onmouseenter={handleMouseEnter}
    onmouseleave={handleMouseLeave}
    onfocus={handleFocus}
    onblur={handleBlur}
    onclick={handleClick}
    onkeydown={handleTriggerKeydown}
    tabindex={trigger === 'focus' ? 0 : undefined}
  >
    <slot />
  </div>

  {#if isVisible}
    <div
      class={tooltipClasses}
      bind:this={tooltipElement}
      role="tooltip"
      aria-hidden={!isVisible}
    >
      <div class="tooltip-arrow"></div>
      <div class="tooltip-inner">
        {title}
        <slot name="title" />
      </div>
    </div>
  {/if}
</div>

<style>
  .tooltip-wrapper {
    position: relative;
    display: inline-block;
  }

  .tooltip-trigger {
    display: inline-block;
  }

  .tooltip-content {
    position: absolute;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    pointer-events: none;
  }

  .tooltip-visible {
    opacity: 1;
    visibility: visible;
  }

  .tooltip-inner {
    max-width: 200px;
    padding: 0.5rem 0.75rem;
    background: var(--gray-800, #1f2937);
    color: white;
    border-radius: 6px;
    font-size: 0.75rem;
    line-height: 1.25;
    word-wrap: break-word;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .tooltip-arrow {
    position: absolute;
    width: 0;
    height: 0;
    border: 5px solid transparent;
  }

  /* Placements */
  .tooltip-top {
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-bottom: 8px;
  }

  .tooltip-top .tooltip-arrow {
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-top-color: var(--gray-800, #1f2937);
  }

  .tooltip-bottom {
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-top: 8px;
  }

  .tooltip-bottom .tooltip-arrow {
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-bottom-color: var(--gray-800, #1f2937);
  }

  .tooltip-left {
    right: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-right: 8px;
  }

  .tooltip-left .tooltip-arrow {
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    border-left-color: var(--gray-800, #1f2937);
  }

  .tooltip-right {
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 8px;
  }

  .tooltip-right .tooltip-arrow {
    right: 100%;
    top: 50%;
    transform: translateY(-50%);
    border-right-color: var(--gray-800, #1f2937);
  }

  /* Mobile */
  @media (max-width: 640px) {
    .tooltip-inner {
      max-width: 150px;
      font-size: 0.6875rem;
    }

    .tooltip-top,
    .tooltip-bottom {
      left: 0;
      right: 0;
      transform: none;
      width: 100%;
    }

    .tooltip-top .tooltip-arrow,
    .tooltip-bottom .tooltip-arrow {
      left: 50%;
      transform: translateX(-50%);
    }

    .tooltip-left,
    .tooltip-right {
      position: fixed;
      top: auto;
      bottom: 0;
      left: 0;
      right: 0;
      transform: none;
      margin: 0;
      width: 100%;
    }

    .tooltip-left .tooltip-arrow,
    .tooltip-right .tooltip-arrow {
      display: none;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .tooltip-inner {
      border: 2px solid;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .tooltip-content {
      transition: none;
    }
  }

  /* Dark mode - tooltip is already dark */
</style>