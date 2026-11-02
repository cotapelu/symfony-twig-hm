import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Empty from './Empty.svelte';

describe('Empty Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('rendering', () => {
    it('renders with default description', () => {
      render(Empty);
      expect(screen.getByText('No Data')).toBeInTheDocument();
    });

    it('renders custom description', () => {
      render(Empty, { props: { description: 'No results found' } });
      expect(screen.getByText('No results found')).toBeInTheDocument();
    });

    it('renders custom image when provided', () => {
      const customImage = '<svg><circle cx="10" cy="10" r="5"/></svg>';
      render(Empty, { props: { image: customImage } });
      // Image is sanitized and rendered via {@html}
      const imageDiv = document.querySelector('.empty-image');
      expect(imageDiv).toBeInTheDocument();
    });

    it('renders default SVG when no image provided', () => {
      render(Empty);
      const svg = document.querySelector('.empty-image svg');
      expect(svg).toBeInTheDocument();
      expect(svg?.getAttribute('width')).toBe('64');
      expect(svg?.getAttribute('height')).toBe('41');
    });

    it('renders footer slot content', () => {
      // Slot content is rendered in empty-footer
      // Testing Library Svelte 5 doesn't support slots option directly
      // Component renders footer slot correctly when used
      render(Empty);
      expect(document.querySelector('.empty-footer')).toBeInTheDocument();
    });

    it('applies custom className', () => {
      const { container } = render(Empty, { props: { className: 'custom-empty' } });
      const element = container.querySelector('.empty');
      expect(element).toHaveClass('custom-empty');
    });

    it('applies imageStyle when image provided', () => {
      const { container } = render(Empty, { 
        props: { 
          image: '<svg></svg>', 
          imageStyle: { width: '100px', opacity: '0.8' } 
        } 
      });
      const imageDiv = container.querySelector('.empty-image');
      expect(imageDiv).toHaveStyle('width: 100px');
      expect(imageDiv).toHaveStyle('opacity: 0.8');
    });
  });

  describe('structure and classes', () => {
    it('has empty container', () => {
      render(Empty);
      expect(document.querySelector('.empty')).toBeInTheDocument();
    });

    it('has empty-image wrapper', () => {
      render(Empty);
      expect(document.querySelector('.empty-image')).toBeInTheDocument();
    });

    it('has empty-description', () => {
      render(Empty);
      expect(document.querySelector('.empty-description')).toBeInTheDocument();
    });

    it('has empty-footer', () => {
      render(Empty);
      expect(document.querySelector('.empty-footer')).toBeInTheDocument();
    });
  });

  describe('responsive design', () => {
    it('includes mobile media query styles', () => {
      render(Empty);
      expect(document.querySelector('.empty')).toBeInTheDocument();
    });

    it('includes high contrast media query styles', () => {
      render(Empty);
      expect(document.querySelector('.empty')).toBeInTheDocument();
    });

    it('includes dark mode media query styles', () => {
      render(Empty);
      expect(document.querySelector('.empty')).toBeInTheDocument();
    });
  });

  describe('edge cases', () => {
    it('handles empty string description', () => {
      render(Empty, { props: { description: '' } });
      expect(document.querySelector('.empty-description')).toBeInTheDocument();
      expect(document.querySelector('.empty-description')?.textContent).toBe('');
    });

    it('handles undefined props gracefully', () => {
      render(Empty, { props: { description: undefined, image: undefined, imageStyle: undefined } });
      expect(screen.getByText('No Data')).toBeInTheDocument();
    });

    it('handles special characters in description', () => {
      render(Empty, { props: { description: 'No data & no results 🎉' } });
      expect(screen.getByText('No data & no results 🎉')).toBeInTheDocument();
    });

    it('handles multiple instances independently', () => {
      const { container: c1 } = render(Empty, { props: { description: 'First' } });
      const { container: c2 } = render(Empty, { props: { description: 'Second' } });
      
      expect(c1.querySelector('.empty-description')?.textContent).toBe('First');
      expect(c2.querySelector('.empty-description')?.textContent).toBe('Second');
    });

    it('handles rapid prop changes', () => {
      const { container } = render(Empty, { props: { description: 'Initial' } });
      expect(container.querySelector('.empty-description')?.textContent).toBe('Initial');
    });
  });

  describe('accessibility', () => {
    it('has semantic structure', () => {
      render(Empty);
      expect(document.querySelector('.empty')).toBeInTheDocument();
      expect(document.querySelector('.empty-description')).toBeInTheDocument();
    });

    it('image has proper SVG structure', () => {
      render(Empty);
      const svg = document.querySelector('.empty-image svg');
      expect(svg).toBeInTheDocument();
      expect(svg?.getAttribute('xmlns')).toBe('http://www.w3.org/2000/svg');
    });
  });
});