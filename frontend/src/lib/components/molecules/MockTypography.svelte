<script lang="ts">
  export let variant = 'body';
  export let children;
  export let className = '';
  export let weight;

  const tag = variant === 'h2' ? 'h2' : variant === 'h3' ? 'h3' : variant === 'body' ? 'p' : variant === 'caption' ? 'small' : 'p';
</script>

<svelte:element this={tag} class={className} style="font-weight: {weight}">
  {@render children()}
</svelte:element>