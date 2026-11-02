import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import Avatar from './Avatar.svelte';

describe('Avatar Component', () => {
  describe('rendering', () => {
    it('renders fallback text when no src and no alt', () => {
      render(Avatar);
      expect(screen.getByText('?')).toBeTruthy();
    });

    it('renders fallback using alt initial when alt provided and no fallback', () => {
      render(Avatar, { props: { alt: 'John Doe' } });
      expect(screen.getByText('J')).toBeTruthy();
    });

    it('renders custom fallback text when provided', () => {
      render(Avatar, { props: { fallback: 'JD' } });
      expect(screen.getByText('JD')).toBeTruthy();
    });

    it('renders image when src provided', () => {
      render(Avatar, { props: { src: 'https://example.com/avatar.jpg', alt: 'User' } });
      const img = document.querySelector('img');
      expect(img).toBeTruthy();
      expect(img?.getAttribute('src')).toBe('https://example.com/avatar.jpg');
      expect(img?.getAttribute('alt')).toBe('User');
    });
  });

  describe('sizes', () => {
    it('applies correct size class for each size variant', () => {
      const sizes: Array<'xs' | 'sm' | 'md' | 'lg' | 'xl'> = ['xs', 'sm', 'md', 'lg', 'xl'];
      sizes.forEach((size) => {
        const { unmount } = render(Avatar, { props: { size } });
        const wrapper = document.querySelector('.avatar');
        expect(wrapper).toHaveClass(`avatar-${size}`);
        unmount();
      });
    });

    it('defaults to md size', () => {
      render(Avatar);
      const wrapper = document.querySelector('.avatar');
      expect(wrapper).toHaveClass('avatar-md');
    });
  });

  describe('shapes', () => {
    it('applies shape class for circle (default)', () => {
      render(Avatar);
      const wrapper = document.querySelector('.avatar');
      expect(wrapper).toHaveClass('avatar-circle');
    });

    it('applies shape class for square', () => {
      render(Avatar, { props: { shape: 'square' } });
      const wrapper = document.querySelector('.avatar');
      expect(wrapper).toHaveClass('avatar-square');
    });

    it('applies shape class for rounded', () => {
      render(Avatar, { props: { shape: 'rounded' } });
      const wrapper = document.querySelector('.avatar');
      expect(wrapper).toHaveClass('avatar-rounded');
    });
  });

  describe('status indicator', () => {
    it('shows status indicator when showStatus is true', () => {
      render(Avatar, { props: { showStatus: true } });
      const status = document.querySelector('.avatar-status');
      expect(status).toBeTruthy();
    });

    it('does not show status when showStatus false', () => {
      render(Avatar);
      expect(document.querySelector('.avatar-state')).toBeNull();
    });

    it('applies correct status class for online', () => {
      render(Avatar, { props: { showStatus: true, status: 'online' } });
      const status = document.querySelector('.avatar-status');
      expect(status).toHaveClass('avatar-status-online');
    });

    it('applies correct status class for offline', () => {
      render(Avatar, { props: { showStatus: true, status: 'offline' } });
      const status = document.querySelector('.avatar-status');
      expect(status).toHaveClass('avatar-status-offline');
    });

    it('applies correct status class for busy', () => {
      render(Avatar, { props: { showStatus: true, status: 'busy' } });
      const status = document.querySelector('.avatar-status');
      expect(status).toHaveClass('avatar-status-busy');
    });

    it('applies correct status class for away', () => {
      render(Avatar, { props: { showStatus: true, status: 'away' } });
      const status = document.querySelector('.avatar-status');
      expect(status).toHaveClass('avatar-status-away');
    });

    it('status has proper aria-label', () => {
      render(Avatar, { props: { showStatus: true, status: 'busy' } });
      const status = document.querySelector('.avatar-status');
      expect(status).toHaveAttribute('aria-label', 'Status: busy');
    });
  });

  describe('group mode', () => {
    it('renders wrapper with avatar-group-wrapper when group is true', () => {
      render(Avatar, { props: { group: true } });
      const wrapper = document.querySelector('.avatar-group-wrapper');
      expect(wrapper).toBeTruthy();
    });

    it('does not render wrapper when group is false', () => {
      render(Avatar);
      expect(document.querySelector('.avatar-group-wrapper')).toBeNull();
    });
  });

  describe('image error handling', () => {
    it('shows fallback when image fails to load', async () => {
      const { component } = render(Avatar, { props: { src: 'invalid-url', alt: 'Test' } });
      const img = document.querySelector('img');
      // Simulate error event
      await img?.dispatchEvent(new Event('error'));
      // Fallback should appear
      expect(screen.getByText('T')).toBeTruthy(); // first letter of alt
    });
  });

  describe('accessibility', () => {
    it('fallback div has aria-label based on alt', () => {
      render(Avatar, { props: { alt: 'John Doe' } });
      const fallback = document.querySelector('.avatar-fallback');
      expect(fallback).toHaveAttribute('aria-label', 'John Doe');
    });

    it('fallback div uses default aria-label when alt missing', () => {
      render(Avatar);
      const fallback = document.querySelector('.avatar-fallback');
      expect(fallback).toHaveAttribute('aria-label', 'Avatar');
    });

    it('image has alt attribute', () => {
      render(Avatar, { props: { src: 'test.jpg', alt: 'Desc' } });
      const img = document.querySelector('img');
      expect(img).toHaveAttribute('alt', 'Desc');
    });

    it('image has loading="lazy"', () => {
      render(Avatar, { props: { src: 'test.jpg' } });
      const img = document.querySelector('img');
      expect(img).toHaveAttribute('loading', 'lazy');
    });
  });

  describe('class composition', () => {
    it('combines base classes without duplicates', () => {
      render(Avatar, { props: { size: 'lg', shape: 'rounded' } });
      const wrapper = document.querySelector('.avatar');
      const classes = wrapper?.className.split(' ').filter(Boolean);
      expect(classes).toContain('avatar-lg');
      expect(classes).toContain('avatar-rounded');
      expect(classes).toContain('avatar');
    });

    it('adds avatar-error class when image error occurs', async () => {
      render(Avatar, { props: { src: 'bad-url' } });
      const img = document.querySelector('img');
      await img?.dispatchEvent(new Event('error'));
      const wrapper = document.querySelector('.avatar');
      expect(wrapper).toHaveClass('avatar-error');
    });
  });
});
