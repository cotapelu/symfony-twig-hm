<script lang="ts">
  export let color = '';
  export let closable = false;
  export let visible = true;
  export let checkable = false;
  export let checked = true;
  export let size = 'default'; // small, default
  export let className = '';

  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  function handleClose() {
    visible = false;
    dispatch('close');
  }

  function handleCheck() {
    checked = !checked;
    dispatch('change', { checked });
  }

  $: classes = [
    'tag',
    `tag-${size}`,
    color ? `tag-${color}` : '',
    !visible ? 'tag-hidden' : '',
    checkable && !checked ? 'tag-unchecked' : '',
    className
  ].filter(Boolean).join(' ');
</script>

{#if visible}
  <span class={classes} onclick={checkable ? handleCheck : undefined}>
    <span class="tag-content">
      <slot />
    </span>

    {#if closable}
      <!-- svelte-ignore a11y_click_events_have_key_events -->
      <!-- svelte-ignore a11y_no_static_element_interactions -->
      <span class="tag-close" onclick={(e) => { e.stopPropagation(); handleClose(); }}>
        ×
      </span>
    {/if}
  </span>
{/if}

<style>
  .tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.25;
    border-radius: 4px;
    background: var(--gray-100, #f3f4f6);
    color: var(--text-primary, #111827);
    border: 1px solid var(--gray-200, #e5e7eb);
    white-space: nowrap;
    transition: all 0.2s ease;
  }

  .tag:hover {
    background: var(--gray-200, #e5e7eb);
    border-color: var(--gray-300, #d1d5db);
  }

  .tag-hidden {
    display: none;
  }

  .tag-unchecked {
    opacity: 0.5;
    background: var(--gray-50, #f9fafb);
  }

  .tag-content {
    flex: 1;
  }

  .tag-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    cursor: pointer;
    color: var(--text-secondary, #6b7280);
    transition: all 0.2s ease;
    font-size: 0.875rem;
    line-height: 1;
  }

  .tag-close:hover {
    background: var(--gray-300, #d1d5db);
    color: var(--text-primary, #111827);
  }

  /* Colors */
  .tag-magenta {
    background: #f9f0ff;
    border-color: #d3adf7;
    color: #722ed1;
  }

  .tag-magenta:hover {
    background: #f6ffed;
    border-color: #b7eb8f;
  }

  .tag-red {
    background: #fff2f0;
    border-color: #ffccc7;
    color: #cf1322;
  }

  .tag-red:hover {
    background: #fff1f0;
    border-color: #ff7875;
  }

  .tag-volcano {
    background: #fff2e8;
    border-color: #ffd591;
    color: #d4380d;
  }

  .tag-volcano:hover {
    background: #fff7e6;
    border-color: #ffc069;
  }

  .tag-orange {
    background: #fff7e6;
    border-color: #ffd591;
    color: #d46b08;
  }

  .tag-orange:hover {
    background: #fff7e6;
    border-color: #ffc069;
  }

  .tag-gold {
    background: #fffbe6;
    border-color: #ffe58f;
    color: #d48806;
  }

  .tag-gold:hover {
    background: #fffbe6;
    border-color: #ffd666;
  }

  .tag-lime {
    background: #fcffe6;
    border-color: #eaff8f;
    color: #7cb305;
  }

  .tag-lime:hover {
    background: #f6ffed;
    border-color: #b7eb8f;
  }

  .tag-green {
    background: #f6ffed;
    border-color: #b7eb8f;
    color: #389e0d;
  }

  .tag-green:hover {
    background: #f6ffed;
    border-color: #95de64;
  }

  .tag-cyan {
    background: #e6fffb;
    border-color: #87e8de;
    color: #08979c;
  }

  .tag-cyan:hover {
    background: #e6fffb;
    border-color: #5cdbd3;
  }

  .tag-blue {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
  }

  .tag-blue:hover {
    background: #dbeafe;
    border-color: #93c5fd;
  }

  .tag-geekblue {
    background: #f0f5ff;
    border-color: #adc6ff;
    color: #1e40af;
  }

  .tag-geekblue:hover {
    background: #e0f2fe;
    border-color: #7dd3fc;
  }

  .tag-purple {
    background: #f9f0ff;
    border-color: #d3adf7;
    color: #531dab;
  }

  .tag-purple:hover {
    background: #f3e8ff;
    border-color: #c4b5fd;
  }

  /* Sizes */
  .tag-small {
    padding: 0 0.375rem;
    font-size: 0.6875rem;
  }

  .tag-small .tag-close {
    width: 0.75rem;
    height: 0.75rem;
    font-size: 0.75rem;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .tag {
      font-size: 0.6875rem;
      padding: 0.125rem 0.375rem;
    }

    .tag-small {
      font-size: 0.625rem;
      padding: 0 0.25rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .tag {
      border-width: 2px;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .tag {
      background: var(--gray-700, #374151);
      border-color: var(--gray-600, #4b5563);
      color: var(--text-primary-dark, #f9fafb);
    }

    .tag:hover {
      background: var(--gray-600, #4b5563);
      border-color: var(--gray-500, #6b7280);
    }

    .tag-unchecked {
      opacity: 0.3;
      background: var(--gray-800, #1f2937);
    }

    .tag-close {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .tag-close:hover {
      background: var(--gray-600, #4b5563);
      color: var(--text-primary-dark, #f9fafb);
    }

    /* Colored tags in dark mode - simplified */
    .tag-magenta {
      background: #2a0845;
      border-color: #722ed1;
      color: #d3adf7;
    }

    .tag-red {
      background: #2a0608;
      border-color: #cf1322;
      color: #ffccc7;
    }

    .tag-volcano {
      background: #2b1416;
      border-color: #d4380d;
      color: #ffd591;
    }

    .tag-orange {
      background: #2b1d11;
      border-color: #d46b08;
      color: #ffd591;
    }

    .tag-gold {
      background: #2b2111;
      border-color: #d48806;
      color: #ffe58f;
    }

    .tag-lime {
      background: #1f2611;
      border-color: #7cb305;
      color: #eaff8f;
    }

    .tag-green {
      background: #162312;
      border-color: #389e0d;
      color: #b7eb8f;
    }

    .tag-cyan {
      background: #112123;
      border-color: #08979c;
      color: #87e8de;
    }

    .tag-blue {
      background: #111827;
      border-color: #1d4ed8;
      color: #bfdbfe;
    }

    .tag-geekblue {
      background: #131649;
      border-color: #1e40af;
      color: #adc6ff;
    }

    .tag-purple {
      background: #1a1325;
      border-color: #531dab;
      color: #d3adf7;
    }
  }
</style>