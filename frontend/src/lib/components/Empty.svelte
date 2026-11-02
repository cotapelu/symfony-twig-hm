<script lang="ts">
  import DOMPurify from 'dompurify';

  export let description = 'No Data';
  export let image = null;
  export let imageStyle = {};
  export let className = '';

  $: classes = [
    'empty',
    className
  ].filter(Boolean).join(' ');
</script>

<div class={classes}>
  {#if image}
    <div class="empty-image" style={Object.entries(imageStyle).map(([k, v]) => `${k}: ${v}`).join('; ')}>
      {@html DOMPurify.sanitize(image)}
    </div>
  {:else}
    <div class="empty-image">
      <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
        <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
          <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7"></ellipse>
          <g fill-rule="nonzero" stroke="#d9d9d9">
            <path d="m55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z"></path>
            <path d="m41.613 15.931c0-1.605.994-2.93 2.227-2.93h1.78c1.232 0 2.227 1.325 2.227 2.93 0 1.605-.995 2.93-2.227 2.93h-1.78c-1.233 0-2.227-1.325-2.227-2.93zM42.867 24.138c-1.404 0-2.54-1.086-2.54-2.426 0-1.34 1.136-2.425 2.54-2.425s2.54 1.085 2.54 2.425c0 1.34-1.136 2.426-2.54 2.426z" fill="#fafafa"></path>
          </g>
        </g>
      </svg>
    </div>
  {/if}

  <div class="empty-description">
    {description}
  </div>

  <div class="empty-footer">
    <slot />
  </div>
</div>

<style>
  .empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    text-align: center;
  }

  .empty-image {
    margin-bottom: 1rem;
    opacity: 0.5;
  }

  .empty-image svg {
    width: 4rem;
    height: 2.5rem;
  }

  .empty-description {
    color: var(--text-secondary, #6b7280);
    font-size: 0.875rem;
    margin-bottom: 1rem;
  }

  .empty-footer {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .empty {
      padding: 1.5rem 0.75rem;
    }

    .empty-image svg {
      width: 3rem;
      height: 2rem;
    }

    .empty-description {
      font-size: 0.75rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .empty-image {
      opacity: 1;
    }

    .empty-image svg path {
      stroke-width: 2px;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .empty-description {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .empty-image svg ellipse {
      fill: var(--gray-700, #374151);
    }

    .empty-image svg path {
      stroke: var(--gray-500, #6b7280);
    }

    .empty-image svg path[fill="#fafafa"] {
      fill: var(--gray-600, #4b5563);
    }
  }
</style>