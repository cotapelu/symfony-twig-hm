<script lang="ts">
  import type { SearchResult } from '../../contracts/searchTypes';

  export let result: SearchResult;
  export let highlighted = false;

  function getResultIcon(type: string) {
    switch (type) {
      case 'user': return '👤';
      case 'message': return '💬';
      case 'file': return '📄';
      case 'product': return '📦';
      case 'post': return '📝';
      case 'group': return '👥';
      case 'event': return '📅';
      case 'place': return '📍';
      default: return '🔍';
    }
  }

  function getTypeColor(type: string) {
    switch (type) {
      case 'user': return 'bg-blue-100 text-blue-800';
      case 'message': return 'bg-green-100 text-green-800';
      case 'file': return 'bg-yellow-100 text-yellow-800';
      case 'product': return 'bg-purple-100 text-purple-800';
      case 'post': return 'bg-pink-100 text-pink-800';
      case 'group': return 'bg-indigo-100 text-indigo-800';
      case 'event': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  }
</script>

<a 
  href={result.url || '#'} 
  class="block p-4 hover:bg-gray-50 transition-colors rounded-lg {highlighted ? 'bg-blue-50 border border-blue-200' : ''}"
>
  <div class="flex items-start gap-4">
    <!-- Image or Icon -->
    <div class="flex-shrink-0 w-14 h-14 rounded-xl overflow-hidden {result.image ? '' : 'bg-gray-100 flex items-center justify-center text-2xl shadow-sm'}"
    >
      {#if result.image}
        <img src={result.image} alt={result.title} class="w-full h-full object-cover" />
      {:else}
        {getResultIcon(result.type)}
      {/if}
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 flex-wrap">
        <h3 class="font-semibold text-gray-900 truncate">{result.title}</h3>
        <span class="text-xs px-2 py-0.5 rounded-full capitalize {getTypeColor(result.type)}">
          {result.type}
        </span>
        {#if result.relevanceScore}
          <span class="text-xs text-gray-400">{Math.round(result.relevanceScore * 100)}% match</span>
        {/if}
      </div>
      
      <p class="text-gray-600 mt-1 text-sm line-clamp-2">{result.description}</p>
      
      <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
        {#if result.author}
          <span class="flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            {result.author}
          </span>
        {/if}
        {#if result.date}
          <span class="flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            {new Date(result.date).toLocaleDateString()}
          </span>
        {/if}
        {#if result.category}
          <span class="text-blue-600">#{result.category}</span>
        {/if}
      </div>
    </div>

    <!-- Arrow -->
    <div class="flex-shrink-0 text-gray-300">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </div>
  </div>
</a>

<style>
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>