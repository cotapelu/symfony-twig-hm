<script lang="ts">
  export let width = '100%';
  export let height = '20px';
  export let borderRadius = '4px';
</script>

<div class="skeleton" style="width: {width}; height: {height}; border-radius: {borderRadius};"></div>

<style>
  .skeleton {
    background: linear-gradient(90deg, #333 25%, #555 50%, #333 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
  }

  @keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
  }
</style>