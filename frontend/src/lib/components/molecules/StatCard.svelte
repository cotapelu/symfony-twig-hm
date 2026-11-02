<script lang="ts">
  import Card from './Card.svelte';
  import Typography from '../atoms/Typography.svelte';
  import Icon from '../atoms/Icon.svelte';

  export let title = '';
  export let value: string | number = '';
  export let icon = '';
  export let color = '#007bff';
</script>

<div class="stat-card-wrapper" style="--card-color: {color}">
  <Card>
    <div class="stat-icon">
      <Icon name={icon} />
    </div>
    <div class="stat-content">
      <Typography variant="caption">{title}</Typography>
      <Typography variant="h3">{value}</Typography>
    </div>
  </Card>
</div>

<style>
  .stat-card-wrapper {
    --card-bg: var(--bg-card);
    --card-text: var(--text-primary);
  }

  .stat-card-wrapper :global(.card) {
    background: var(--card-bg, #ffffff);
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid var(--border-primary, #e9ecef);
    text-align: center;
    box-shadow: var(--shadow-sm, 0 2px 4px rgba(0,0,0,0.1));
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .stat-card-wrapper :global(.card:hover) {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md, 0 4px 8px rgba(0,0,0,0.15));
    border-color: var(--card-color);
  }

  .stat-icon {
    font-size: 2rem;
    opacity: 0.8;
    color: var(--card-color);
  }

  .stat-content {
    text-align: left;
  }

  /* Cyberpunk theme special effects */
  :global([data-theme="cyberpunk"]) .stat-card-wrapper :global(.card) {
    border-color: var(--border-accent);
    box-shadow: var(--shadow-glow);
  }

  :global([data-theme="cyberpunk"]) .stat-card-wrapper :global(.card:hover) {
    box-shadow: 0 0 20px var(--card-color);
    border-color: var(--accent-cyber);
  }

  :global([data-theme="cyberpunk"]) .stat-icon {
    text-shadow: 0 0 10px var(--card-color);
  }
</style>