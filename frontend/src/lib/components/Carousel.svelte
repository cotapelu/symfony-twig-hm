<script lang="ts">
  import { onMount } from 'svelte';
  import DOMPurify from 'dompurify';

  interface CarouselItem {
    type: 'image' | 'video' | 'content';
    src?: string;
    alt?: string;
    content?: string;
  }

  export let items: CarouselItem[] = [];
  export let autoplay = false;
  export let autoplayDelay = 3000;
  export let showDots = true;
  export let showArrows = true;
  export let parallax = false;

  let currentIndex = 0;
  let autoplayInterval: ReturnType<typeof setInterval> | null = null;
  let carouselElement: HTMLElement;
  let isTransitioning = false;

  $: totalItems = items.length;

  onMount(() => {
    if (autoplay && totalItems > 1) {
      startAutoplay();
    }

    return () => {
      stopAutoplay();
    };
  });

  function startAutoplay(): void {
    autoplayInterval = setInterval(() => {
      next();
    }, autoplayDelay);
  }

  function stopAutoplay(): void {
    if (autoplayInterval) {
      clearInterval(autoplayInterval);
      autoplayInterval = null;
    }
  }

  function next(): void {
    if (!isTransitioning) {
      currentIndex = (currentIndex + 1) % totalItems;
      handleTransition();
    }
  }

  function prev(): void {
    if (!isTransitioning) {
      currentIndex = currentIndex === 0 ? totalItems - 1 : currentIndex - 1;
      handleTransition();
    }
  }

  function goTo(index: number): void {
    if (!isTransitioning && index !== currentIndex) {
      currentIndex = index;
      handleTransition();
    }
  }

  function handleTransition(): void {
    isTransitioning = true;
    setTimeout(() => {
      isTransitioning = false;
    }, 500);
  }

  function handleMouseEnter(): void {
    if (autoplay) stopAutoplay();
  }

  function handleMouseLeave(): void {
    if (autoplay && totalItems > 1) startAutoplay();
  }

  function getTransform(): string {
    if (parallax) {
      return `translateX(-${currentIndex * 100}%)`;
    }
    return `translateX(-${currentIndex * 100}%)`;
  }
</script>

<div
  class="carousel {parallax ? 'parallax' : ''}"
  bind:this={carouselElement}
  role="region"
  aria-label="Carousel"
  aria-roledescription="carousel"
  onmouseenter={handleMouseEnter}
  onmouseleave={handleMouseLeave}
>
  <!-- Slides -->
  <div class="carousel-track" style="transform: {getTransform()}" role="list" aria-live="polite">
    {#each items as item, index}
      <div class="carousel-slide" role="group" aria-roledescription="slide" aria-label="{index + 1} of {items.length}">
        {#if item.type === 'image'}
          <img src={item.src} alt={item.alt || ''} class="carousel-image" />
        {:else if item.type === 'video'}
          <video src={item.src} controls class="carousel-video">
            <track kind="captions" />
          </video>
        {:else}
          <div class="carousel-content">
            {@html DOMPurify.sanitize(item.content || '')}
          </div>
        {/if}
      </div>
    {/each}
  </div>

  <!-- Navigation arrows -->
  {#if showArrows && totalItems > 1}
    <button
      class="carousel-arrow carousel-arrow-prev"
      onclick={prev}
      disabled={isTransitioning}
      aria-label="Previous slide"
    >
      ‹
    </button>
    <button
      class="carousel-arrow carousel-arrow-next"
      onclick={next}
      disabled={isTransitioning}
      aria-label="Next slide"
    >
      ›
    </button>
  {/if}

  <!-- Dots indicator -->
  {#if showDots && totalItems > 1}
    <div class="carousel-dots" role="tablist" aria-label="Slide indicators">
      {#each items as _, index}
        <button
          class="carousel-dot {index === currentIndex ? 'active' : ''}"
          role="tab"
          aria-selected={index === currentIndex}
          aria-label="Go to slide {index + 1}"
          onclick={() => goTo(index)}
          disabled={isTransitioning}
        ></button>
      {/each}
    </div>
  {/if}
</div>

<style>
  .carousel {
    position: relative;
    width: 100%;
    height: 400px;
    overflow: hidden;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .carousel-track {
    display: flex;
    height: 100%;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .carousel-slide {
    min-width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
  }

  .carousel-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .carousel-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .carousel-content {
    padding: 2rem;
    text-align: center;
    max-width: 600px;
  }

  /* Parallax variant */
  .carousel.parallax .carousel-track {
    width: calc(100% * var(--total-items, 1));
  }

  .carousel.parallax .carousel-slide {
    width: calc(100% / var(--total-items, 1));
  }

  /* Navigation arrows */
  .carousel-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .carousel-arrow:hover:not(:disabled) {
    background: rgba(0, 0, 0, 0.8);
    transform: translateY(-50%) scale(1.1);
  }

  .carousel-arrow:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .carousel-arrow-prev {
    left: 1rem;
  }

  .carousel-arrow-next {
    right: 1rem;
  }

  /* Dots indicator */
  .carousel-dots {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.5rem;
    z-index: 10;
  }

  .carousel-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.5);
    background: transparent;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .carousel-dot:hover:not(:disabled),
  .carousel-dot.active {
    background: white;
    border-color: white;
  }

  .carousel-dot:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* Responsive design */
  @media (max-width: 768px) {
    .carousel {
      height: 300px;
    }

    .carousel-arrow {
      width: 40px;
      height: 40px;
      font-size: 1.2rem;
    }

    .carousel-arrow-prev {
      left: 0.5rem;
    }

    .carousel-arrow-next {
      right: 0.5rem;
    }
  }
</style>
