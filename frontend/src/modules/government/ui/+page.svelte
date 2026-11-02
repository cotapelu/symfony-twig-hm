<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import GovernmentDashboard from './GovernmentDashboard.svelte';
  import GovernmentPage from './GovernmentPage.svelte';
  
  // Determine which view to show based on query param
  let showDashboard = true;
  let initialTab = 'citizens';
  
  onMount(() => {
    // Check URL query params
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    
    if (tab) {
      showDashboard = false;
      initialTab = tab;
    }
  });
  
  // Subscribe to page store for navigation
  $: {
    const tab = $page.url.searchParams.get('tab');
    if (tab) {
      showDashboard = false;
      initialTab = tab;
    } else {
      showDashboard = true;
    }
  }
</script>

{#if showDashboard}
  <GovernmentDashboard />
{:else}
  <GovernmentPage />
{/if}
