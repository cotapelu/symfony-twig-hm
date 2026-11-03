<script lang="ts">
  import { searchStore, search, setSearchQuery, clearSearchResults } from '../state/searchStore';
  import { onMount } from 'svelte';
  import DOMPurify from 'dompurify';
  import type { SearchResult } from '../contracts/searchTypes';

  let query = '';
  let selectedCategory = 'all';
  let recentSearches: string[] = [];
  let showFilters = false;

  let filters = {
    dateRange: 'all',
    sortBy: 'relevance',
    type: 'all'
  };

  onMount(() => {
    // Load recent searches from localStorage
    const saved = localStorage.getItem('recentSearches');
    if (saved) {
      recentSearches = JSON.parse(saved);
    }
  });

  $: results = $searchStore.results;
  $: loading = $searchStore.loading;
  $: suggestions = $searchStore.suggestions;

  function handleSearch() {
    if (query.trim()) {
      search(query, { category: selectedCategory, ...filters } as unknown as Parameters<typeof search>[1]);
      addToRecentSearches(query);
    }
  }

  function addToRecentSearches(term: string) {
    recentSearches = [term, ...recentSearches.filter(s => s !== term)].slice(0, 10);
    localStorage.setItem('recentSearches', JSON.stringify(recentSearches));
  }

  function clearRecentSearches() {
    recentSearches = [];
    localStorage.removeItem('recentSearches');
  }

  function removeRecentSearch(term: string) {
    recentSearches = recentSearches.filter(s => s !== term);
    localStorage.setItem('recentSearches', JSON.stringify(recentSearches));
  }

  function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
      handleSearch();
    }
  }

  function getResultIcon(type: string) {
    switch (type) {
      case 'user': return '👤';
      case 'message': return '💬';
      case 'file': return '📄';
      case 'product': return '📦';
      case 'post': return '📝';
      case 'group': return '👥';
      default: return '🔍';
    }
  }
</script>

<div class="search-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-2xl font-bold mb-6">Search</h1>

      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex gap-4">
          <div class="flex-1 relative">
            <input
              type="text"
              bind:value={query}
              onkeydown={handleKeyDown}
              placeholder="Search for anything..."
              class="w-full px-4 py-3 pl-12 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
            {#if query}
              <button 
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                onclick={() => { query = ''; clearSearchResults(); }}
              >
                ✕
              </button>
            {/if}
          </div>
          <select 
            bind:value={selectedCategory}
            class="px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">All</option>
            <option value="users">People</option>
            <option value="messages">Messages</option>
            <option value="files">Files</option>
            <option value="products">Products</option>
            <option value="posts">Posts</option>
            <option value="groups">Groups</option>
          </select>
          <button 
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
            onclick={handleSearch}
          >
            Search
          </button>
          <button 
            class="px-4 py-3 border rounded-lg hover:bg-gray-50 {showFilters ? 'bg-gray-100' : ''}"
            onclick={() => showFilters = !showFilters}
          >
            ⚙️
          </button>
        </div>

        {#if showFilters}
          <div class="mt-4 pt-4 border-t grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
              <select bind:value={filters.dateRange} class="w-full px-3 py-2 border rounded-lg">
                <option value="all">Any time</option>
                <option value="today">Today</option>
                <option value="week">This week</option>
                <option value="month">This month</option>
                <option value="year">This year</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
              <select bind:value={filters.sortBy} class="w-full px-3 py-2 border rounded-lg">
                <option value="relevance">Relevance</option>
                <option value="date">Date</option>
                <option value="popularity">Popularity</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
              <select bind:value={filters.type} class="w-full px-3 py-2 border rounded-lg">
                <option value="all">All types</option>
                <option value="image">Images</option>
                <option value="video">Videos</option>
                <option value="document">Documents</option>
              </select>
            </div>
          </div>
        {/if}
      </div>

      {#if query && suggestions.length > 0 && !loading}
        <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
          {#each suggestions as suggestion}
            <button 
              class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3"
              onclick={() => { query = suggestion.text; handleSearch(); }}
            >
              <span class="text-gray-400">🔍</span>
              <span>{@html DOMPurify.sanitize(suggestion.text.replace(new RegExp(query, 'gi'), match => `<strong>${match}</strong>`), {ALLOWED_TAGS: ['strong']})}</span>
            </button>
          {/each}
        </div>
      {/if}

      {#if loading}
        <div class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      {:else if results.length > 0}
        <div class="space-y-4">
          <div class="flex items-center justify-between text-sm text-gray-600">
            <span>{results.length} results found</span>
            <button class="text-blue-600 hover:underline" onclick={clearSearchResults}>Clear results</button>
          </div>
          
          <div class="bg-white rounded-lg shadow divide-y">
            {#each results as result}
              <div class="p-4 hover:bg-gray-50 flex items-start gap-4">
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-2xl flex-shrink-0">
                  {#if result.image}
                    <img src={result.image} alt={result.title} class="w-full h-full rounded-lg object-cover" />
                  {:else}
                    {getResultIcon(result.type)}
                  {/if}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-gray-900">{result.title}</h3>
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{result.type}</span>
                  </div>
                  <p class="text-gray-600 mt-1 line-clamp-2">{result.description}</p>
                  <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                    {#if result.author}
                      <span>By {result.author}</span>
                    {/if}
                    {#if result.date}
                      <span>{new Date(result.date).toLocaleDateString()}</span>
                    {/if}
                    {#if result.category}
                      <span class="text-blue-600">#{result.category}</span>
                    {/if}
                  </div>
                </div>
                {#if result.url}
                  <a href={result.url} class="text-blue-600 hover:text-blue-800 p-2">
                    →
                  </a>
                {/if}
              </div>
            {/each}
          </div>
        </div>
      {:else if query && !loading}
        <div class="text-center py-12 text-gray-500">
          <div class="text-6xl mb-4">🔍</div>
          <p class="text-lg">No results found for "{query}"</p>
          <p class="text-sm mt-2">Try different keywords or check your spelling</p>
        </div>
      {:else}
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold">Recent Searches</h3>
            {#if recentSearches.length > 0}
              <button 
                class="text-sm text-red-600 hover:text-red-800"
                onclick={clearRecentSearches}
              >
                Clear all
              </button>
            {/if}
          </div>
          
          {#if recentSearches.length === 0}
            <p class="text-gray-500 text-center py-4">No recent searches</p>
          {:else}
            <div class="flex flex-wrap gap-2">
              {#each recentSearches as term}
                <div class="flex items-center gap-1 bg-gray-100 rounded-full px-3 py-1">
                  <button 
                    class="text-gray-700 hover:text-blue-600"
                    onclick={() => { query = term; handleSearch(); }}
                  >
                    {term}
                  </button>
                  <button 
                    class="text-gray-400 hover:text-gray-600 ml-1"
                    onclick={() => removeRecentSearch(term)}
                  >
                    ✕
                  </button>
                </div>
              {/each}
            </div>
          {/if}

          <div class="mt-8">
            <h3 class="font-semibold mb-4">Popular Categories</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              {#each ['People', 'Messages', 'Files', 'Products', 'Posts', 'Groups', 'Events', 'Places'] as category}
                <button 
                  class="p-4 bg-gray-50 rounded-lg hover:bg-gray-100 text-center"
                  onclick={() => { selectedCategory = category.toLowerCase(); }}
                >
                  <span class="text-2xl block mb-2">
                    {category === 'People' ? '👤' : 
                     category === 'Messages' ? '💬' : 
                     category === 'Files' ? '📄' : 
                     category === 'Products' ? '📦' : 
                     category === 'Posts' ? '📝' : 
                     category === 'Groups' ? '👥' : 
                     category === 'Events' ? '📅' : '📍'}
                  </span>
                  <span class="text-sm font-medium">{category}</span>
                </button>
              {/each}
            </div>
          </div>
        </div>
      {/if}
    </div>
  </div>
</div>

<style>
  .search-page {
    min-height: 100vh;
    background: #f9fafb;
  }
  
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>
