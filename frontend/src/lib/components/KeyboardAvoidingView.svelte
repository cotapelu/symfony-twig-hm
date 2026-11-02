<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount, onDestroy } from 'svelte';

  const dispatch = createEventDispatcher();

  export let enabled: boolean = true;
  export let behavior: 'height' | 'position' | 'padding' = 'padding';
  export let offset: number = 0;
  export let className: string = '';

  let container: HTMLElement;
  let originalPaddingBottom = 0;
  let originalHeight = '';
  let keyboardHeight = 0;
  let isKeyboardVisible = false;

  $: classes = [
    'keyboard-avoiding-view',
    className
  ].filter(Boolean).join(' ');

  function updateLayout() {
    if (!container || !enabled) return;

    const viewportHeight = window.visualViewport?.height || window.innerHeight;
    const windowHeight = window.innerHeight;
    const keyboardVisible = viewportHeight < windowHeight;

    if (keyboardVisible) {
      keyboardHeight = windowHeight - viewportHeight;
      isKeyboardVisible = true;

      const adjustment = keyboardHeight + offset;

      switch (behavior) {
        case 'padding':
          container.style.paddingBottom = `${originalPaddingBottom + adjustment}px`;
          break;
        case 'height':
          container.style.height = `calc(${originalHeight} - ${adjustment}px)`;
          break;
        case 'position':
          container.style.transform = `translateY(-${adjustment}px)`;
          break;
      }

      dispatch('keyboardShow', { height: keyboardHeight });
    } else if (isKeyboardVisible) {
      isKeyboardVisible = false;
      keyboardHeight = 0;

      // Reset styles
      container.style.paddingBottom = `${originalPaddingBottom}px`;
      container.style.height = originalHeight;
      container.style.transform = '';

      dispatch('keyboardHide');
    }
  }

  function handleResize() {
    updateLayout();
  }

  function handleFocusIn(event: FocusEvent) {
    const target = event.target as HTMLElement;
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.contentEditable === 'true') {
      // Small delay to allow keyboard to appear
      setTimeout(updateLayout, 100);
    }
  }

  function handleFocusOut() {
    // Delay to check if keyboard actually hides
    setTimeout(updateLayout, 100);
  }

  onMount(() => {
    if (container) {
      originalPaddingBottom = parseFloat(getComputedStyle(container).paddingBottom) || 0;
      originalHeight = container.style.height || getComputedStyle(container).height;
    }

    if (enabled) {
      window.addEventListener('resize', handleResize);
      document.addEventListener('focusin', handleFocusIn);
      document.addEventListener('focusout', handleFocusOut);

      if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', handleResize);
      }
    }
  });

  onDestroy(() => {
    if (enabled) {
      window.removeEventListener('resize', handleResize);
      document.removeEventListener('focusin', handleFocusIn);
      document.removeEventListener('focusout', handleFocusOut);

      if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', handleResize);
      }
    }
  });
</script>

<div bind:this={container} class={classes} data-testid="keyboard-avoiding-view">
  <slot />
</div>

<style>
  .keyboard-avoiding-view {
    width: 100%;
    height: 100%;
    transition: padding-bottom 0.3s ease, height 0.3s ease, transform 0.3s ease;
  }

  /* Accessibility */
  .keyboard-avoiding-view:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .keyboard-avoiding-view {
      transition: none;
    }
  }
</style>