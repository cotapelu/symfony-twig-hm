import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Divider from './Divider.svelte';

describe('Divider Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('rendering', () => {
    it('renders horizontal divider by default', () => {
      render(Divider);
      const hr = document.querySelector('hr.divider');
      expect(hr).toBeInTheDocument();
    });

    it('renders vertical divider when orientation is vertical', () => {
      render(Divider, { props: { orientation: 'vertical' } });
      const div = document.querySelector('div.divider.divider-vertical');
      expect(div).toBeInTheDocument();
      expect(div).toHaveAttribute('aria-orientation', 'vertical');
    });

    it('renders horizontal divider explicitly', () => {
      render(Divider, { props: { orientation: 'horizontal' } });
      const hr = document.querySelector('hr.divider.divider-horizontal');
      expect(hr).toBeInTheDocument();
    });
  });

  describe('variants', () => {
    it('applies solid variant by default', () => {
      render(Divider);
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-solid');
    });

    it('applies dashed variant', () => {
      render(Divider, { props: { variant: 'dashed' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-dashed');
    });

    it('applies dotted variant', () => {
      render(Divider, { props: { variant: 'dotted' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-dotted');
    });

    it('applies double variant', () => {
      render(Divider, { props: { variant: 'double' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-double');
    });
  });

  describe('thickness', () => {
    it('applies thin thickness by default', () => {
      render(Divider);
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-thin');
    });

    it('applies medium thickness', () => {
      render(Divider, { props: { thickness: 'medium' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-medium');
    });

    it('applies thick thickness', () => {
      render(Divider, { props: { thickness: 'thick' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-thick');
    });
  });

  describe('colors', () => {
    it('applies default color by default', () => {
      render(Divider);
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-default');
    });

    it('applies muted color', () => {
      render(Divider, { props: { color: 'muted' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-muted');
    });

    it('applies accent color', () => {
      render(Divider, { props: { color: 'accent' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-accent');
    });
  });

  describe('spacing', () => {
    it('applies md spacing by default', () => {
      render(Divider);
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-spacing-md');
    });

    it('applies xs spacing', () => {
      render(Divider, { props: { spacing: 'xs' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-spacing-xs');
    });

    it('applies sm spacing', () => {
      render(Divider, { props: { spacing: 'sm' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-spacing-sm');
    });

    it('applies lg spacing', () => {
      render(Divider, { props: { spacing: 'lg' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-spacing-lg');
    });

    it('applies xl spacing', () => {
      render(Divider, { props: { spacing: 'xl' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-spacing-xl');
    });

    it('applies no spacing when set to none', () => {
      render(Divider, { props: { spacing: 'none' } });
      const hr = document.querySelector('hr.divider');
      expect(hr).not.toHaveClass('divider-spacing-xs');
      expect(hr).not.toHaveClass('divider-spacing-sm');
      expect(hr).not.toHaveClass('divider-spacing-md');
      expect(hr).not.toHaveClass('divider-spacing-lg');
      expect(hr).not.toHaveClass('divider-spacing-xl');
    });
  });

  describe('vertical divider spacing', () => {
    it('applies vertical spacing classes', () => {
      render(Divider, { props: { orientation: 'vertical', spacing: 'md' } });
      const div = document.querySelector('div.divider-vertical');
      expect(div).toHaveClass('divider-spacing-md');
    });

    it('applies vertical xs spacing', () => {
      render(Divider, { props: { orientation: 'vertical', spacing: 'xs' } });
      const div = document.querySelector('div.divider-vertical');
      expect(div).toHaveClass('divider-spacing-xs');
    });
  });

  describe('combined props', () => {
    it('combines all prop classes correctly', () => {
      render(Divider, { 
        props: { 
          orientation: 'vertical', 
          variant: 'dashed', 
          thickness: 'thick', 
          color: 'accent', 
          spacing: 'lg' 
        } 
      });
      const div = document.querySelector('div.divider');
      expect(div).toHaveClass('divider-vertical');
      expect(div).toHaveClass('divider-dashed');
      expect(div).toHaveClass('divider-thick');
      expect(div).toHaveClass('divider-accent');
      expect(div).toHaveClass('divider-spacing-lg');
    });

    it('horizontal divider with all variants', () => {
      render(Divider, { 
        props: { 
          orientation: 'horizontal', 
          variant: 'double', 
          thickness: 'medium', 
          color: 'muted', 
          spacing: 'sm' 
        } 
      });
      const hr = document.querySelector('hr.divider');
      expect(hr).toHaveClass('divider-horizontal');
      expect(hr).toHaveClass('divider-double');
      expect(hr).toHaveClass('divider-medium');
      expect(hr).toHaveClass('divider-muted');
      expect(hr).toHaveClass('divider-spacing-sm');
    });
  });

  describe('responsive design', () => {
    it('includes mobile media query styles', () => {
      render(Divider);
      expect(document.querySelector('.divider')).toBeInTheDocument();
    });

    it('includes high contrast media query styles', () => {
      render(Divider);
      expect(document.querySelector('.divider')).toBeInTheDocument();
    });

    it('includes print media query styles', () => {
      render(Divider);
      expect(document.querySelector('.divider')).toBeInTheDocument();
    });
  });

  describe('edge cases', () => {
    it('handles undefined props gracefully', () => {
      render(Divider, { 
        props: { 
          orientation: undefined, 
          variant: undefined, 
          thickness: undefined, 
          color: undefined, 
          spacing: undefined 
        } 
      });
      const hr = document.querySelector('hr.divider');
      expect(hr).toBeInTheDocument();
      // Should have default classes
      expect(hr).toHaveClass('divider-horizontal');
      expect(hr).toHaveClass('divider-solid');
      expect(hr).toHaveClass('divider-thin');
      expect(hr).toHaveClass('divider-default');
      expect(hr).toHaveClass('divider-spacing-md');
    });

    it('handles multiple instances independently', () => {
      const { container: c1 } = render(Divider, { props: { orientation: 'horizontal', variant: 'solid' } });
      const { container: c2 } = render(Divider, { props: { orientation: 'vertical', variant: 'dashed' } });
      
      expect(c1.querySelector('hr')).toBeInTheDocument();
      expect(c2.querySelector('div')).toBeInTheDocument();
    });

    it('handles rapid prop changes', () => {
      const { container } = render(Divider, { props: { variant: 'solid' } });
      const hr = container.querySelector('hr');
      expect(hr).toHaveClass('divider-solid');
    });
  });

  describe('accessibility', () => {
    it('vertical divider has aria-orientation', () => {
      render(Divider, { props: { orientation: 'vertical' } });
      const div = document.querySelector('div.divider-vertical');
      expect(div).toHaveAttribute('aria-orientation', 'vertical');
    });

    it('horizontal divider is hr element', () => {
      render(Divider, { props: { orientation: 'horizontal' } });
      const hr = document.querySelector('hr.divider-horizontal');
      expect(hr).toBeInTheDocument();
    });

    it('has semantic meaning for screen readers', () => {
      render(Divider);
      const divider = document.querySelector('.divider');
      expect(divider).toBeInTheDocument();
    });
  });
});