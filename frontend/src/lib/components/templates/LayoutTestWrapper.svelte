<script lang="ts">
  import Layout from './Layout.svelte';
  export let variant: 'vertical' | 'horizontal' = 'vertical';
  export let hasSidebar = true;
  export let sidebarWidth = '250px';
  export let collapsible = false;
  export let collapsed = false;
  export let className = '';
</script>

<Layout
  {variant}
  {hasSidebar}
  {sidebarWidth}
  {collapsible}
  {collapsed}
  {className}
>
  <svelte:fragment slot="header">
    <h1>Test Header</h1>
  </svelte:fragment>
  <svelte:fragment slot="sidebar">
    <nav>Sidebar</nav>
  </svelte:fragment>
  <p>Main content</p>
  <svelte:fragment slot="footer">
    <span>Footer content</span>
  </svelte:fragment>
</Layout>
