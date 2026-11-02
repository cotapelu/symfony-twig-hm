<script lang="ts">
  import { onMount } from 'svelte';

  interface SavedLocation {
    id: string;
    name: string;
    lat: number;
    lng: number;
    address: string;
    category: 'home' | 'work' | 'favorite' | 'custom';
    createdAt: string;
  }

  interface RecentLocation {
    id: string;
    name: string;
    lat: number;
    lng: number;
    timestamp: string;
  }

  interface MapMarker {
    id: string;
    lat: number;
    lng: number;
    name: string;
    type: 'saved' | 'recent' | 'search' | 'current';
  }

  interface RouteResult {
    distance: string;
    duration: string;
    steps: { instruction: string; distance: string }[];
  }

  // State
  let mapContainer: HTMLElement;
  let map: any = null;
  let L: any = null;
  let markers: any[] = [];
  
  let savedLocations: SavedLocation[] = [];
  let recentLocations: RecentLocation[] = [];
  let mapMarkers: MapMarker[] = [];
  
  let searchQuery = '';
  let searchResults: any[] = [];
  let isSearching = false;
  
  let selectedLocation: SavedLocation | null = null;
  let routeResult: RouteResult | null = null;
  let isRouting = false;
  let showRoutePanel = false;
  
  let currentPosition: { lat: number; lng: number } | null = null;
  let isLoadingPosition = false;
  
  let newLocationName = '';
  let newLocationCategory: SavedLocation['category'] = 'custom';
  let showAddModal = false;
  let pendingLocation: { lat: number; lng: number; address: string } | null = null;

  let activeTab: 'saved' | 'recent' | 'search' = 'saved';

  // Initialize
  onMount(async () => {
    // Load saved data from localStorage
    loadFromStorage();
    
    // Dynamically import Leaflet
    const leaflet = await import('leaflet');
    L = leaflet.default;
    
    // Fix Leaflet marker icons
    delete (L.Icon.Default.prototype as any)._getIconUrl;
    L.Icon.Default.mergeOptions({
      iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
      iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
      shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    });
    
    // Initialize map centered on Ho Chi Minh City (default)
    map = L.map(mapContainer).setView([10.8231, 106.6297], 14);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Try to get current location
    getCurrentLocation();
    
    // Render markers
    renderMarkers();
    
    return () => {
      if (map) {
        map.remove();
      }
    };
  });

  function loadFromStorage() {
    const saved = localStorage.getItem('saved_locations');
    if (saved) {
      savedLocations = JSON.parse(saved);
    } else {
      // Default locations
      savedLocations = [
        { id: '1', name: 'Home', lat: 10.8250, lng: 106.6300, address: '123 Home Street', category: 'home', createdAt: new Date().toISOString() },
        { id: '2', name: 'Office', lat: 10.8200, lng: 106.6250, address: '456 Business Ave', category: 'work', createdAt: new Date().toISOString() },
      ];
      saveToStorage();
    }

    const recent = localStorage.getItem('recent_locations');
    if (recent) {
      recentLocations = JSON.parse(recent);
    }
  }

  function saveToStorage() {
    localStorage.setItem('saved_locations', JSON.stringify(savedLocations));
    localStorage.setItem('recent_locations', JSON.stringify(recentLocations));
  }

  function getCurrentLocation() {
    if (!navigator.geolocation) return;
    
    isLoadingPosition = true;
    navigator.geolocation.getCurrentPosition(
      (position) => {
        currentPosition = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
        
        if (map) {
          map.setView([currentPosition.lat, currentPosition.lng], 15);
          addCurrentLocationMarker();
        }
        isLoadingPosition = false;
      },
      (error) => {
        console.log('Geolocation error:', error);
        isLoadingPosition = false;
      }
    );
  }

  function addCurrentLocationMarker() {
    if (!L || !map || !currentPosition) return;
    
    const icon = L.divIcon({
      className: 'current-location-marker',
      html: `<div style="width: 20px; height: 20px; background: #2196F3; border: 3px solid white; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>`,
      iconSize: [20, 20],
      iconAnchor: [10, 10]
    });
    
    L.marker([currentPosition.lat, currentPosition.lng], { icon })
      .addTo(map)
      .bindPopup('📍 Your Location');
  }

  function renderMarkers() {
    if (!L || !map) return;
    
    for (const m of markers) {
      map.removeLayer(m);
    }
    markers = [];
    
    // Add saved location markers
    savedLocations.forEach(loc => {
      const marker = createMarker(loc.lat, loc.lng, loc.name, getCategoryIcon(loc.category));
      markers.push(marker);
    });
    
    // Add recent location markers
    recentLocations.slice(0, 5).forEach(loc => {
      const marker = createMarker(loc.lat, loc.lng, loc.name, '🕐');
      markers.push(marker);
    });
    
    // Add search results markers
    searchResults.forEach(result => {
      const marker = createMarker(result.lat, result.lng, result.name, '🔍');
      markers.push(marker);
    });
  }

  function createMarker(lat: number, lng: number, name: string, icon: string) {
    const marker = L.marker([lat, lng])
      .addTo(map)
      .bindPopup(`<b>${name}</b>`);
    
    marker.on('click', () => {
      const loc = savedLocations.find(l => l.lat === lat && l.lng === lng);
      if (loc) {
        selectedLocation = loc;
      }
    });
    
    return marker;
  }

  function getCategoryIcon(category: string) {
    switch (category) {
      case 'home': return '🏠';
      case 'work': return '🏢';
      case 'favorite': return '⭐';
      default: return '📍';
    }
  }

  async function searchLocation() {
    if (!searchQuery.trim()) return;
    
    isSearching = true;
    searchResults = [];
    
    // Simulate search results (in production, use OpenStreetMap Nominatim API)
    await new Promise(resolve => setTimeout(resolve, 500));
    
    // Mock search results
    const mockResults = [
      { name: `${searchQuery} - Result 1`, lat: 10.8231 + Math.random() * 0.01, lng: 106.6297 + Math.random() * 0.01, address: 'Address 1' },
      { name: `${searchQuery} - Result 2`, lat: 10.8231 + Math.random() * 0.02, lng: 106.6297 + Math.random() * 0.02, address: 'Address 2' },
      { name: `${searchQuery} - Result 3`, lat: 10.8231 + Math.random() * 0.03, lng: 106.6297 + Math.random() * 0.03, address: 'Address 3' },
    ];
    
    searchResults = mockResults;
    renderMarkers();
    isSearching = false;
    activeTab = 'search';
  }

  function selectSearchResult(result: any) {
    if (map) {
      map.setView([result.lat, result.lng], 16);
    }
    selectedLocation = {
      id: Date.now().toString(),
      name: result.name,
      lat: result.lat,
      lng: result.lng,
      address: result.address,
      category: 'custom',
      createdAt: new Date().toISOString()
    };
  }

  function addToSaved(location: typeof selectedLocation) {
    if (!location) return;
    pendingLocation = { lat: location.lat, lng: location.lng, address: location.address || '' };
    newLocationName = location.name;
    newLocationCategory = 'custom';
    showAddModal = true;
  }

  function confirmAddLocation() {
    if (!newLocationName.trim() || !pendingLocation) return;
    
    const newLoc: SavedLocation = {
      id: Date.now().toString(),
      name: newLocationName,
      lat: pendingLocation.lat,
      lng: pendingLocation.lng,
      address: pendingLocation.address,
      category: newLocationCategory,
      createdAt: new Date().toISOString()
    };
    
    savedLocations = [...savedLocations, newLoc];
    saveToStorage();
    renderMarkers();
    
    showAddModal = false;
    pendingLocation = null;
    newLocationName = '';
  }

  function deleteLocation(id: string) {
    savedLocations = savedLocations.filter(l => l.id !== id);
    saveToStorage();
    renderMarkers();
    if (selectedLocation?.id === id) {
      selectedLocation = null;
    }
  }

  function addToRecent(location: SavedLocation) {
    const existing = recentLocations.find(l => l.lat === location.lat && l.lng === location.lng);
    if (existing) {
      // Move to front
      recentLocations = [existing, ...recentLocations.filter(l => l.id !== existing.id)];
    } else {
      const recent: RecentLocation = {
        id: Date.now().toString(),
        name: location.name,
        lat: location.lat,
        lng: location.lng,
        timestamp: new Date().toISOString()
      };
      recentLocations = [recent, ...recentLocations].slice(0, 10);
    }
    saveToStorage();
  }

  function selectLocation(location: SavedLocation) {
    selectedLocation = location;
    if (map) {
      map.setView([location.lat, location.lng], 16);
    }
    addToRecent(location);
  }

  async function getDirections(destination: SavedLocation) {
    if (!currentPosition) {
      alert('Current location not available. Please enable location services.');
      return;
    }
    
    isRouting = true;
    showRoutePanel = true;
    
    // Simulate routing calculation
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Mock route result
    const distance = Math.random() * 10 + 1;
    const duration = Math.round(distance * 3);
    
    routeResult = {
      distance: `${distance.toFixed(1)} km`,
      duration: `${duration} min`,
      steps: [
        { instruction: 'Head north on current street', distance: '500m' },
        { instruction: 'Turn right onto main road', distance: '1.2km' },
        { instruction: 'Continue straight', distance: '2.5km' },
        { instruction: 'Turn left towards destination', distance: '800m' },
        { instruction: 'Arrive at destination', distance: '0m' },
      ]
    };
    
    isRouting = false;
  }

  function shareLocation(location: SavedLocation) {
    const url = `https://maps.google.com/?q=${location.lat},${location.lng}`;
    
    if (navigator.share) {
      navigator.share({
        title: location.name,
        text: `Check out ${location.name}`,
        url: url
      });
    } else {
      // Fallback: copy to clipboard
      navigator.clipboard.writeText(url);
      alert('Location URL copied to clipboard!');
    }
  }

  function openInMaps(location: SavedLocation) {
    const url = `https://www.google.com/maps/dir/?api=1&destination=${location.lat},${location.lng}`;
    window.open(url, '_blank');
  }

  function clearSearch() {
    searchQuery = '';
    searchResults = [];
    activeTab = 'saved';
    renderMarkers();
  }
</script>

<svelte:head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
  <title>📍 Location - Travel App</title>
</svelte:head>

<div class="location-page">
  <header class="header">
    <div class="header-content">
      <h1>📍 Location</h1>
      <button class="locate-btn" onclick={getCurrentLocation} disabled={isLoadingPosition}>
        {isLoadingPosition ? '⏳' : '📍'} My Location
      </button>
    </div>
  </header>

  <div class="main-layout">
    <aside class="sidebar">
      <div class="search-section">
        <div class="search-box">
          <input 
            type="text" 
            placeholder="Search places..." 
            bind:value={searchQuery}
            onkeydown={(e) => e.key === 'Enter' && searchLocation()}
          />
          <button class="search-btn" onclick={searchLocation} disabled={isSearching}>
            {isSearching ? '⏳' : '🔍'}
          </button>
        </div>
      </div>

      <div class="tabs">
        <button class="tab" class:active={activeTab === 'saved'} onclick={() => activeTab = 'saved'}>
          💾 Saved ({savedLocations.length})
        </button>
        <button class="tab" class:active={activeTab === 'recent'} onclick={() => activeTab = 'recent'}>
          🕐 Recent ({recentLocations.length})
        </button>
        <button class="tab" class:active={activeTab === 'search'} onclick={() => activeTab = 'search'}>
          🔍 Search
        </button>
      </div>

      <div class="location-list">
        {#if activeTab === 'saved'}
          {#if savedLocations.length === 0}
            <div class="empty-state">
              <p>No saved locations yet</p>
            </div>
          {:else}
            {#each savedLocations as loc}
              <div 
                class="location-card" 
                class:selected={selectedLocation?.id === loc.id}
                onclick={() => selectLocation(loc)}
              >
                <div class="loc-icon">{getCategoryIcon(loc.category)}</div>
                <div class="loc-info">
                  <h3>{loc.name}</h3>
                  <p>{loc.address || `${loc.lat.toFixed(4)}, ${loc.lng.toFixed(4)}`}</p>
                </div>
                <div class="loc-actions">
                  <button class="action-btn" onclick={(e) => { e.stopPropagation(); openInMaps(loc); }} title="Navigate">
                    🧭
                  </button>
                  <button class="action-btn" onclick={(e) => { e.stopPropagation(); shareLocation(loc); }} title="Share">
                    📤
                  </button>
                  <button class="action-btn delete" onclick={(e) => { e.stopPropagation(); deleteLocation(loc.id); }} title="Delete">
                    🗑️
                  </button>
                </div>
              </div>
            {/each}
          {/if}
        {:else if activeTab === 'recent'}
          {#if recentLocations.length === 0}
            <div class="empty-state">
              <p>No recent locations</p>
            </div>
          {:else}
            {#each recentLocations as loc}
              <div class="location-card" onclick={() => {
                selectedLocation = {
                  id: loc.id,
                  name: loc.name,
                  lat: loc.lat,
                  lng: loc.lng,
                  address: '',
                  category: 'custom',
                  createdAt: loc.timestamp
                };
                if (map) map.setView([loc.lat, loc.lng], 16);
              }}>
                <div class="loc-icon">🕐</div>
                <div class="loc-info">
                  <h3>{loc.name}</h3>
                  <p>{new Date(loc.timestamp).toLocaleDateString()}</p>
                </div>
              </div>
            {/each}
          {/if}
        {:else if activeTab === 'search'}
          {#if searchResults.length === 0}
            <div class="empty-state">
              <p>{searchQuery ? 'No results found' : 'Search for a location'}</p>
            </div>
          {:else}
            {#each searchResults as result}
              <div class="location-card" onclick={() => selectSearchResult(result)}>
                <div class="loc-icon">🔍</div>
                <div class="loc-info">
                  <h3>{result.name}</h3>
                  <p>{result.address}</p>
                </div>
				<button class="add-btn" onclick={(e) => {
					e.stopPropagation();
					selectedLocation = {
						id: Date.now().toString(),
						name: result.name,
						lat: result.lat,
						lng: result.lng,
						address: result.address,
						category: 'custom',
						createdAt: new Date().toISOString()
					};
					addToSaved(selectedLocation);
				}}>+ Save</button>
              </div>
            {/each}
          {/if}
      {/if}
    </aside>

    <div class="map-container">
      <div class="map" bind:this={mapContainer}></div>
      
      <div class="map-controls">
        <button class="map-control" onclick={() => map?.zoomIn()}>+</button>
        <button class="map-control" onclick={() => map?.zoomOut()}>−</button>
      </div>
    </div>

    {#if showRoutePanel}
      <div class="route-panel">
        <div class="route-header">
          <h2>🧭 Directions</h2>
          <button class="close-btn" onclick={() => showRoutePanel = false}>✕</button>
        </div>
        
        {#if isRouting}
          <div class="routing">Calculating route...</div>
        {:else if routeResult}
          <div class="route-summary">
            <div class="route-stat">
              <span class="stat-icon">📏</span>
              <span class="stat-value">{routeResult.distance}</span>
            </div>
            <div class="route-stat">
              <span class="stat-icon">⏱️</span>
              <span class="stat-value">{routeResult.duration}</span>
            </div>
          </div>
          
          <div class="route-steps">
            {#each routeResult.steps as step, i}
              <div class="route-step">
                <div class="step-number">{i + 1}</div>
                <div class="step-content">
                  <p>{step.instruction}</p>
                  <span class="step-distance">{step.distance}</span>
                </div>
              </div>
            {/each}
          </div>
          
          <button class="open-maps-btn" onclick={() => selectedLocation && openInMaps(selectedLocation)}>
            📱 Open in Google Maps
          </button>
        {/if}
      </div>
    {/if}
  </div>

{#if showAddModal}
				<div class="modal-overlay" onclick={() => showAddModal = false}>
					<div class="modal" onclick={(e) => e.stopPropagation()}>
        <h2>💾 Save Location</h2>
        
        <div class="form-group">
          <label>Name</label>
          <input type="text" bind:value={newLocationName} placeholder="Location name" />
        </div>
        
        <div class="form-group">
          <label>Category</label>
          <select bind:value={newLocationCategory}>
            <option value="home">🏠 Home</option>
            <option value="work">🏢 Work</option>
            <option value="favorite">⭐ Favorite</option>
            <option value="custom">📍 Custom</option>
          </select>
        </div>
        
        <div class="modal-actions">
          <button class="cancel-btn" onclick={() => showAddModal = false}>Cancel</button>
          <button class="save-btn" onclick={confirmAddLocation}>Save</button>
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .location-page {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f5f5f5;
  }

  .header {
    background: white;
    padding: 1rem 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    z-index: 100;
  }

  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1600px;
    margin: 0 auto;
  }

  .header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: #1a1a2e;
  }

  .locate-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .locate-btn:hover:not(:disabled) {
    transform: scale(1.02);
  }

  .locate-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .main-layout {
    flex: 1;
    display: flex;
    position: relative;
    overflow: hidden;
  }

  .sidebar {
    width: 380px;
    background: white;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 8px rgba(0,0,0,0.08);
    z-index: 10;
  }

  .search-section {
    padding: 1rem;
    border-bottom: 1px solid #eee;
  }

  .search-box {
    display: flex;
    gap: 0.5rem;
  }

  .search-box input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.95rem;
  }

  .search-box input:focus {
    outline: none;
    border-color: #667eea;
  }

  .search-btn {
    padding: 0.75rem 1rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
  }

  .search-btn:disabled {
    opacity: 0.7;
  }

  .tabs {
    display: flex;
    border-bottom: 1px solid #eee;
  }

  .tab {
    flex: 1;
    padding: 1rem 0.5rem;
    background: none;
    border: none;
    font-size: 0.85rem;
    color: #666;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
  }

  .tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
  }

  .location-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
  }

  .location-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
    gap: 0.75rem;
  }

  .location-card:hover {
    background: #f8f9fa;
  }

  .location-card.selected {
    background: #f0f4ff;
    border: 1px solid #667eea;
  }

  .loc-icon {
    font-size: 1.5rem;
  }

  .loc-info {
    flex: 1;
    min-width: 0;
  }

  .loc-info h3 {
    margin: 0;
    font-size: 0.95rem;
    color: #1a1a2e;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .loc-info p {
    margin: 0.25rem 0 0;
    font-size: 0.8rem;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .loc-actions {
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s;
  }

  .location-card:hover .loc-actions {
    opacity: 1;
  }

  .action-btn {
    padding: 0.4rem;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    border-radius: 4px;
  }

  .action-btn:hover {
    background: #eee;
  }

  .action-btn.delete:hover {
    background: #ffebee;
  }

  .add-btn {
    padding: 0.4rem 0.75rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
  }

  .empty-state {
    text-align: center;
    padding: 2rem;
    color: #999;
  }

  .selected-actions {
    padding: 1rem;
    border-top: 1px solid #eee;
    background: #f8f9fa;
  }

  .selected-actions h3 {
    margin: 0;
    font-size: 1rem;
    color: #1a1a2e;
  }

  .coords {
    margin: 0.25rem 0 0.75rem;
    font-size: 0.8rem;
    color: #666;
    font-family: monospace;
  }

  .action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .primary-btn {
    padding: 0.875rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .secondary-btn {
    padding: 0.75rem;
    background: white;
    color: #1a1a2e;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
  }

  .secondary-btn:hover {
    background: #f5f5f5;
  }

  .map-container {
    flex: 1;
    position: relative;
  }

  .map {
    width: 100%;
    height: 100%;
  }

  .map-controls {
    position: absolute;
    right: 1rem;
    top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    z-index: 1000;
  }

  .map-control {
    width: 36px;
    height: 36px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1.25rem;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  }

  .map-control:hover {
    background: #f5f5f5;
  }

  .route-panel {
    position: absolute;
    bottom: 1rem;
    right: 1rem;
    width: 360px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1000;
    max-height: 70vh;
    overflow-y: auto;
  }

  .route-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #eee;
  }

  .route-header h2 {
    margin: 0;
    font-size: 1.1rem;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    color: #666;
  }

  .routing {
    padding: 2rem;
    text-align: center;
    color: #666;
  }

  .route-summary {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
  }

  .route-stat {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .stat-icon {
    font-size: 1.25rem;
  }

  .stat-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a2e;
  }

  .route-steps {
    padding: 1rem;
  }

  .route-step {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
  }

  .route-step:last-child {
    border-bottom: none;
  }

  .step-number {
    width: 24px;
    height: 24px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    flex-shrink: 0;
  }

  .step-content p {
    margin: 0;
    font-size: 0.9rem;
    color: #1a1a2e;
  }

  .step-distance {
    font-size: 0.8rem;
    color: #666;
  }

  .open-maps-btn {
    display: block;
    width: calc(100% - 2rem);
    margin: 0 1rem 1rem;
    padding: 0.875rem;
    background: #4caf50;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
  }

  .modal {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    width: 90%;
    max-width: 400px;
  }

  .modal h2 {
    margin: 0 0 1.5rem;
    font-size: 1.25rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
  }

  .form-group input,
  .form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
  }

  .modal-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }

  .cancel-btn {
    flex: 1;
    padding: 0.875rem;
    background: #f5f5f5;
    border: none;
    border-radius: 8px;
    cursor: pointer;
  }

  .save-btn {
    flex: 1;
    padding: 0.875rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  @media (max-width: 768px) {
    .main-layout {
      flex-direction: column;
    }

    .sidebar {
      width: 100%;
      max-height: 40vh;
    }

    .route-panel {
      left: 1rem;
      right: 1rem;
      width: auto;
    }
  }

  :global(.current-location-marker) {
    background: transparent !important;
    border: none !important;
  }
</style>
