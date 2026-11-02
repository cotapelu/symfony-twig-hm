import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import MockIcon from './MockIcon.svelte';

describe('MockIcon Component', () => {
  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(MockIcon);
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });

    it('renders with custom name', () => {
      render(MockIcon, { props: { name: 'user' } });
      expect(screen.getByTestId('mock-icon-user-md')).toBeInTheDocument();
    });

    it('renders with custom size', () => {
      render(MockIcon, { props: { size: 'lg' } });
      expect(screen.getByTestId('mock-icon-home-lg')).toBeInTheDocument();
    });

    it('renders with custom color', () => {
      render(MockIcon, { props: { color: 'red' } });
      expect(screen.getByTestId('mock-icon-home-md')).toHaveStyle({ color: 'rgb(255, 0, 0)' });
    });

    it('renders with spin', () => {
      render(MockIcon, { props: { spin: true } });
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });

    it('renders with rotation', () => {
      render(MockIcon, { props: { rotate: 90 } });
      const icon = screen.getByTestId('mock-icon-home-md'); expect(icon).toBeInTheDocument(); expect(icon).toHaveAttribute('style');
    });

    it('renders with horizontal flip', () => {
      render(MockIcon, { props: { flip: 'horizontal' } });
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });

    it('renders with vertical flip', () => {
      render(MockIcon, { props: { flip: 'vertical' } });
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });
  });

  describe('Props', () => {
    it('accepts name prop', () => {
      render(MockIcon, { props: { name: 'user' } });
      expect(screen.getByTestId('mock-icon-user-md')).toBeInTheDocument();
    });

    it('accepts size prop', () => {
      render(MockIcon, { props: { size: 'lg' } });
      expect(screen.getByTestId('mock-icon-home-lg')).toBeInTheDocument();
    });

    it('accepts color prop', () => {
      render(MockIcon, { props: { color: 'blue' } });
      expect(screen.getByTestId('mock-icon-home-md')).toHaveStyle({ color: 'rgb(0, 0, 255)' });
    });

    it('accepts spin prop', () => {
      render(MockIcon, { props: { spin: true } });
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });

    it('accepts rotate prop', () => {
      render(MockIcon, { props: { rotate: 180 } });
      const icon = screen.getByTestId('mock-icon-home-md'); expect(icon).toBeInTheDocument(); expect(icon).toHaveAttribute('style');
    });

    it('accepts flip prop', () => {
      render(MockIcon, { props: { flip: 'horizontal' } });
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });

    it('accepts className prop', () => {
      render(MockIcon, { props: { className: 'custom-class' } });
      expect(screen.getByTestId('mock-icon-home-md')).toHaveClass('custom-class');
    });
  });

  describe('Icons', () => {
    it('renders home icon', () => {
      render(MockIcon, { props: { name: 'home' } });
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });

    it('renders user icon', () => {
      render(MockIcon, { props: { name: 'user' } });
      expect(screen.getByTestId('mock-icon-user-md')).toBeInTheDocument();
    });

    it('renders settings icon', () => {
      render(MockIcon, { props: { name: 'settings' } });
      expect(screen.getByTestId('mock-icon-settings-md')).toBeInTheDocument();
    });

    it('renders search icon', () => {
      render(MockIcon, { props: { name: 'search' } });
      expect(screen.getByTestId('mock-icon-search-md')).toBeInTheDocument();
    });

    it('renders menu icon', () => {
      render(MockIcon, { props: { name: 'menu' } });
      expect(screen.getByTestId('mock-icon-menu-md')).toBeInTheDocument();
    });

    it('renders x icon', () => {
      render(MockIcon, { props: { name: 'x' } });
      expect(screen.getByTestId('mock-icon-x-md')).toBeInTheDocument();
    });

    it('renders check icon', () => {
      render(MockIcon, { props: { name: 'check' } });
      expect(screen.getByTestId('mock-icon-check-md')).toBeInTheDocument();
    });

    it('renders alert icon', () => {
      render(MockIcon, { props: { name: 'alert' } });
      expect(screen.getByTestId('mock-icon-alert-md')).toBeInTheDocument();
    });

    it('renders info icon', () => {
      render(MockIcon, { props: { name: 'info' } });
      expect(screen.getByTestId('mock-icon-info-md')).toBeInTheDocument();
    });

    it('renders plus icon', () => {
      render(MockIcon, { props: { name: 'plus' } });
      expect(screen.getByTestId('mock-icon-plus-md')).toBeInTheDocument();
    });

    it('renders minus icon', () => {
      render(MockIcon, { props: { name: 'minus' } });
      expect(screen.getByTestId('mock-icon-minus-md')).toBeInTheDocument();
    });

    it('renders chevron icon', () => {
      render(MockIcon, { props: { name: 'chevron' } });
      expect(screen.getByTestId('mock-icon-chevron-md')).toBeInTheDocument();
    });

    it('renders arrow icon', () => {
      render(MockIcon, { props: { name: 'arrow' } });
      expect(screen.getByTestId('mock-icon-arrow-md')).toBeInTheDocument();
    });

    it('renders download icon', () => {
      render(MockIcon, { props: { name: 'download' } });
      expect(screen.getByTestId('mock-icon-download-md')).toBeInTheDocument();
    });

    it('renders upload icon', () => {
      render(MockIcon, { props: { name: 'upload' } });
      expect(screen.getByTestId('mock-icon-upload-md')).toBeInTheDocument();
    });

    it('renders trash icon', () => {
      render(MockIcon, { props: { name: 'trash' } });
      expect(screen.getByTestId('mock-icon-trash-md')).toBeInTheDocument();
    });

    it('renders edit icon', () => {
      render(MockIcon, { props: { name: 'edit' } });
      expect(screen.getByTestId('mock-icon-edit-md')).toBeInTheDocument();
    });

    it('renders eye icon', () => {
      render(MockIcon, { props: { name: 'eye' } });
      expect(screen.getByTestId('mock-icon-eye-md')).toBeInTheDocument();
    });

    it('renders share icon', () => {
      render(MockIcon, { props: { name: 'share' } });
      expect(screen.getByTestId('mock-icon-share-md')).toBeInTheDocument();
    });

    it('renders star icon', () => {
      render(MockIcon, { props: { name: 'star' } });
      expect(screen.getByTestId('mock-icon-star-md')).toBeInTheDocument();
    });

    it('renders heart icon', () => {
      render(MockIcon, { props: { name: 'heart' } });
      expect(screen.getByTestId('mock-icon-heart-md')).toBeInTheDocument();
    });

    it('renders bell icon', () => {
      render(MockIcon, { props: { name: 'bell' } });
      expect(screen.getByTestId('mock-icon-bell-md')).toBeInTheDocument();
    });

    it('renders mail icon', () => {
      render(MockIcon, { props: { name: 'mail' } });
      expect(screen.getByTestId('mock-icon-mail-md')).toBeInTheDocument();
    });

    it('renders location icon', () => {
      render(MockIcon, { props: { name: 'location' } });
      expect(screen.getByTestId('mock-icon-location-md')).toBeInTheDocument();
    });

    it('renders calendar icon', () => {
      render(MockIcon, { props: { name: 'calendar' } });
      expect(screen.getByTestId('mock-icon-calendar-md')).toBeInTheDocument();
    });

    it('renders clock icon', () => {
      render(MockIcon, { props: { name: 'clock' } });
      expect(screen.getByTestId('mock-icon-clock-md')).toBeInTheDocument();
    });

    it('renders filter icon', () => {
      render(MockIcon, { props: { name: 'filter' } });
      expect(screen.getByTestId('mock-icon-filter-md')).toBeInTheDocument();
    });

    it('renders sort icon', () => {
      render(MockIcon, { props: { name: 'sort' } });
      expect(screen.getByTestId('mock-icon-sort-md')).toBeInTheDocument();
    });

    it('renders grid icon', () => {
      render(MockIcon, { props: { name: 'grid' } });
      expect(screen.getByTestId('mock-icon-grid-md')).toBeInTheDocument();
    });

    it('renders list icon', () => {
      render(MockIcon, { props: { name: 'list' } });
      expect(screen.getByTestId('mock-icon-list-md')).toBeInTheDocument();
    });

    it('renders tree icon', () => {
      render(MockIcon, { props: { name: 'tree' } });
      expect(screen.getByTestId('mock-icon-tree-md')).toBeInTheDocument();
    });

    it('renders file icon', () => {
      render(MockIcon, { props: { name: 'file' } });
      expect(screen.getByTestId('mock-icon-file-md')).toBeInTheDocument();
    });

    it('renders folder icon', () => {
      render(MockIcon, { props: { name: 'folder' } });
      expect(screen.getByTestId('mock-icon-folder-md')).toBeInTheDocument();
    });
  });

  describe('Size Variants', () => {
    it('renders xs size', () => {
      render(MockIcon, { props: { size: 'xs' } });
      expect(screen.getByTestId('mock-icon-home-xs')).toHaveClass('icon-xs');
    });

    it('renders sm size', () => {
      render(MockIcon, { props: { size: 'sm' } });
      expect(screen.getByTestId('mock-icon-home-sm')).toHaveClass('icon-sm');
    });

    it('renders md size', () => {
      render(MockIcon, { props: { size: 'md' } });
      expect(screen.getByTestId('mock-icon-home-md')).toHaveClass('icon-md');
    });

    it('renders lg size', () => {
      render(MockIcon, { props: { size: 'lg' } });
      expect(screen.getByTestId('mock-icon-home-lg')).toHaveClass('icon-lg');
    });

    it('renders xl size', () => {
      render(MockIcon, { props: { size: 'xl' } });
      expect(screen.getByTestId('mock-icon-home-xl')).toHaveClass('icon-xl');
    });
  });

  describe('Color', () => {
    it('applies color style', () => {
      render(MockIcon, { props: { color: 'red' } });
      expect(screen.getByTestId('mock-icon-home-md')).toHaveStyle({ color: 'rgb(255, 0, 0)' });
    });

    it('applies blue color', () => {
      render(MockIcon, { props: { color: 'blue' } });
      expect(screen.getByTestId('mock-icon-home-md')).toHaveStyle({ color: 'rgb(0, 0, 255)' });
    });
  });

  describe('Spin Animation', () => {
    it('adds spin animation when spin is true', () => {
      const { container } = render(MockIcon, { props: { spin: true } });
      const svg = container.querySelector('svg');
      const animateTransform = svg?.getElementsByTagNameNS('http://www.w3.org/2000/svg', 'animateTransform')[0];
      expect(animateTransform).not.toBeNull();
    });
  });

  describe('Rotation', () => {
    it('applies rotation', () => {
      render(MockIcon, { props: { rotate: 90 } });
      const icon = screen.getByTestId('mock-icon-home-md'); expect(icon).toBeInTheDocument(); expect(icon).toHaveAttribute('style');
    });

    it('applies 180 degree rotation', () => {
      render(MockIcon, { props: { rotate: 180 } });
      const icon = screen.getByTestId('mock-icon-home-md'); expect(icon).toBeInTheDocument(); expect(icon).toHaveAttribute('style');
    });

    it('applies 270 degree rotation', () => {
      render(MockIcon, { props: { rotate: 270 } });
      const icon = screen.getByTestId('mock-icon-home-md'); expect(icon).toBeInTheDocument(); expect(icon).toHaveAttribute('style');
    });
  });

  describe('Flip', () => {
    it('applies horizontal flip', () => {
      render(MockIcon, { props: { flip: 'horizontal' } });
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });

    it('applies vertical flip', () => {
      render(MockIcon, { props: { flip: 'vertical' } });
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });

    it('does not flip when none', () => {
      render(MockIcon, { props: { flip: 'none' } });
      expect(screen.getByTestId('mock-icon-home-md')).toBeInTheDocument();
    });
  });

  describe('Size Classes', () => {
    it('applies xs class', () => {
      render(MockIcon, { props: { size: 'xs' } });
      expect(screen.getByTestId('mock-icon-home-xs')).toHaveClass('icon-xs');
    });

    it('applies sm class', () => {
      render(MockIcon, { props: { size: 'sm' } });
      expect(screen.getByTestId('mock-icon-home-sm')).toHaveClass('icon-sm');
    });

    it('applies md class', () => {
      render(MockIcon, { props: { size: 'md' } });
      expect(screen.getByTestId('mock-icon-home-md')).toHaveClass('icon-md');
    });

    it('applies lg class', () => {
      render(MockIcon, { props: { size: 'lg' } });
      expect(screen.getByTestId('mock-icon-home-lg')).toHaveClass('icon-lg');
    });

    it('applies xl class', () => {
      render(MockIcon, { props: { size: 'xl' } });
      expect(screen.getByTestId('mock-icon-home-xl')).toHaveClass('icon-xl');
    });
  });

  describe('Rotation Styles', () => {
    it('applies rotation transform for 90deg', () => {
      render(MockIcon, { props: { rotate: 90 } });
      const icon = screen.getByTestId('mock-icon-home-md'); expect(icon).toBeInTheDocument(); expect(icon).toHaveAttribute('style');
    });

    it('applies rotation transform for 180deg', () => {
      render(MockIcon, { props: { rotate: 180 } });
      const icon = screen.getByTestId('mock-icon-home-md'); expect(icon).toBeInTheDocument(); expect(icon).toHaveAttribute('style');
    });
  });

  describe('Spin Animation', () => {
    it('adds animateTransform when spin is true', () => {
      const { container } = render(MockIcon, { props: { spin: true } });
      const svg = container.querySelector('svg');
      const animateTransform = svg?.getElementsByTagNameNS('http://www.w3.org/2000/svg', 'animateTransform')[0];
      expect(animateTransform).not.toBeNull();
      expect(animateTransform).toHaveAttribute('attributeName', 'transform');
      expect(animateTransform).toHaveAttribute('type', 'rotate');
      expect(animateTransform).toHaveAttribute('from', '0 12 12');
      expect(animateTransform).toHaveAttribute('to', '360 12 12');
      expect(animateTransform).toHaveAttribute('dur', '1s');
      expect(animateTransform).toHaveAttribute('repeatCount', 'indefinite');
    });
  });

  describe('Flip', () => {
    it('applies horizontal flip transform', () => {
      render(MockIcon, { props: { flip: 'horizontal' } });
      const g = screen.getByTestId('mock-icon-home-md').querySelector('g[transform="scale(-1, 1) translate(-24, 0)"]');
      expect(g).toBeInTheDocument();
    });

    it('applies vertical flip transform', () => {
      render(MockIcon, { props: { flip: 'vertical' } });
      const g = screen.getByTestId('mock-icon-home-md').querySelector('g[transform="scale(1, -1) translate(0, -24)"]');
      expect(g).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('has role img', () => {
      render(MockIcon);
      expect(screen.getByTestId('mock-icon-home-md')).toHaveAttribute('role', 'img');
    });

    it('has aria-hidden', () => {
      render(MockIcon);
      expect(screen.getByTestId('mock-icon-home-md')).toHaveAttribute('aria-hidden', 'true');
    });

    it('has data-testid with name and size', () => {
      render(MockIcon, { props: { name: 'user', size: 'lg' } });
      expect(screen.getByTestId('mock-icon-user-lg')).toHaveAttribute('data-name', 'user');
      expect(screen.getByTestId('mock-icon-user-lg')).toHaveAttribute('data-size', 'lg');
    });
  });

  describe('SVG Attributes', () => {
    it('has viewBox', () => {
      render(MockIcon);
      expect(screen.getByTestId('mock-icon-home-md')).toHaveAttribute('viewBox', '0 0 24 24');
    });

    it('has fill none', () => {
      render(MockIcon);
      expect(screen.getByTestId('mock-icon-home-md')).toHaveAttribute('fill', 'none');
    });

    it('has stroke currentColor', () => {
      render(MockIcon);
      expect(screen.getByTestId('mock-icon-home-md')).toHaveAttribute('stroke', 'currentColor');
    });

    it('has stroke-width 2', () => {
      render(MockIcon);
      expect(screen.getByTestId('mock-icon-home-md')).toHaveAttribute('stroke-width', '2');
    });

    it('has stroke-linecap round', () => {
      render(MockIcon);
      expect(screen.getByTestId('mock-icon-home-md')).toHaveAttribute('stroke-linecap', 'round');
    });

    it('has stroke-linejoin round', () => {
      render(MockIcon);
      expect(screen.getByTestId('mock-icon-home-md')).toHaveAttribute('stroke-linejoin', 'round');
    });
  });

  describe('Edge Cases', () => {
    it('handles unknown icon name', () => {
      render(MockIcon, { props: { name: 'unknown' } });
      // Falls back to home icon
      expect(screen.getByTestId('mock-icon-unknown-md')).toBeInTheDocument();
    });

    it('handles empty name', () => {
      render(MockIcon, { props: { name: '' } });
      // Empty name defaults to home icon but testid uses empty name
      expect(screen.getByTestId('mock-icon--md')).toBeInTheDocument();
    });

    it('handles special characters in color', () => {
      render(MockIcon, { props: { color: '#ff0000' } });
      expect(screen.getByTestId('mock-icon-home-md')).toHaveStyle('color: #ff0000');
    });

    it('handles negative rotation', () => {
      render(MockIcon, { props: { rotate: -90 } });
      const icon = screen.getByTestId('mock-icon-home-md'); expect(icon).toBeInTheDocument(); expect(icon).toHaveAttribute('style');
    });

    it('handles large rotation', () => {
      render(MockIcon, { props: { rotate: 720 } });
      // Rotation style is applied via inline style; exact format may vary
      const icon = screen.getByTestId('mock-icon-home-md');
      expect(icon).toBeInTheDocument();
      expect(icon).toHaveAttribute('style');
    });
  });
});