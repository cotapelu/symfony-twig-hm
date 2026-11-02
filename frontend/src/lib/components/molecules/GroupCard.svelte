<script lang="ts">
  interface Group {
    name: string;
    members?: number;
    description?: string;
  }

  export let group: Group | null = null;
  export let onJoin: ((group: Group) => void) | null = null;
  export let onView: ((group: Group) => void) | null = null;
</script>

{#if group}
  <div class="group-card" onclick={() => onView?.(group)} onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onView?.(group); } }} role="button" tabindex="0">
    <div class="group-header">
      <h3>{group.name}</h3>
      <span class="member-count">{group.members || 0} members</span>
    </div>
    {#if group.description}
      <p class="group-description">{group.description}</p>
    {/if}
    <div class="group-actions">
      <button onclick={(e) => { e.stopPropagation(); onJoin?.(group); }} class="join-btn">
        Join Group
      </button>
    </div>
  </div>
{/if}

<style>
  .group-card {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid #ff00ff;
    border-radius: 10px;
    padding: 1rem;
    box-shadow:
      0 0 10px rgba(255, 0, 255, 0.3),
      inset 0 0 10px rgba(255, 0, 255, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .group-card:hover {
    transform: translateY(-5px);
    box-shadow:
      0 0 25px rgba(255, 0, 255, 0.6),
      0 0 50px rgba(0, 255, 255, 0.4);
    border-color: #00ffff;
  }

  .group-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
  }

  .group-header h3 {
    margin: 0;
    color: #00ffff;
    font-size: 1.2rem;
  }

  .member-count {
    color: #ff00ff;
    font-size: 0.9rem;
    background: rgba(255, 0, 255, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid rgba(255, 0, 255, 0.3);
  }

  .group-description {
    margin: 0 0 1rem 0;
    color: #ffffff;
    line-height: 1.4;
  }

  .group-actions {
    display: flex;
    justify-content: flex-end;
  }

  .join-btn {
    background: linear-gradient(45deg, #00ffff, #ff00ff);
    color: #000000;
    border: 1px solid #00ffff;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    transition: all 0.3s ease;
    font-size: 0.9rem;
  }

  .join-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.6);
  }
</style>