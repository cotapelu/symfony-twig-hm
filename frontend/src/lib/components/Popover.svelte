<script lang="ts">
	import { createEventDispatcher, onDestroy, tick } from 'svelte';

	const dispatch = createEventDispatcher();

  export let trigger: 'click' | 'hover' | 'focus' = 'click';
  export let placement: 'top' | 'bottom' | 'left' | 'right' | 'top-start' | 'top-end' | 'bottom-start' | 'bottom-end' | 'left-start' | 'left-end' | 'right-start' | 'right-end' = 'bottom';
  export let visible: boolean = false;
  export let disabled: boolean = false;
  export let className: string = '';

  let triggerElement: HTMLElement;
  let popoverElement: HTMLElement;
  let isVisible = visible;

  $: classes = [
    'popover',
    `popover-${placement}`,
    className
  ].filter(Boolean).join(' ');

  function showPopover() {
    if (disabled) return;
    isVisible = true;
    dispatch('open');
    updatePosition();
  }

  function hidePopover() {
    isVisible = false;
    dispatch('close');
  }

  function togglePopover() {
    if (isVisible) {
      hidePopover();
    } else {
      showPopover();
    }
  }

  function updatePosition() {
    if (!triggerElement || !popoverElement || !isVisible) return;

    const triggerRect = triggerElement.getBoundingClientRect();
    const popoverRect = popoverElement.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    let top = 0;
    let left = 0;

    switch (placement) {
      case 'top':
        top = triggerRect.top - popoverRect.height - 8;
        left = triggerRect.left + (triggerRect.width - popoverRect.width) / 2;
        break;
      case 'bottom':
        top = triggerRect.bottom + 8;
        left = triggerRect.left + (triggerRect.width - popoverRect.width) / 2;
        break;
      case 'left':
        top = triggerRect.top + (triggerRect.height - popoverRect.height) / 2;
        left = triggerRect.left - popoverRect.width - 8;
        break;
      case 'right':
        top = triggerRect.top + (triggerRect.height - popoverRect.height) / 2;
        left = triggerRect.right + 8;
        break;
      case 'top-start':
        top = triggerRect.top - popoverRect.height - 8;
        left = triggerRect.left;
        break;
      case 'top-end':
        top = triggerRect.top - popoverRect.height - 8;
        left = triggerRect.right - popoverRect.width;
        break;
      case 'bottom-start':
        top = triggerRect.bottom + 8;
        left = triggerRect.left;
        break;
      case 'bottom-end':
        top = triggerRect.bottom + 8;
        left = triggerRect.right - popoverRect.width;
        break;
      case 'left-start':
        top = triggerRect.top;
        left = triggerRect.left - popoverRect.width - 8;
        break;
      case 'left-end':
        top = triggerRect.bottom - popoverRect.height;
        left = triggerRect.left - popoverRect.width - 8;
        break;
      case 'right-start':
        top = triggerRect.top;
        left = triggerRect.right + 8;
        break;
      case 'right-end':
        top = triggerRect.bottom - popoverRect.height;
        left = triggerRect.right + 8;
        break;
    }

    // Keep within viewport
    if (left < 8) left = 8;
    if (left + popoverRect.width > viewportWidth - 8) left = viewportWidth - popoverRect.width - 8;
    if (top < 8) top = 8;
    if (top + popoverRect.height > viewportHeight - 8) top = viewportHeight - popoverRect.height - 8;

    popoverElement.style.top = `${top}px`;
    popoverElement.style.left = `${left}px`;
  }

  function handleTriggerClick() {
    if (trigger === 'click') {
      togglePopover();
    }
  }

  function handleTriggerMouseEnter() {
    if (trigger === 'hover') {
      showPopover();
    }
  }

  function handleTriggerMouseLeave() {
    if (trigger === 'hover') {
      hidePopover();
    }
  }

  function handleTriggerFocus() {
    if (trigger === 'focus') {
      showPopover();
    }
  }

  function handleTriggerBlur() {
    if (trigger === 'focus') {
      hidePopover();
    }
  }

  // Close on outside click
  function handleDocumentClick(event: MouseEvent) {
    if (isVisible && triggerElement && popoverElement &&
        !triggerElement.contains(event.target as Node) &&
        !popoverElement.contains(event.target as Node)) {
      hidePopover();
    }
  }

  // Close on escape
  function handleKeyDown(event: KeyboardEvent) {
    if (event.key === 'Escape' && isVisible) {
      hidePopover();
    }
  }

	onDestroy(() => {
		document.removeEventListener('click', handleDocumentClick);
		document.removeEventListener('keydown', handleKeyDown);
	});
</script>

<svelte:window onclick={handleDocumentClick} onkeydown={handleKeyDown} />

<div class="popover-container">
  <div
    bind:this={triggerElement}
    class="popover-trigger"
    onclick={handleTriggerClick}
    onmouseenter={handleTriggerMouseEnter}
    onmouseleave={handleTriggerMouseLeave}
    onfocus={handleTriggerFocus}
    onblur={handleTriggerBlur}
    tabindex={trigger === 'focus' ? 0 : -1}
    role="button"
    aria-expanded={isVisible}
    aria-haspopup="dialog"
    onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { handleTriggerClick(); e.preventDefault(); } }}
  >
    <slot name="trigger" />
  </div>

  {#if isVisible}
    <div
      bind:this={popoverElement}
      class={classes}
      role="dialog"
      aria-modal="true"
      tabindex="-1"
    >
      <div class="popover-content">
        <slot />
      </div>
      <div class="popover-arrow" data-placement={placement}></div>
    </div>
  {/if}
</div>

<style>
  .popover-container {
    position: relative;
    display: inline-block;
  }

  .popover-trigger {
    display: inline-block;
    cursor: pointer;
  }

  .popover {
    position: fixed;
    z-index: 1000;
    background: var(--background, #ffffff);
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    max-width: 300px;
    word-wrap: break-word;
  }

  .popover-content {
    padding: 1rem;
  }

  .popover-arrow {
    position: absolute;
    width: 0;
    height: 0;
    border: 6px solid transparent;
  }

  .popover-arrow[data-placement="top"] {
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%);
    border-top-color: var(--background, #ffffff);
    border-bottom: none;
  }

  .popover-arrow[data-placement="bottom"] {
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    border-bottom-color: var(--background, #ffffff);
    border-top: none;
  }

  .popover-arrow[data-placement="left"] {
    right: -12px;
    top: 50%;
    transform: translateY(-50%);
    border-left-color: var(--background, #ffffff);
    border-right: none;
  }

  .popover-arrow[data-placement="right"] {
    left: -12px;
    top: 50%;
    transform: translateY(-50%);
    border-right-color: var(--background, #ffffff);
    border-left: none;
  }

  .popover-arrow[data-placement="top-start"] {
    bottom: -12px;
    left: 20px;
    border-top-color: var(--background, #ffffff);
    border-bottom: none;
  }

  .popover-arrow[data-placement="top-end"] {
    bottom: -12px;
    right: 20px;
    border-top-color: var(--background, #ffffff);
    border-bottom: none;
  }

  .popover-arrow[data-placement="bottom-start"] {
    top: -12px;
    left: 20px;
    border-bottom-color: var(--background, #ffffff);
    border-top: none;
  }

  .popover-arrow[data-placement="bottom-end"] {
    top: -12px;
    right: 20px;
    border-bottom-color: var(--background, #ffffff);
    border-top: none;
  }

  .popover-arrow[data-placement="left-start"] {
    right: -12px;
    top: 20px;
    border-left-color: var(--background, #ffffff);
    border-right: none;
  }

  .popover-arrow[data-placement="left-end"] {
    right: -12px;
    bottom: 20px;
    border-left-color: var(--background, #ffffff);
    border-right: none;
  }

  .popover-arrow[data-placement="right-start"] {
    left: -12px;
    top: 20px;
    border-right-color: var(--background, #ffffff);
    border-left: none;
  }

  .popover-arrow[data-placement="right-end"] {
    left: -12px;
    bottom: 20px;
    border-right-color: var(--background, #ffffff);
    border-left: none;
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .popover {
      background: var(--background-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
    }

    .popover-arrow[data-placement="top"] {
      border-top-color: var(--background-dark, #1f2937);
    }

    .popover-arrow[data-placement="bottom"] {
      border-bottom-color: var(--background-dark, #1f2937);
    }

    .popover-arrow[data-placement="left"] {
      border-left-color: var(--background-dark, #1f2937);
    }

    .popover-arrow[data-placement="right"] {
      border-right-color: var(--background-dark, #1f2937);
    }

    .popover-arrow[data-placement="top-start"],
    .popover-arrow[data-placement="top-end"] {
      border-top-color: var(--background-dark, #1f2937);
    }

    .popover-arrow[data-placement="bottom-start"],
    .popover-arrow[data-placement="bottom-end"] {
      border-bottom-color: var(--background-dark, #1f2937);
    }

    .popover-arrow[data-placement="left-start"],
    .popover-arrow[data-placement="left-end"] {
      border-left-color: var(--background-dark, #1f2937);
    }

    .popover-arrow[data-placement="right-start"],
    .popover-arrow[data-placement="right-end"] {
      border-right-color: var(--background-dark, #1f2937);
    }
  }

  /* Accessibility */
  .popover-trigger:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .popover:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .popover {
      animation: none;
    }
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .popover {
      max-width: calc(100vw - 2rem);
    }
  }
</style>