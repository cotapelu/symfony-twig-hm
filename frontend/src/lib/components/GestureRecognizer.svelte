<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  export let disabled: boolean = false;
  export let enablePan: boolean = true;
  export let enablePinch: boolean = true;
  export let enableRotate: boolean = true;
  export let minPointers: number = 1;
  export let maxPointers: number = 2;
  export let className: string = '';

  let element: HTMLElement;
  let touches: Touch[] = [];
  let isActive = false;
  let startTime = 0;
  let gestureState = {
    pan: { x: 0, y: 0, deltaX: 0, deltaY: 0 },
    pinch: { scale: 1, deltaScale: 0 },
    rotate: { angle: 0, deltaAngle: 0 }
  };

  $: classes = [
    'gesture-recognizer',
    className
  ].filter(Boolean).join(' ');

  function getDistance(touch1: Touch, touch2: Touch): number {
    const dx = touch1.clientX - touch2.clientX;
    const dy = touch1.clientY - touch2.clientY;
    return Math.sqrt(dx * dx + dy * dy);
  }

  function getAngle(touch1: Touch, touch2: Touch): number {
    return Math.atan2(touch2.clientY - touch1.clientY, touch2.clientX - touch1.clientX);
  }

  function handleTouchStart(event: TouchEvent) {
    if (disabled) return;

    touches = Array.from(event.touches);
    if (touches.length < minPointers || touches.length > maxPointers) return;

    isActive = true;
    startTime = Date.now();

    // Initialize gesture state
    if (touches.length === 1 && enablePan) {
      gestureState.pan.x = touches[0].clientX;
      gestureState.pan.y = touches[0].clientY;
      gestureState.pan.deltaX = 0;
      gestureState.pan.deltaY = 0;
    } else if (touches.length === 2) {
      if (enablePinch) {
        gestureState.pinch.scale = getDistance(touches[0], touches[1]);
        gestureState.pinch.deltaScale = 0;
      }
      if (enableRotate) {
        gestureState.rotate.angle = getAngle(touches[0], touches[1]);
        gestureState.rotate.deltaAngle = 0;
      }
    }

    dispatch('gesturestart', {
      touches: touches.length,
      position: touches.length === 1 ? { x: touches[0].clientX, y: touches[0].clientY } : null
    });
  }

  function handleTouchMove(event: TouchEvent) {
    if (!isActive || disabled) return;

    event.preventDefault();
    const currentTouches = Array.from(event.touches);

    if (currentTouches.length === 1 && enablePan && touches.length === 1) {
      // Pan gesture
      const deltaX = currentTouches[0].clientX - gestureState.pan.x;
      const deltaY = currentTouches[0].clientY - gestureState.pan.y;

      gestureState.pan.deltaX = deltaX;
      gestureState.pan.deltaY = deltaY;

      dispatch('pan', {
        x: currentTouches[0].clientX,
        y: currentTouches[0].clientY,
        deltaX,
        deltaY
      });
    } else if (currentTouches.length === 2 && touches.length === 2) {
      const touch1 = currentTouches[0];
      const touch2 = currentTouches[1];

      if (enablePinch) {
        // Pinch gesture
        const distance = getDistance(touch1, touch2);
        const deltaScale = distance / gestureState.pinch.scale;

        gestureState.pinch.deltaScale = deltaScale;

        dispatch('pinch', {
          scale: deltaScale,
          center: {
            x: (touch1.clientX + touch2.clientX) / 2,
            y: (touch1.clientY + touch2.clientY) / 2
          }
        });
      }

      if (enableRotate) {
        // Rotate gesture
        const angle = getAngle(touch1, touch2);
        const deltaAngle = angle - gestureState.rotate.angle;

        gestureState.rotate.deltaAngle = deltaAngle;

        dispatch('rotate', {
          angle: deltaAngle,
          center: {
            x: (touch1.clientX + touch2.clientX) / 2,
            y: (touch1.clientY + touch2.clientY) / 2
          }
        });
      }
    }
  }

  function handleTouchEnd(event: TouchEvent) {
    if (!isActive || disabled) return;

    const remainingTouches = Array.from(event.touches);
    const duration = Date.now() - startTime;

    if (remainingTouches.length === 0) {
      isActive = false;

      dispatch('gestureend', {
        duration,
        finalState: gestureState
      });

      // Reset state
      touches = [];
      gestureState = {
        pan: { x: 0, y: 0, deltaX: 0, deltaY: 0 },
        pinch: { scale: 1, deltaScale: 0 },
        rotate: { angle: 0, deltaAngle: 0 }
      };
    } else {
      // Update touches for continued gesture
      touches = remainingTouches;
    }
  }

  function handleTouchCancel(event: TouchEvent) {
    if (!isActive || disabled) return;

    isActive = false;
    touches = [];

    dispatch('gesturecancel');

    gestureState = {
      pan: { x: 0, y: 0, deltaX: 0, deltaY: 0 },
      pinch: { scale: 1, deltaScale: 0 },
      rotate: { angle: 0, deltaAngle: 0 }
    };
  }
</script>

<div
  bind:this={element}
  class={classes}
  ontouchstart={handleTouchStart}
  ontouchmove={handleTouchMove}
  ontouchend={handleTouchEnd}
  ontouchcancel={handleTouchCancel}
>
  <slot />
</div>

<style>
  .gesture-recognizer {
    width: 100%;
    height: 100%;
    touch-action: none;
    user-select: none;
  }

  /* Accessibility */
  .gesture-recognizer:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }
</style>