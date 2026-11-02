<script lang="ts">
  import { createEventDispatcher, onMount, onDestroy } from 'svelte';

  interface Props {
    data?: Record<string, any>;
    storageKey?: string;
    debounceMs?: number;
    saveFunction?: (data: Record<string, any>) => Promise<void> | void;
    loadFunction?: () => Record<string, any> | Promise<Record<string, any>>;
    // Callback props for Svelte 5
    onSaved?: (data: Record<string, any>) => void;
    onLoaded?: (data: Record<string, any>) => void;
  }

  const {
    data: initialData = {},
    storageKey = 'autosave',
    debounceMs = 1000,
    saveFunction = (data: Record<string, any>) => {
      localStorage.setItem(storageKey, JSON.stringify(data));
    },
    loadFunction = () => {
      const saved = localStorage.getItem(storageKey);
      return saved ? JSON.parse(saved) : {};
    },
    onSaved,
    onLoaded
  } = $props<Props>();

  const dispatch = createEventDispatcher<{ saved: Record<string, any>; loaded: Record<string, any> }>();

  // Local state initialized from prop
  let data = $state(initialData);
  let debounceTimer: ReturnType<typeof setTimeout> | null = null;
  let isLoading = $state(true);

  // Sync prop changes to local state
  $effect(() => {
    data = initialData;
  });

  // Load initial data
  onMount(async () => {
    try {
      const loadedData = await loadFunction();
      data = loadedData;
      dispatch('loaded', loadedData);
      onLoaded?.(loadedData);
    } catch (error) {
      console.error('Failed to load autosave data:', error);
    } finally {
      isLoading = false;
    }
  });

  // Auto save on data change - using $effect instead of $:
  $effect(() => {
    if (!isLoading) {
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(async () => {
        try {
          await saveFunction(data);
          dispatch('saved', data);
          onSaved?.(data);
        } catch (error) {
          console.error('Failed to autosave:', error);
        }
      }, debounceMs);
    }
  });

  // Manual save
  function save() {
    if (debounceTimer) clearTimeout(debounceTimer);
    saveFunction(data);
    dispatch('saved', data);
    onSaved?.(data);
  }

  // Clear autosave
  function clear() {
    localStorage.removeItem(storageKey);
    data = {};
  }

  onDestroy(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
  });

  // Expose methods for slot
  let exposed = { isLoading, save, clear };
</script>

<slot {isLoading} {save} {clear} />