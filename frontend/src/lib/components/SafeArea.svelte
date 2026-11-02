<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount } from 'svelte';

  const dispatch = createEventDispatcher();

  export let top: boolean = true;
  export let bottom: boolean = true;
  export let left: boolean = true;
  export let right: boolean = true;
  export let className: string = '';

  let element: HTMLElement;
  let insets = {
    top: 0,
    bottom: 0,
    left: 0,
    right: 0
  };

  $: classes = [
    'safe-area',
    className
  ].filter(Boolean).join(' ');

  $: style = `
    ${top ? `padding-top: ${insets.top}px;` : ''}
    ${bottom ? `padding-bottom: ${insets.bottom}px;` : ''}
    ${left ? `padding-left: ${insets.left}px;` : ''}
    ${right ? `padding-right: ${insets.right}px;` : ''}
  `;

  function detectSafeAreaInsets() {
    if (!element) return;

    // Use CSS env() for iOS Safari
    const computedStyle = getComputedStyle(element);

    insets = {
      top: parseFloat(computedStyle.getPropertyValue('padding-top')) || 0,
      bottom: parseFloat(computedStyle.getPropertyValue('padding-bottom')) || 0,
      left: parseFloat(computedStyle.getPropertyValue('padding-left')) || 0,
      right: parseFloat(computedStyle.getPropertyValue('padding-right')) || 0
    };

    // Fallback detection for browsers without env() support
    if (window.visualViewport && insets.top === 0) {
      const viewport = window.visualViewport;
      const windowHeight = window.innerHeight;
      const viewportHeight = viewport.height;

      if (viewportHeight < windowHeight) {
        insets.top = windowHeight - viewportHeight;
      }
    }

    dispatch('insetsChange', insets);
  }

  onMount(() => {
    detectSafeAreaInsets();

    // Re-detect on orientation change or resize
    window.addEventListener('orientationchange', detectSafeAreaInsets);
    window.addEventListener('resize', detectSafeAreaInsets);

    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', detectSafeAreaInsets);
    }
  });
</script>

<div bind:this={element} class={classes} {style}>
  <slot />
</div>

<style>
  .safe-area {
    width: 100%;
    height: 100%;
    /* iOS Safari safe area support */
    padding-top: env(safe-area-inset-top);
    padding-bottom: env(safe-area-inset-bottom);
    padding-left: env(safe-area-inset-left);
    padding-right: env(safe-area-inset-right);
  }

  /* Fallback for browsers without env() support */
  @supports not (padding-top: env(safe-area-inset-top)) {
    .safe-area {
      padding-top: constant(safe-area-inset-top);
      padding-bottom: constant(safe-area-inset-bottom);
      padding-left: constant(safe-area-inset-left);
      padding-right: constant(safe-area-inset-right);
    }
  }

  /* Accessibility */
  .safe-area:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }
</style>