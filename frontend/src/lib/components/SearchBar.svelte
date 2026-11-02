<script lang="ts">
  import Icon from './atoms/Icon.svelte';
  import AutoComplete from './AutoComplete.svelte';

  export let value = '';
  export let options: Array<{ value: string; label: string }> = [];
  export let placeholder = 'Search...';
  export let loading = false;
  export let disabled = false;
  export let size: 'sm' | 'md' | 'lg' = 'md';
  export let variant: 'default' | 'filled' | 'outlined' = 'default';
  export let className = '';

  import { createEventDispatcher } from 'svelte';
  const dispatch = createEventDispatcher();

  $: classes = [
    'search-bar',
    `search-bar-${size}`,
    `search-bar-${variant}`,
    disabled ? 'search-bar-disabled' : '',
    loading ? 'search-bar-loading' : '',
    className
  ].filter(Boolean).join(' ');

  function handleInput(event) {
    dispatch('input', event.detail);
  }

  function handleSelect(event) {
    dispatch('select', event.detail);
  }

  function handleSearch(event) {
    if (event.key === 'Enter') {
      dispatch('search', { value });
    }
  }
</script>

<div class={classes}>
  <Icon name="search" />
  <AutoComplete
    {value}
    options={options}
    {placeholder}
    {loading}
    {disabled}
    {size}
    {variant}
    on:select={handleSelect}
    {...$$restProps}
  />
</div>

<style>
  .search-bar {
    position: relative;
    width: 100%;
  }

  .search-bar .icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1;
    color: var(--text-secondary, #6b7280);
    pointer-events: none;
  }

  .search-bar :global(.autocomplete) {
    padding-left: 2.5rem;
  }

  .search-bar-sm :global(.autocomplete) {
    padding-left: 2.25rem;
  }

  .search-bar-lg :global(.autocomplete) {
    padding-left: 3rem;
  }

  .search-bar-disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .search-bar-loading {
    /* Additional styles if needed */
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .search-bar-icon {
      color: var(--text-secondary-dark, #9ca3af);
    }
  }

  /* Mobile */
  @media (max-width: 640px) {
    .search-bar :global(.autocomplete) {
      padding-left: 2.25rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .search-bar-icon {
      color: currentColor;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    /* No animations */
  }
</style>