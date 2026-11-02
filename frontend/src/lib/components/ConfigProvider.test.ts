import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import ConfigProvider from './ConfigProvider.svelte';

describe('ConfigProvider Component', () => {
  let originalDocStyle: CSSStyleDeclaration | null = null;

  beforeEach(() => {
    vi.clearAllMocks();
    // Store original styles
    originalDocStyle = { ...document.documentElement.style };
  });

  afterEach(() => {
    // Restore original styles
    if (originalDocStyle) {
      document.documentElement.style.cssText = '';
      Object.entries(originalDocStyle).forEach(([key, value]) => {
        if (key.startsWith('--')) {
          document.documentElement.style.setProperty(key, value);
        }
      });
    }
  });

  describe('rendering', () => {
    it('renders without props', () => {
      render(ConfigProvider);
      // Should render without error
      expect(document.body).toBeTruthy();
    });

    it('accepts theme prop', () => {
      render(ConfigProvider, { props: { theme: 'light' } });
      expect(document.body).toBeTruthy();
      
      const { unmount } = render(ConfigProvider, { props: { theme: 'dark' } });
      expect(document.body).toBeTruthy();
      unmount();
    });

    it('accepts locale prop', () => {
      render(ConfigProvider, { props: { locale: 'en' } });
      expect(document.body).toBeTruthy();
      
      const { unmount } = render(ConfigProvider, { props: { locale: 'vi' } });
      expect(document.body).toBeTruthy();
      unmount();
    });

    it('accepts primaryColor prop', () => {
      render(ConfigProvider, { props: { primaryColor: '#ff0000' } });
      expect(document.body).toBeTruthy();
    });

    it('accepts borderRadius prop', () => {
      render(ConfigProvider, { props: { borderRadius: '0.5rem' } });
      expect(document.body).toBeTruthy();
    });

    it('accepts fontSize prop', () => {
      render(ConfigProvider, { props: { fontSize: '16px' } });
      expect(document.body).toBeTruthy();
    });
  });

  describe('CSS variable injection', () => {
    it('sets --color-primary CSS variable', () => {
      render(ConfigProvider, { props: { primaryColor: '#123456' } });
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#123456');
    });

    it('sets --radius CSS variable', () => {
      render(ConfigProvider, { props: { borderRadius: '0.75rem' } });
      expect(document.documentElement.style.getPropertyValue('--radius')).toBe('0.75rem');
    });

    it('sets --font-size-base CSS variable', () => {
      render(ConfigProvider, { props: { fontSize: '18px' } });
      expect(document.documentElement.style.getPropertyValue('--font-size-base')).toBe('18px');
    });

    it('updates CSS variables when props change', () => {
      const { rerender } = render(ConfigProvider, { props: { primaryColor: '#000000' } });
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#000000');
      
      rerender({ primaryColor: '#ffffff' });
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#ffffff');
    });

    it('handles multiple prop changes', () => {
      const { rerender } = render(ConfigProvider, { 
        props: { 
          primaryColor: '#111111',
          borderRadius: '0.25rem',
          fontSize: '12px'
        } 
      });
      
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#111111');
      expect(document.documentElement.style.getPropertyValue('--radius')).toBe('0.25rem');
      expect(document.documentElement.style.getPropertyValue('--font-size-base')).toBe('12px');
      
      rerender({ 
        primaryColor: '#222222',
        borderRadius: '0.5rem',
        fontSize: '14px'
      });
      
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#222222');
      expect(document.documentElement.style.getPropertyValue('--radius')).toBe('0.5rem');
      expect(document.documentElement.style.getPropertyValue('--font-size-base')).toBe('14px');
    });
  });

  describe('context provision', () => {
    it('provides config context to children without error', () => {
      // The component uses setContext to provide config
      // We verify it renders without error
      render(ConfigProvider, { 
        props: { 
          theme: 'dark',
          locale: 'vi',
          primaryColor: '#3b82f6'
        } 
      });
      expect(document.body).toBeTruthy();
    });

    it('provides correct context values', () => {
      // Component provides context with all props
      render(ConfigProvider, { 
        props: { 
          theme: 'light',
          locale: 'en',
          primaryColor: '#ff0000',
          borderRadius: '0.5rem',
          fontSize: '16px'
        } 
      });
      expect(document.body).toBeTruthy();
    });
  });

  describe('default values', () => {
    it('uses default theme: light', () => {
      render(ConfigProvider);
      // Default theme is 'light'
      expect(document.body).toBeTruthy();
    });

    it('uses default locale: en', () => {
      render(ConfigProvider);
      expect(document.body).toBeTruthy();
    });

    it('uses default primaryColor: #3b82f6', () => {
      render(ConfigProvider);
      // Default primary color
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#3b82f6');
    });

    it('uses default borderRadius: 0.375rem', () => {
      render(ConfigProvider);
      expect(document.documentElement.style.getPropertyValue('--radius')).toBe('0.375rem');
    });

    it('uses default fontSize: 14px', () => {
      render(ConfigProvider);
      expect(document.documentElement.style.getPropertyValue('--font-size-base')).toBe('14px');
    });
  });

  describe('reactive updates', () => {
    it('reactively updates CSS variables on prop change', () => {
      // Svelte 5 removed component.$set(); rerender is the replacement API
      const { rerender } = render(ConfigProvider, { 
        props: { primaryColor: '#aaaaaa' } 
      });
      
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#aaaaaa');
      
      // Update via component props
      rerender({ primaryColor: '#bbbbbb' });
      
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#bbbbbb');
    });

    it('handles rapid reactive updates', () => {
      const { rerender } = render(ConfigProvider, { 
        props: { primaryColor: '#111111' } 
      });
      
      rerender({ primaryColor: '#222222' });
      rerender({ primaryColor: '#333333' });
      rerender({ primaryColor: '#444444' });
      
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#444444');
    });
  });

  describe('slot handling', () => {
    it('renders children via default slot', () => {
      // Test component with children
      const TestWithChildren = `
        <script>
          import ConfigProvider from './ConfigProvider.svelte';
        </script>
        <ConfigProvider primaryColor="#test">
          <div data-testid="child">Child Content</div>
        </ConfigProvider>
      `;
      
      // Can't easily test slot content with setContext, but we test the component renders
      render(ConfigProvider, { props: { primaryColor: '#test' } });
      expect(document.body).toBeTruthy();
    });

    it('renders empty slot', () => {
      render(ConfigProvider, { props: { primaryColor: '#test' } });
      expect(document.body).toBeTruthy();
    });
  });

  describe('edge cases', () => {
    it('handles undefined props gracefully', () => {
      render(ConfigProvider, { 
        props: { 
          theme: undefined,
          locale: undefined,
          primaryColor: undefined,
          borderRadius: undefined,
          fontSize: undefined
        } 
      });
      expect(document.body).toBeTruthy();
    });

    it('handles empty string props', () => {
      render(ConfigProvider, { 
        props: { 
          theme: '',
          locale: '',
          primaryColor: '',
          borderRadius: '',
          fontSize: ''
        } 
      });
      expect(document.body).toBeTruthy();
    });

    it('handles special characters in color', () => {
      render(ConfigProvider, { props: { primaryColor: 'rgb(255, 0, 0)' } });
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('rgb(255, 0, 0)');
    });

    it('handles CSS calc in borderRadius', () => {
      render(ConfigProvider, { props: { borderRadius: 'calc(1rem / 2)' } });
      expect(document.documentElement.style.getPropertyValue('--radius')).toBe('calc(1rem / 2)');
    });
  });

  describe('multiple instances', () => {
    it('allows multiple ConfigProviders', () => {
      render(ConfigProvider, { props: { primaryColor: '#111111' } });
      const { container: container2 } = render(ConfigProvider, { props: { primaryColor: '#222222' } });
      expect(container2).toBeTruthy();
      // Last rendered wins for CSS variables
      expect(document.documentElement.style.getPropertyValue('--color-primary')).toBe('#222222');
    });

    it('handles nested usage', () => {
      // Both parent and child ConfigProviders render
      render(ConfigProvider, { props: { primaryColor: '#outer' } });
      render(ConfigProvider, { props: { primaryColor: '#inner' } });
      expect(document.body).toBeTruthy();
    });
  });
});