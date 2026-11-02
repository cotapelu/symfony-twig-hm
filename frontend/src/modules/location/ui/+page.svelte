<script lang="ts">
  import { locationStore, getCurrentLocation, shareLocation, trackLocation } from '../state/locationStore';
  import { onMount } from 'svelte';
  import type { Place, SharedLocation } from '../contracts/locationTypes';

  let mapElement: HTMLDivElement;
  let activeTab = 'map';
  let searchQuery = '';
  let selectedPlace: Place | null = null;
  let showShareModal = false;
  let shareDuration = 60; // minutes

  onMount(() => {
    // Initialize map (mock implementation)
    getCurrentLocation();
  });

  $: currentLocation = $locationStore.currentLocation;
  $: nearbyPlaces = $locationStore.nearbyPlaces;
  $: sharedLocations = $locationStore.sharedLocations;
  $: loading = $locationStore.loading;
  $: isTracking = $locationStore.isTracking;

  function handleSearch() {
    if (searchQuery.trim()) {
      // Search places
    }
  }

  function startTracking() {
    trackLocation(true);
  }

  function stopTracking() {
    trackLocation(false);
  }

  function handleShareLocation() {
    if (currentLocation) {
      shareLocation({
        latitude: currentLocation.latitude,
        longitude: currentLocation.longitude,
        duration: shareDuration * 60 // convert to seconds
      });
      showShareModal = false;
    }
  }

  function getPlaceIcon(type: string) {
    switch (type) {
      case 'restaurant': return '🍽️';
      case 'cafe': return '☕';
      case 'shop': return '🛍️';
      case 'hotel': return '🏨';
      case 'gas': return '⛽';
      case 'parking': return '🅿️';
      case 'hospital': return '🏥';
      case 'bank': return '🏦';
      default: return '📍';
    }
  }

  function formatDistance(meters: number) {
    if (meters < 1000) return `${Math.round(meters)}m`;
    return `${(meters / 1000).toFixed(1)}km`;
  }
</script>

<div class="location-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Location</h1>
        <div class="flex gap-2">
          <button 
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
            onclick={() => showShareModal = true}
          >
            📤 Share
          </button>
          {#if isTracking}
            <button 
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
              onclick={stopTracking}
            >
              Stop Tracking
            </button>
          {:else}
            <button 
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
              onclick={startTracking}
            >
              Start Tracking
            </button>
          {/if}
        </div>
      </div>

      <div class="flex gap-4 mb-6 border-b">
        <button 
          class="px-4 py-2 font-medium {activeTab === 'map' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
          onclick={() => activeTab = 'map'}
        >
          Map
        </button>
        <button 
          class="px-4 py-2 font-medium {activeTab === 'nearby' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
          onclick={() => activeTab = 'nearby'}
        >
          Nearby Places
        </button>
        <button 
          class="px-4 py-2 font-medium {activeTab === 'shared' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
          onclick={() => activeTab = 'shared'}
        >
          Shared Locations
        </button>
      </div>

      {#if loading}
        <div class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      {:else if activeTab === 'map'}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2">
            <div bind:this={mapElement} class="bg-gray-200 rounded-lg h-96 lg:h-[500px] flex items-center justify-center relative overflow-hidden">
              <!-- Mock Map -->
              <div class="absolute inset-0 bg-gradient-to-br from-blue-100 to-green-100">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                  <div class="w-8 h-8 bg-blue-600 rounded-full border-4 border-white shadow-lg flex items-center justify-center">
                    <span class="text-white text-xs">📍</span>
                  </div>
                  <p class="text-center text-sm font-medium mt-2 bg-white px-2 py-1 rounded shadow">You are here</p>
                </div>
                <!-- Mock nearby places on map -->
                {#each nearbyPlaces.slice(0, 5) as place, i}
                  <div class="absolute" style="top: {20 + i * 15}%; left: {20 + i * 12}%;">
                    <div class="w-6 h-6 bg-red-500 rounded-full border-2 border-white shadow flex items-center justify-center">
                      <span class="text-white text-xs">{getPlaceIcon(place.type)}</span>
                    </div>
                  </div>
                {/each}
              </div>
            </div>
          </div>
          <div>
            {#if currentLocation}
              <div class="bg-white rounded-lg shadow p-4 mb-4">
                <h3 class="font-semibold mb-2">Current Location</h3>
                <div class="space-y-2 text-sm">
                  <p><span class="text-gray-500">Latitude:</span> {currentLocation.latitude.toFixed(6)}</p>
                  <p><span class="text-gray-500">Longitude:</span> {currentLocation.longitude.toFixed(6)}</p>
                  {#if currentLocation.accuracy}
                    <p><span class="text-gray-500">Accuracy:</span> ±{Math.round(currentLocation.accuracy)}m</p>
                  {/if}
                  {#if currentLocation.address}
                    <p><span class="text-gray-500">Address:</span> {currentLocation.address}</p>
                  {/if}
                </div>
              </div>
            {/if}

            <div class="bg-white rounded-lg shadow p-4">
              <h3 class="font-semibold mb-2">Quick Actions</h3>
              <div class="space-y-2">
                <button class="w-full py-2 px-4 bg-gray-100 rounded-lg hover:bg-gray-200 text-left flex items-center gap-2">
                  🧭 Get Directions
                </button>
                <button class="w-full py-2 px-4 bg-gray-100 rounded-lg hover:bg-gray-200 text-left flex items-center gap-2">
                  🏪 Find Nearby
                </button>
                <button class="w-full py-2 px-4 bg-gray-100 rounded-lg hover:bg-gray-200 text-left flex items-center gap-2">
                  📍 Save Location
                </button>
              </div>
            </div>
          </div>
        </div>

      {:else if activeTab === 'nearby'}
        <div class="mb-4">
          <div class="flex gap-2">
            <input
              type="text"
              bind:value={searchQuery}
              placeholder="Search nearby places..."
              class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
            <button 
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              onclick={handleSearch}
            >
              Search
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {#each nearbyPlaces as place}
            <div class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow cursor-pointer" onclick={() => selectedPlace = place}>
              <div class="flex items-start gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-2xl">
                  {getPlaceIcon(place.type)}
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold truncate">{place.name}</h3>
                  <p class="text-sm text-gray-500 truncate">{place.address}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-sm text-blue-600">{formatDistance(place.distance)}</span>
                    {#if place.rating}
                      <span class="text-sm text-yellow-500">★ {place.rating}</span>
                    {/if}
                  </div>
                </div>
              </div>
            </div>
          {/each}
          
          {#if nearbyPlaces.length === 0}
            <div class="col-span-full text-center py-12 text-gray-500">
              <div class="text-6xl mb-4">📍</div>
              <p>No nearby places found</p>
            </div>
          {/if}
        </div>

      {:else if activeTab === 'shared'}
        <div class="space-y-4">
          {#each sharedLocations as shared}
            <div class="bg-white rounded-lg shadow p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <img 
                    src={shared.userAvatar || '/default-avatar.png'} 
                    alt={shared.userName}
                    class="w-12 h-12 rounded-full"
                  />
                  <div>
                    <h3 class="font-semibold">{shared.userName}</h3>
                    <p class="text-sm text-gray-500">
                      Shared {new Date(shared.timestamp).toLocaleTimeString()}
                      {#if shared.expiresAt}
                        • Expires {new Date(shared.expiresAt).toLocaleTimeString()}
                      {/if}
                    </p>
                  </div>
                </div>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                  View
                </button>
              </div>
            </div>
          {/each}

          {#if sharedLocations.length === 0}
            <div class="text-center py-12 text-gray-500">
              <div class="text-6xl mb-4">📤</div>
              <p>No shared locations</p>
            </div>
          {/if}
        </div>
      {/if}
    </div>
  </div>
</div>

{#if showShareModal}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
      <h2 class="text-xl font-bold mb-4">Share Location</h2>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
          <select bind:value={shareDuration} class="w-full px-3 py-2 border rounded-lg">
            <option value={15}>15 minutes</option>
            <option value={30}>30 minutes</option>
            <option value={60}>1 hour</option>
            <option value={180}>3 hours</option>
            <option value={480}>8 hours</option>
            <option value={1440}>24 hours</option>
          </select>
        </div>
        <div class="bg-gray-50 p-3 rounded-lg">
          <p class="text-sm text-gray-600">Your current location will be shared with selected contacts.</p>
        </div>
      </div>
      <div class="flex gap-2 mt-6">
        <button 
          class="flex-1 py-2 border rounded-lg hover:bg-gray-50"
          onclick={() => showShareModal = false}
        >
          Cancel
        </button>
        <button 
          class="flex-1 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          onclick={handleShareLocation}
        >
          Share
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .location-page {
    min-height: 100vh;
    background: #f9fafb;
  }
</style>
