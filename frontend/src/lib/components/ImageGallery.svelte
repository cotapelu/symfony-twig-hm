<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  interface GalleryImage {
    src: string;
    thumbnail?: string;
    alt?: string;
    title?: string;
    description?: string;
    filename?: string;
  }

  export let images: GalleryImage[] = [];
  export let columns = 3;
  export let gap = '1rem';
  export let showZoom = true;
  export let showSlideshow = true;

  interface SelectedImage extends GalleryImage {
    index: number;
  }

  const dispatch = createEventDispatcher();

  let selectedImage: SelectedImage | null = null;
  let isZoomed = false;
  let slideshowActive = false;
  let slideshowIndex = 0;
  let slideshowInterval: ReturnType<typeof setInterval> | null = null;

  $: gridColumns = Math.min(columns, images.length);

  function openModal(image: GalleryImage, index: number) {
    selectedImage = { ...image, index };
    dispatch('imageSelect', { image, index });
  }

  function closeModal() {
    selectedImage = null;
    isZoomed = false;
    stopSlideshow();
  }

  function toggleZoom() {
    isZoomed = !isZoomed;
  }

  function nextImage() {
    if (selectedImage) {
      const nextIndex = (selectedImage.index + 1) % images.length;
      selectedImage = { ...images[nextIndex], index: nextIndex };
    }
  }

  function prevImage() {
    if (selectedImage) {
      const prevIndex = selectedImage.index === 0 ? images.length - 1 : selectedImage.index - 1;
      selectedImage = { ...images[prevIndex], index: prevIndex };
    }
  }

  function startSlideshow() {
    slideshowActive = true;
    slideshowIndex = selectedImage?.index || 0;
    slideshowInterval = setInterval(() => {
      slideshowIndex = (slideshowIndex + 1) % images.length;
      selectedImage = { ...images[slideshowIndex], index: slideshowIndex };
    }, 3000);
  }

  function stopSlideshow() {
    slideshowActive = false;
    if (slideshowInterval) {
      clearInterval(slideshowInterval);
      slideshowInterval = null;
    }
  }

  function downloadImage() {
    if (selectedImage) {
      const link = document.createElement('a');
      link.href = selectedImage.src;
      link.download = selectedImage.filename || 'image.jpg';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }

  function handleKeydown(event: KeyboardEvent) {
    if (!selectedImage) return;

    switch (event.key) {
      case 'Escape':
        closeModal();
        break;
      case 'ArrowRight':
        nextImage();
        break;
      case 'ArrowLeft':
        prevImage();
        break;
      case 'z':
      case 'Z':
        if (showZoom) toggleZoom();
        break;
    }
  }
</script>

<svelte:window onkeydown={handleKeydown} />

<div class="image-gallery" style="--columns: {gridColumns}; --gap: {gap};">
  {#each images as image, index}
    <div
      class="gallery-item"
      role="button"
      tabindex="0"
      onclick={() => openModal(image, index)}
      onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { openModal(image, index); e.preventDefault(); } }}
    >
      <img
        src={image.thumbnail || image.src}
        alt={image.alt || ''}
        loading="lazy"
        class="gallery-image"
      />
      <div class="gallery-overlay">
        <div class="gallery-info">
          {#if image.title}
            <h3 class="gallery-title">{image.title}</h3>
          {/if}
          {#if image.description}
            <p class="gallery-description">{image.description}</p>
          {/if}
        </div>
        <button class="zoom-btn" onclick={(e) => { e.stopPropagation(); openModal(image, index); }}>
          🔍
        </button>
      </div>
    </div>
  {/each}
</div>

<!-- Modal -->
{#if selectedImage}
  <div
    class="modal-overlay"
    role="dialog"
    aria-modal="true"
    aria-label="Image preview"
    onclick={closeModal}
    onkeydown={(e) => { if (e.key === 'Escape') closeModal(); }}
  >
    <div
      class="modal-content"
      class:zoomed={isZoomed}
      role="document"
      onclick={(e) => e.stopPropagation()}
      onkeydown={(e) => { if (e.key === 'Escape') closeModal(); }}
    >
      <!-- Close button -->
      <button class="modal-close" onclick={closeModal}>✕</button>

      <!-- Main image -->
      <div class="modal-image-container">
        <img
          src={selectedImage.src}
          alt={selectedImage.alt || ''}
          class="modal-image"
          class:zoomed={isZoomed}
        />

        <!-- Navigation arrows -->
        {#if images.length > 1}
          <button class="modal-nav modal-nav-prev" onclick={(e) => { e.stopPropagation(); prevImage(); }}>
            ‹
          </button>
          <button class="modal-nav modal-nav-next" onclick={(e) => { e.stopPropagation(); nextImage(); }}>
            ›
          </button>
        {/if}
      </div>

      <!-- Image info -->
      <div class="modal-info">
        {#if selectedImage.title}
          <h3 class="modal-title">{selectedImage.title}</h3>
        {/if}
        {#if selectedImage.description}
          <p class="modal-description">{selectedImage.description}</p>
        {/if}
        <div class="modal-meta">
          {selectedImage.index + 1} / {images.length}
        </div>
      </div>

      <!-- Controls -->
      <div class="modal-controls">
        {#if showZoom}
          <button class="control-btn" onclick={(e) => { e.stopPropagation(); toggleZoom(); }}>
            {isZoomed ? '🔍-' : '🔍+'}
          </button>
        {/if}

        {#if showSlideshow}
          <button
            class="control-btn"
            onclick={(e) => { e.stopPropagation(); slideshowActive ? stopSlideshow() : startSlideshow(); }}
          >
            {slideshowActive ? '⏸️' : '▶️'}
          </button>
        {/if}

        <button class="control-btn" onclick={(e) => { e.stopPropagation(); downloadImage(); }}>
          ⬇️
        </button>
      </div>

      <!-- Thumbnails -->
      {#if images.length > 1}
        <div class="modal-thumbnails">
          {#each images as image, index}
            <button
              class="thumbnail-btn {index === selectedImage.index ? 'active' : ''}"
              onclick={(e) => { e.stopPropagation(); openModal(image, index); }}
            >
              <img src={image.thumbnail || image.src} alt="" class="thumbnail-image" />
            </button>
          {/each}
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  .image-gallery {
    display: grid;
    grid-template-columns: repeat(var(--columns, 3), 1fr);
    gap: var(--gap, 1rem);
  }

  .gallery-item {
    position: relative;
    cursor: pointer;
    border-radius: 0.5rem;
    overflow: hidden;
    aspect-ratio: 1;
    transition: transform 0.2s ease;
  }

  .gallery-item:hover {
    transform: scale(1.02);
  }

  .gallery-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.2s ease;
  }

  .gallery-item:hover .gallery-image {
    transform: scale(1.1);
  }

  .gallery-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  .gallery-item:hover .gallery-overlay {
    opacity: 1;
  }

  .gallery-info {
    color: white;
    flex: 1;
  }

  .gallery-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
  }

  .gallery-description {
    font-size: 0.875rem;
    margin: 0;
    opacity: 0.9;
  }

  .zoom-btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .zoom-btn:hover {
    background: white;
    transform: scale(1.1);
  }

  /* Modal */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.3s ease;
  }

  .modal-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    background: white;
    border-radius: 0.5rem;
    overflow: hidden;
    animation: slideIn 0.3s ease;
  }

  .modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
  }

  .modal-image-container {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 400px;
  }

  .modal-image {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    transition: transform 0.3s ease;
    cursor: zoom-in;
  }

  .modal-image.zoomed {
    transform: scale(1.5);
    cursor: zoom-out;
  }

  .modal-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }

  .modal-nav:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: translateY(-50%) scale(1.1);
  }

  .modal-nav-prev {
    left: 1rem;
  }

  .modal-nav-next {
    right: 1rem;
  }

  .modal-info {
    padding: 1rem;
    background: #f8fafc;
  }

  .modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
  }

  .modal-description {
    color: #64748b;
    margin: 0 0 0.5rem 0;
  }

  .modal-meta {
    font-size: 0.875rem;
    color: #94a3b8;
  }

  .modal-controls {
    padding: 0 1rem 1rem;
    display: flex;
    gap: 0.5rem;
    justify-content: center;
  }

  .control-btn {
    background: #e5e7eb;
    border: none;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .control-btn:hover {
    background: #d1d5db;
  }

  .modal-thumbnails {
    padding: 1rem;
    background: #f8fafc;
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
  }

  .thumbnail-btn {
    border: 2px solid transparent;
    border-radius: 0.375rem;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .thumbnail-btn.active {
    border-color: #3b82f6;
  }

  .thumbnail-btn:hover {
    border-color: #9ca3af;
  }

  .thumbnail-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  @keyframes slideIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
  }

  /* Responsive */
  @media (max-width: 768px) {
    .image-gallery {
      --columns: 2;
    }

    .modal-content {
      max-width: 95vw;
      max-height: 95vh;
    }

    .modal-nav {
      width: 40px;
      height: 40px;
      font-size: 1.2rem;
    }
  }

  @media (max-width: 480px) {
    .image-gallery {
      --columns: 1;
    }
  }
</style>
