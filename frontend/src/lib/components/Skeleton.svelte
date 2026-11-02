<script lang="ts">
  export let width = '100%';
  export let height = '1rem';
  export let borderRadius = '4px';
  export let className = '';
  export let animated = true;
</script>

<div
  class="skeleton {className}"
  class:animated
  style="width: {width}; height: {height}; border-radius: {borderRadius};"
></div>

<style>
  .skeleton {
    background: var(--bg-tertiary, #f1f5f9);
    position: relative;
    overflow: hidden;
  }

  .skeleton.animated::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
      90deg,
      transparent,
      rgba(255, 255, 255, 0.4),
      transparent
    );
    animation: shimmer 1.5s infinite;
  }

  @keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
  }

  /* Dark theme adjustments */
  :global([data-theme="dark"]) .skeleton {
    background: var(--bg-tertiary);
  }

  :global([data-theme="dark"]) .skeleton.animated::before {
    background: linear-gradient(
      90deg,
      transparent,
      rgba(255, 255, 255, 0.1),
      transparent
    );
  }

  /* Cyberpunk theme */
  :global([data-theme="cyberpunk"]) .skeleton {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid rgba(0, 255, 255, 0.3);
  }

  :global([data-theme="cyberpunk"]) .skeleton.animated::before {
    background: linear-gradient(
      90deg,
      transparent,
      rgba(0, 255, 255, 0.6),
      transparent
    );
  }

  /* Reduced motion support */
  @media (prefers-reduced-motion: reduce) {
    .skeleton.animated::before {
      animation: none;
    }
  }
</style>