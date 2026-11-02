<script lang="ts">
  import { searchStore, search, setSearchQuery } from '../../state/searchStore';
  import { onMount } from 'svelte';
  import type { SearchSuggestion } from '../../contracts/searchTypes';

  export let placeholder = 'Search...';
  export let compact = false;
  export let onSearch: ((query: string) => void) | null = null;
  
  let query = '';
  let showSuggestions = false;
  let searchBarRef: HTMLDivElement;
  let recentSearches: string[] = [];

  onMount(() => {
    const saved = localStorage.getItem('recentSearches');
    if (saved) {
      try {
        recentSearches = JSON.parse(saved);
      } catch { /* ignore */ }
    }

    const handleClickOutside = (event: MouseEvent) => {
      if (searchBarRef && !searchBarRef.contains(event.target as Node)) {
        showSuggestions = false;
      }
    };
    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });

  function handleInput() {
    setSearchQuery(query);
    if (query.trim().length >= 2) {
      searchStore.getSuggestions(query);
      showSuggestions = true;
    } else {
      showSuggestions = false;
    }
  }

  function handleSubmit() {
    if (query.trim()) {
      search(query);
      addToRecentSearches(query);
      showSuggestions = false;
      onSearch?.(query);
    }
  }

  function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
      handleSubmit();
    } else if (e.key === 'Escape') {
      showSuggestions = false;
    }
  }

  function addToRecentSearches(term: string) {
    recentSearches = [term, ...recentSearches.filter(s => s !== term)].slice(0, 5);
    localStorage.setItem('recentSearches', JSON.stringify(recentSearches));
  }

  function selectSuggestion(suggestion: SearchSuggestion | string) {
    const text = typeof suggestion === 'string' ? suggestion : suggestion.text;
    query = text;
    handleSubmit();
  }

  function clearRecentSearches() {
    recentSearches = [];
    localStorage.removeItem('recentSearches');
  }

  $: suggestions = $searchStore.suggestions;
</script>

<div class="relative" bind:this={searchBarRef} class:w-full={!compact}>
  <div class="relative">
    <input
      type="text"
      bind:value={query}
      oninput={handleInput}
      onkeydown={handleKeyDown}
      onfocus={() => showSuggestions = true}
      {placeholder}
      class="w-full bg-gray-100 border-0 rounded-full px-4 py-2 pl-10 pr-10 focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all {compact ? 'text-sm py-1.5' : ''}"
    />
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>
    {#if query}
      <button 
        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
        onclick={() => { query = ''; setSearchQuery(''); }}
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    {/if}
  </div>

  <!-- Suggestions Dropdown -->
  {#if showSuggestions}
    <div class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border z-50 overflow-hidden">
      {#if query.trim().length >= 2 && suggestions.length > 0}
        <div class="p-2">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide px-3 py-2">Suggestions</p>
          {#each suggestions as suggestion}
            <button 
              class="w-full px-3 py-2 text-left hover:bg-gray-50 rounded-lg flex items-center gap-3"
              onclick={() => selectSuggestion(suggestion)}
            >
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <span class="text-sm">{suggestion.text}</span>
            </button>
          {/each}
        </div>
      {/if}

      {#if recentSearches.length > 0}
        <div class="p-2 border-t">
          <div class="flex items-center justify-between px-3 py-2">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Recent</p>
            <button 
              class="text-xs text-red-600 hover:text-red-800"
              onclick={clearRecentSearches}
            >
              Clear
            </button>
          </div>
          {#each recentSearches as term}
            <button 
              class="w-full px-3 py-2 text-left hover:bg-gray-50 rounded-lg flex items-center gap-3"
              onclick={() => selectSuggestion(term)}
            >
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-sm">{term}</span>
            </button>
          {/each}
        </div>
      {/if}

      {#if !query.trim() && recentSearches.length === 0}
        <div class="p-4 text-center text-gray-500">
          <p class="text-sm">Start typing to search</p>
        </div>
      {/if}

      <div class="p-2 border-t bg-gray-50">
        <a href="/search" class="text-xs text-blue-600 hover:text-blue-800 text-center block py-1">
          Advanced Search
        </a>
      </div>
    </div>
  {/if}
</div>