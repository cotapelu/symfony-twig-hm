<script lang="ts">
  export let count = 0;
  export let show = true;
  export let size: 'small' | 'medium' | 'large' = 'small';
</script>

{#if show && count > 0}
  <div class="unread-badge {size}">
    <span class="count">
      {count > 99 ? '99+' : count}
    </span>
  </div>
{/if}

<style>
  .unread-badge {
    background: linear-gradient(45deg, #ff6b6b, #ff8e53);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 11px;
    box-shadow: 0 2px 4px rgba(255, 107, 107, 0.3);
    animation: pulse 2s infinite;
  }

  .small {
    width: 18px;
    height: 18px;
    font-size: 10px;
  }

  .medium {
    width: 24px;
    height: 24px;
    font-size: 12px;
  }

  .large {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }

  .count {
    line-height: 1;
    display: block;
  }

  @keyframes pulse {
    0%, 100% {
      transform: scale(1);
      opacity: 1;
    }
    50% {
      transform: scale(1.1);
      opacity: 0.8;
    }
  }

  /* Dark mode adjustments */
  :global([data-theme="dark"]) .unread-badge {
    box-shadow: 0 2px 4px rgba(255, 107, 107, 0.4);
  }

  /* Reduce motion for accessibility */
  @media (prefers-reduced-motion: reduce) {
    .unread-badge {
      animation: none;
    }
  }
</style>