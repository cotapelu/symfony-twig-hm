<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  export let disabled: boolean = false;
  export let className: string = '';

  let element: HTMLElement;
  let isPressed = false;

  $: classes = [
    'touch-feedback',
    isPressed ? 'touch-feedback-active' : '',
    className
  ].filter(Boolean).join(' ');

  function handleTouchStart(event: TouchEvent) {
    if (disabled) return;

    isPressed = true;
    dispatch('touchstart', event);
  }

  function handleTouchEnd(event: TouchEvent) {
    if (disabled) return;

    isPressed = false;
    dispatch('touchend', event);
  }

  function handleTouchCancel(event: TouchEvent) {
    if (disabled) return;

    isPressed = false;
    dispatch('touchcancel', event);
  }

  function handleClick(event: MouseEvent) {
    if (disabled) return;

    dispatch('click', event);
  }
</script>

<div
  bind:this={element}
  class={classes}
  ontouchstart={handleTouchStart}
  ontouchend={handleTouchEnd}
  ontouchcancel={handleTouchCancel}
  onclick={handleClick}
>
  <slot />
</div>

<style>
  .touch-feedback {
    position: relative;
    display: inline-block;
    transition: transform 0.1s ease, opacity 0.1s ease;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
  }

  .touch-feedback-active {
    transform: scale(0.95);
    opacity: 0.8;
  }

  /* Accessibility */
  .touch-feedback:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .touch-feedback {
      transition: none;
    }

    .touch-feedback-active {
      transform: none;
      opacity: 1;
    }
  }

  /* High contrast mode */
  @media (prefers-contrast: high) {
    .touch-feedback-active {
      opacity: 1;
      transform: none;
      border: 2px solid var(--focus-ring, #3b82f6);
    }
  }
</style>