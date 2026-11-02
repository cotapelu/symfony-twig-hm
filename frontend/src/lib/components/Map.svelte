<script lang="ts">
  import { onMount } from 'svelte';
  import { createEventDispatcher } from 'svelte';

  export let center: [number, number] = [51.505, -0.09];
  export let zoom = 13;
  export let height = '300px';
  export let markers: Array<{ lat: number; lng: number; popup?: string; icon?: string }> = [];
  export const controls = true;

  const dispatch = createEventDispatcher();

  let mapElement: HTMLElement;
  let map: any;

  onMount(async () => {
    console.log('Map initialized at', center, 'zoom', zoom);
    dispatch('ready');
  });

  function handleClick(lat: number, lng: number) {
    dispatch('click', { lat, lng });
  }

  export function setCenter(newCenter: [number, number]) {
    center = newCenter;
  }

  export function setZoom(newZoom: number) {
    zoom = newZoom;
  }

  export function addMarker(marker: { lat: number; lng: number; popup?: string; icon?: string }) {
    markers = [...markers, marker];
  }
</script>

<div class="map-container" style="height: {height};">
  <div
    bind:this={mapElement}
    class="map"
    role="button"
    tabindex="0"
    aria-label="Map at center {center[0]}, {center[1]} with zoom level {zoom}"
    onclick={() => handleClick(center[0], center[1])}
    onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { handleClick(center[0], center[1]); e.preventDefault(); } }}
  >
    <!-- Placeholder for map -->
    <div class="map-placeholder">
      <p>Interactive Map</p>
      <small>Center: {center[0]}, {center[1]} | Zoom: {zoom}</small>
      {#each markers as marker}
        <div class="marker" style="left: {Math.random() * 100}%; top: {Math.random() * 100}%;">
          📍 {marker.popup || ''}
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .map-container {
    position: relative;
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 8px;
    overflow: hidden;
  }

  .map {
    width: 100%;
    height: 100%;
    background-color: var(--bg-secondary, #f9fafb);
    cursor: pointer;
  }

  .map-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-secondary, #6b7280);
    text-align: center;
  }

  .marker {
    position: absolute;
    transform: translate(-50%, -100%);
    background-color: var(--bg-primary, #ffffff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 4px;
    padding: 0.25rem;
    font-size: 0.75rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
</style>