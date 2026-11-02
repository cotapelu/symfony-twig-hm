<script lang="ts">
  import { searchStore } from '../../state/searchStore';
  import type { SearchFilters } from '../../contracts/searchTypes';

  export let filters: SearchFilters = {};
  export let onChange: ((filters: SearchFilters) => void) | null = null;

  const categories = [
    { id: 'all', label: 'All', icon: '🌐' },
    { id: 'users', label: 'People', icon: '👤' },
    { id: 'messages', label: 'Messages', icon: '💬' },
    { id: 'files', label: 'Files', icon: '📄' },
    { id: 'products', label: 'Products', icon: '📦' },
    { id: 'posts', label: 'Posts', icon: '📝' },
    { id: 'groups', label: 'Groups', icon: '👥' },
    { id: 'events', label: 'Events', icon: '📅' }
  ];

  const dateRanges = [
    { id: 'all', label: 'Any time' },
    { id: 'today', label: 'Today' },
    { id: 'week', label: 'This week' },
    { id: 'month', label: 'This month' },
    { id: 'year', label: 'This year' }
  ];

  const sortOptions = [
    { id: 'relevance', label: 'Relevance' },
    { id: 'date', label: 'Date' },
    { id: 'popularity', label: 'Popularity' }
  ];

  function handleCategoryChange(category: string) {
    const newFilters = { ...filters, category: category === 'all' ? undefined : category };
    onChange?.(newFilters);
  }

  function handleDateChange(dateRange: string) {
    const newFilters = { ...filters, dateRange: dateRange as any };
    onChange?.(newFilters);
  }

  function handleSortChange(sortBy: string) {
    const newFilters = { ...filters, sortBy: sortBy as any };
    onChange?.(newFilters);
  }
</script>

<div class="search-filters">
  <!-- Categories -->
  <div class="mb-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Categories</h3>
    <div class="flex flex-wrap gap-2">
      {#each categories as cat}
        <button
          class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors flex items-center gap-1.5
            {(filters.category || 'all') === cat.id 
              ? 'bg-blue-600 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}"
          onclick={() => handleCategoryChange(cat.id)}
        >
          <span>{cat.icon}</span>
          <span>{cat.label}</span>
        </button>
      {/each}
    </div>
  </div>

  <!-- Date Range -->
  <div class="mb-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Date Range</h3>
    <div class="flex flex-wrap gap-2">
      {#each dateRanges as range}
        <button
          class="px-3 py-1.5 rounded-full text-sm transition-colors
            {(filters.dateRange || 'all') === range.id 
              ? 'bg-blue-100 text-blue-800' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}"
          onclick={() => handleDateChange(range.id)}
        >
          {range.label}
        </button>
      {/each}
    </div>
  </div>

  <!-- Sort -->
  <div class="mb-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Sort By</h3>
    <div class="flex gap-2">
      {#each sortOptions as option}
        <button
          class="px-3 py-1.5 rounded-lg text-sm transition-colors border
            {(filters.sortBy || 'relevance') === option.id 
              ? 'bg-blue-600 text-white border-blue-600' 
              : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'}"
          onclick={() => handleSortChange(option.id)}
        >
          {option.label}
        </button>
      {/each}
    </div>
  </div>

  <!-- Active Filters -->
  {#if Object.keys(filters).some(k => filters[k as keyof SearchFilters])}
    <div class="flex items-center justify-between pt-4 border-t">
      <span class="text-sm text-gray-500">
        {Object.values(filters).filter(Boolean).length} filter(s) active
      </span>
      <button 
        class="text-sm text-red-600 hover:text-red-800"
        onclick={() => onChange?.({})}
      >
        Clear all
      </button>
    </div>
  {/if}
</div>