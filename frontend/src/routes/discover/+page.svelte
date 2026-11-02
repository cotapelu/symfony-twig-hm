<script lang="ts">
  import { onMount } from 'svelte';

  interface DiscoveryItem {
    id: number;
    type: string;
    title: string;
    description?: string;
    image_url?: string;
    address?: string;
    score: number;
  }

  let items: DiscoveryItem[] = [];
  let loading = false;
  let activeCategory = 'all';

  const categories = ['all', 'place', 'event', 'offer', 'user'];

  async function loadDiscoveries() {
    loading = true;
    try {
      const url = activeCategory === 'all' 
        ? '/api/discover' 
        : `/api/discover?type=${activeCategory}`;
      const res = await fetch(url);
      const data = await res.json();
      items = data.data || [];
    } catch (e) {
      console.error('Failed to load discoveries');
    }
    loading = false;
  }

  onMount(loadDiscoveries);

  $: if (activeCategory) loadDiscoveries();
</script>

<div class="discover-page">
  <header>
    <h1>🔍 Discover</h1>
    <div class="search-box">
      <input type="text" placeholder="Search places, events, offers..." />
      <button>🔍</button>
    </div>
  </header>

  <div class="category-tabs">
    {#each categories as cat}
      <button 
        class:active={activeCategory === cat}
        on:click={() => activeCategory = cat}
      >
        {cat}
      </button>
    {/each}
  </div>

  {#if loading}
    <div class="loading">Loading...</div>
  {:else}
    <div class="discovery-grid">
      {#each items as item}
        <div class="discovery-card">
          {#if item.image_url}
            <img src={item.image_url} alt={item.title} />
          {:else}
            <div class="placeholder-img">
              {item.type.charAt(0).toUpperCase()}
            </div>
          {/if}
          <div class="content">
            <span class="type-badge">{item.type}</span>
            <h3>{item.title}</h3>
            {#if item.description}
              <p>{item.description}</p>
            {/if}
            {#if item.address}
              <span class="address">📍 {item.address}</span>
            {/if}
            <div class="score">
              <span>★ {item.score}</span>
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<style>
  .discover-page { padding: 20px; max-width: 1200px; margin: 0 auto; }
  header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  h1 { font-size: 1.8rem; }
  .search-box { display: flex; gap: 10px; }
  .search-box input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; width: 300px; }
  .search-box button { background: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; }
  .category-tabs { display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; }
  .category-tabs button {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 20px;
    cursor: pointer;
    text-transform: capitalize;
    white-space: nowrap;
  }
  .category-tabs button.active { background: #007bff; color: white; border-color: #007bff; }
  .discovery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
  .discovery-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .placeholder-img { height: 150px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; }
  .discovery-card img { width: 100%; height: 150px; object-fit: cover; }
  .content { padding: 15px; }
  .type-badge { background: #e9ecef; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; }
  h3 { margin: 10px 0; font-size: 1.1rem; }
  p { color: #666; font-size: 0.9rem; margin: 5px 0; }
  .address { color: #999; font-size: 0.85rem; display: block; margin-top: 5px; }
  .score { margin-top: 10px; color: #ffc107; }
</style>
