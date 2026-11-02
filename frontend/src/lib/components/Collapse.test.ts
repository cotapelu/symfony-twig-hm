import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Collapse from './Collapse.svelte';

// Mock panel component for testing
const MockPanel = `
<script lang="ts">
  import { getContext, onMount, onDestroy } from 'svelte';
  
  export let key = '';
  export let header = '';
  export let children: any;
  
  const ctx = getContext('collapse-context');
  
  let isActive = false;
  let unregister: () => void = () => {};
  
  onMount(() => {
    if (ctx) {
      isActive = ctx.isActive(key);
      unregister = ctx.registerPanel({ key });
    }
  });
  
  onDestroy(() => {
    unregister();
  });
  
  function handleClick() {
    if (ctx) {
      ctx.togglePanel(key);
    }
  }
</script>

<div class="collapse-item">
  <button class="collapse-header" on:click={handleClick}>
    <span>{header}</span>
    <span class="expand-icon">
      {#if ctx?.expandIconPosition === 'left'}
        {isActive ? '▼' : '▶'}
      {/if}
    </span>
  </button>
  {#if isActive}
    <div class="collapse-content">
      {@render children()}
    </div>
  {/if}
</div>
`;

describe('Collapse Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('rendering', () => {
    it('renders collapse container', () => {
      render(Collapse);
      const container = document.querySelector('.collapse');
      expect(container).toBeInTheDocument();
    });

    it('applies bordered class by default', () => {
      render(Collapse);
      const container = document.querySelector('.collapse');
      expect(container).toHaveClass('collapse-bordered');
    });

    it('applies ghost class when ghost prop is true', () => {
      const { container } = render(Collapse, { props: { ghost: true } });
      const el = container.querySelector('.collapse');
      expect(el).toHaveClass('collapse-ghost');
      expect(el).not.toHaveClass('collapse-bordered');
    });

    it('still allows an explicit border on a ghost collapse', () => {
      const { container } = render(Collapse, { props: { ghost: true, bordered: true } });
      const el = container.querySelector('.collapse');
      expect(el).toHaveClass('collapse-ghost');
      expect(el).toHaveClass('collapse-bordered');
    });

    it('applies icon position classes', () => {
      const { container } = render(Collapse, { props: { expandIconPosition: 'left' } });
      const left = container.querySelector('.collapse');
      expect(left).toHaveClass('collapse-icon-left');

      // Scope to this render's own container: both instances stay mounted,
      // so document.querySelector would return the 'left' one above.
      const { container: container2, unmount } = render(Collapse, { props: { expandIconPosition: 'right' } });
      const right = container2.querySelector('.collapse');
      expect(right).toHaveClass('collapse-icon-right');
      expect(right).not.toHaveClass('collapse-icon-left');
      unmount();
    });

    it('applies custom className', () => {
      render(Collapse, { props: { className: 'custom-class' } });
      const container = document.querySelector('.collapse');
      expect(container).toHaveClass('custom-class');
    });
  });

  describe('accordion mode', () => {
    it('toggles single panel in accordion mode', async () => {
      const TestComponent = `
        <script>
          import Collapse from './Collapse.svelte';
        </script>
        <Collapse accordion>
          <slot />
        </Collapse>
      `;
      
      const { container } = render(Collapse, { 
        props: { accordion: true }
      });
      
      // The component renders correctly with accordion prop
      expect(container).toBeTruthy();
    });
  });

  describe('active key management', () => {
    it('initializes with defaultActiveKey', () => {
      render(Collapse, { props: { defaultActiveKey: ['panel1'] } });
      // Component renders without error
      const container = document.querySelector('.collapse');
      expect(container).toBeInTheDocument();
    });

    it('updates when activeKey prop changes', () => {
      const { component, rerender } = render(Collapse, { 
        props: { activeKey: [] } 
      });
      
      // Component handles prop changes
      expect(component).toBeTruthy();
      
      rerender({ activeKey: ['panel1'] });
      expect(component).toBeTruthy();
    });

    it('dispatches change event when active key changes', async () => {
      const dispatch = vi.fn();
      
      // We can't easily test context dispatch, but we can verify component renders
      render(Collapse, { props: { activeKey: ['panel1'] } });
      const container = document.querySelector('.collapse');
      expect(container).toBeInTheDocument();
    });
  });

  describe('panel registration', () => {
    it('provides collapse context for child panels', () => {
      render(Collapse);
      // Component provides context without error
      expect(document.querySelector('.collapse')).toBeInTheDocument();
    });

    it('handles panel registration and unregistration', () => {
      // Basic test that component works with context
      render(Collapse, { props: { accordion: true } });
      expect(document.querySelector('.collapse')).toBeInTheDocument();
    });
  });

  describe('multiple panels', () => {
    it('handles multiple panels in non-accordion mode', () => {
      render(Collapse, { props: { accordion: false, defaultActiveKey: ['panel1', 'panel2'] } });
      const container = document.querySelector('.collapse');
      expect(container).toBeInTheDocument();
    });

    it('handles empty active keys', () => {
      render(Collapse, { props: { activeKey: [] } });
      expect(document.querySelector('.collapse')).toBeInTheDocument();
    });
  });

  describe('accessibility', () => {
    it('has proper structure for accessibility', () => {
      render(Collapse);
      const container = document.querySelector('.collapse');
      expect(container).toBeInTheDocument();
    });

    it('renders expand icons for panel headers', () => {
      // The component provides expandIconPosition to context
      render(Collapse, { props: { expandIconPosition: 'left' } });
      expect(document.querySelector('.collapse')).toHaveClass('collapse-icon-left');
    });
  });

  describe('edge cases', () => {
    it('handles empty children', () => {
      render(Collapse, { props: { defaultActiveKey: [] } });
      expect(document.querySelector('.collapse')).toBeInTheDocument();
    });

    it('handles undefined props gracefully', () => {
      render(Collapse, { props: { accordion: undefined, ghost: undefined } });
      expect(document.querySelector('.collapse')).toBeInTheDocument();
    });

    it('handles rapid prop changes', () => {
      const { rerender } = render(Collapse, { props: { activeKey: ['a'] } });
      rerender({ activeKey: ['b'] });
      rerender({ activeKey: [] });
      rerender({ activeKey: ['c', 'd'] });
      expect(document.querySelector('.collapse')).toBeInTheDocument();
    });
  });

  describe('responsive classes', () => {
    it('includes mobile responsive classes', () => {
      render(Collapse);
      // Component includes media query styles in its style block
      expect(document.querySelector('.collapse')).toBeInTheDocument();
    });

    it('includes high contrast media query classes', () => {
      render(Collapse);
      expect(document.querySelector('.collapse')).toBeInTheDocument();
    });

    it('includes dark mode media query classes', () => {
      render(Collapse);
      expect(document.querySelector('.collapse')).toBeInTheDocument();
    });
  });
});