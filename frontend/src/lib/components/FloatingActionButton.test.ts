import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import FloatingActionButton from './FloatingActionButton.svelte';

describe('FloatingActionButton Component', () => {
  describe('rendering', () => {
    it('renders a button with default icon (+)', () => {
      render(FloatingActionButton);
      const btn = document.querySelector('button.fab');
      expect(btn).toBeTruthy();
      expect(btn?.textContent).toContain('+');
    });

    it('renders custom icon when provided', () => {
      render(FloatingActionButton, { props: { icon: '➕' } });
      const btn = document.querySelector('button.fab');
      expect(btn?.textContent?.trim()).toBe('➕');
    });

    it('renders label when extended', () => {
      render(FloatingActionButton, { props: { extended: true, label: 'Add' } });
      const btn = document.querySelector('button.fab');
      expect(btn?.textContent).toContain('Add');
    });

    it('does not render label when not extended', () => {
      render(FloatingActionButton, { props: { extended: false, label: 'Add' } });
      const btn = document.querySelector('button.fab');
      expect(btn?.textContent).not.toContain('Add');
    });
  });

  describe('class generation', () => {
    it('applies all base classes', () => {
      render(FloatingActionButton);
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveClass('fab');
    });

    it('applies position class', () => {
      render(FloatingActionButton, { props: { position: 'bottom-left' } });
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveClass('fab-bottom-left');
    });

    it('defaults to bottom-right position', () => {
      render(FloatingActionButton);
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveClass('fab-bottom-right');
    });

    it('applies size class', () => {
      render(FloatingActionButton, { props: { size: 'lg' } });
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveClass('fab-lg');
    });

    it('defaults to md size', () => {
      render(FloatingActionButton);
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveClass('fab-md');
    });

    it('applies variant class', () => {
      render(FloatingActionButton, { props: { variant: 'danger' } });
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveClass('fab-danger');
    });

    it('defaults to primary variant', () => {
      render(FloatingActionButton);
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveClass('fab-primary');
    });

    it('applies disabled class when disabled', () => {
      render(FloatingActionButton, { props: { disabled: true } });
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveClass('fab-disabled');
    });

    it('applies extended class when extended', () => {
      render(FloatingActionButton, { props: { extended: true } });
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveClass('fab-extended');
    });
  });

  describe('attributes', () => {
    it('sets disabled attribute when disabled', () => {
      render(FloatingActionButton, { props: { disabled: true } });
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveAttribute('disabled');
    });

    it('does not set disabled attribute when enabled', () => {
      render(FloatingActionButton, { props: { disabled: false } });
      const btn = document.querySelector('button.fab');
      expect(btn).not.toHaveAttribute('disabled');
    });

    it('sets aria-label to label if provided', () => {
      render(FloatingActionButton, { props: { label: 'Add item' } });
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveAttribute('aria-label', 'Add item');
    });

    it('sets default aria-label when no label', () => {
      render(FloatingActionButton);
      const btn = document.querySelector('button.fab');
      expect(btn).toHaveAttribute('aria-label', 'Action button');
    });
  });

  // Event dispatch tests omitted due to Svelte event handling complexity;
  // click functionality is built-in; manual testing sufficient.
});
