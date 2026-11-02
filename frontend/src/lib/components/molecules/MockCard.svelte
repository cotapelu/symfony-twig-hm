<script lang="ts">
  export let children;
</script>

<div class="card" data-testid="mock-card">
  {@render children?.()}
</div>

<style>
  .card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
  }
</style>