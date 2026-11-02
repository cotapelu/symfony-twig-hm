<script lang="ts">
  import { createEventDispatcher, onMount, onDestroy } from 'svelte';

  interface Props {
    trigger?: 'click' | 'hover' | 'focus';
    placement?: 'bottom-start' | 'bottom-end' | 'top-start' | 'top-end';
    disabled?: boolean;
    visible?: boolean;
    overlayClass?: string;
    mouseEnterDelay?: number;
    mouseLeaveDelay?: number;
  }

  let {
    trigger = 'click',
    placement = 'bottom-start',
    disabled = false,
    visible = false,
    overlayClass = '',
    mouseEnterDelay = 100,
    mouseLeaveDelay = 100
  }: Props = $props();

  const dispatch = createEventDispatcher();

  let triggerElement: HTMLElement;
  let dropdownElement: HTMLElement;
  let hoverTimeout: ReturnType<typeof setTimeout>;
  let focusTimeout: ReturnType<typeof setTimeout>;
  let isVisible = $state(visible);
  let mounted = $state(false);

  // Only sync on mount, then become uncontrolled
  $effect(() => {
    if (!mounted) {
      isVisible = visible;
      mounted = true;
    }
  });

  function showDropdown() {
    if (disabled) return;
    clearTimeout(hoverTimeout);
    clearTimeout(focusTimeout);
    isVisible = true;
    dispatch('visibleChange', true);
  }

  function hideDropdown(immediate = false) {
    clearTimeout(hoverTimeout);
    clearTimeout(focusTimeout);
    if (immediate || mouseLeaveDelay <= 0) {
      isVisible = false;
      dispatch('visibleChange', false);
    } else {
      hoverTimeout = setTimeout(() => {
        isVisible = false;
        dispatch('visibleChange', false);
      }, mouseLeaveDelay);
    }
  }

  function handleTriggerClick() {
    if (trigger === 'click') {
      if (isVisible) {
        hideDropdown(true);
      } else {
        showDropdown();
      }
    }
  }

  function handleTriggerMouseEnter() {
    if (trigger === 'hover') {
      clearTimeout(hoverTimeout);
      if (mouseEnterDelay <= 0) {
        showDropdown();
      } else {
        focusTimeout = setTimeout(() => {
          showDropdown();
        }, mouseEnterDelay);
      }
    }
  }

  function handleTriggerMouseLeave() {
    if (trigger === 'hover') {
      hideDropdown();
    }
  }

  function handleTriggerFocus() {
    if (trigger === 'focus') {
      showDropdown();
    }
  }

  function handleTriggerBlur() {
    if (trigger === 'focus') {
      hideDropdown(true);
    }
  }

  function handleDropdownMouseEnter() {
    if (trigger === 'hover') {
      clearTimeout(hoverTimeout);
    }
  }

  function handleDropdownMouseLeave() {
    if (trigger === 'hover') {
      hideDropdown();
    }
  }

  function handleDropdownFocus() {
    if (trigger === 'focus') {
      clearTimeout(focusTimeout);
    }
  }

  function handleDropdownBlur() {
    if (trigger === 'focus') {
      focusTimeout = setTimeout(() => {
        hideDropdown();
      }, 100);
    }
  }

  // Close dropdown when clicking outside
  onMount(() => {
    function handleClickOutside(event: MouseEvent) {
      if (triggerElement && !triggerElement.contains(event.target as Node) &&
          dropdownElement && !dropdownElement.contains(event.target as Node)) {
        isVisible = false;
        dispatch('visibleChange', false);
      }
    }

    document.addEventListener('click', handleClickOutside);

    return () => {
      document.removeEventListener('click', handleClickOutside);
    };
  });

  onDestroy(() => {
    clearTimeout(hoverTimeout);
    clearTimeout(focusTimeout);
  });

  let classes = $derived([
    'dropdown',
    `dropdown-${placement}`,
    overlayClass
  ].filter(Boolean).join(' '));
</script>

<div class="dropdown-container">
  <div
    bind:this={triggerElement}
    class="dropdown-trigger"
    role="button"
    tabindex={disabled ? -1 : 0}
    aria-haspopup="menu"
    aria-expanded={isVisible}
    onclick={handleTriggerClick}
    onmouseenter={handleTriggerMouseEnter}
    onmouseleave={handleTriggerMouseLeave}
    onfocus={handleTriggerFocus}
    onblur={handleTriggerBlur}
    onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { handleTriggerClick(); e.preventDefault(); } }}
  >
    <slot name="trigger" />
  </div>

  {#if isVisible}
    <div
      bind:this={dropdownElement}
      class={classes}
      role="menu"
      tabindex="0"
      onmouseenter={handleDropdownMouseEnter}
      onmouseleave={handleDropdownMouseLeave}
      onfocus={handleDropdownFocus}
      onblur={handleDropdownBlur}
    >
      <slot />
    </div>
  {/if}
</div>

<style>
  .dropdown-container {
    position: relative;
    display: inline-block;
  }

  .dropdown-trigger {
    cursor: pointer;
  }

  .dropdown-trigger:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .dropdown {
    position: absolute;
    z-index: 1000;
    background: var(--bg-primary, white);
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    min-width: 160px;
    padding: 0.5rem 0;
  }

  /* Placements */
  .dropdown-bottom-start {
    top: 100%;
    left: 0;
    margin-top: 0.25rem;
  }

  .dropdown-bottom-end {
    top: 100%;
    right: 0;
    margin-top: 0.25rem;
  }

  .dropdown-top-start {
    bottom: 100%;
    left: 0;
    margin-bottom: 0.25rem;
  }

  .dropdown-top-end {
    bottom: 100%;
    right: 0;
    margin-bottom: 0.25rem;
  }

  /* Animations */
  .dropdown {
    animation: dropdown-appear 0.2s ease;
  }

  @keyframes dropdown-appear {
    from {
      opacity: 0;
      transform: translateY(-4px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Mobile adjustments */
  @media (max-width: 640px) {
    .dropdown {
      position: fixed;
      top: auto;
      bottom: 0;
      left: 0;
      right: 0;
      margin: 0;
      border-radius: 0;
      max-height: 50vh;
      overflow-y: auto;
    }

    .dropdown-bottom-start,
    .dropdown-bottom-end,
    .dropdown-top-start,
    .dropdown-top-end {
      margin: 0;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .dropdown {
      border-width: 2px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .dropdown {
      animation: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .dropdown {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }
  }
</style>