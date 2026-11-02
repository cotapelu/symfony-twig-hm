import { render, fireEvent, act } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import Tooltip from './Tooltip.svelte';

describe('Tooltip Simple Tests', () => {
  it('renders tooltip when visible prop is true', () => {
    render(Tooltip, { props: { title: 'Test', visible: true } });
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
  });

  it('does not render tooltip when visible prop is false', () => {
    render(Tooltip, { props: { title: 'Test', visible: false } });
    expect(document.querySelector('.tooltip-content')).not.toBeInTheDocument();
  });

  it('shows tooltip on hover after delay', async () => {
    render(Tooltip, { props: { title: 'Test', trigger: 'hover', visible: false, mouseEnterDelay: 50 } });
    
    const trigger = document.querySelector('.tooltip-trigger');
    fireEvent.mouseEnter(trigger);
    
    expect(document.querySelector('.tooltip-content')).not.toBeInTheDocument();
    
    await new Promise(resolve => setTimeout(resolve, 100));
    
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
  });

  it('hides tooltip on mouseleave after delay', async () => {
    const { unmount } = render(Tooltip, { props: { title: 'Test', trigger: 'hover', visible: true, mouseLeaveDelay: 50 } });
    
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
    
    const trigger = document.querySelector('.tooltip-trigger');
    fireEvent.mouseLeave(trigger);
    
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
    
    await new Promise(resolve => setTimeout(resolve, 100));
    
    expect(document.querySelector('.tooltip-content')).not.toBeInTheDocument();
    unmount();
  });

  it('shows tooltip on focus', async () => {
    render(Tooltip, { props: { title: 'Test', trigger: 'focus', visible: false } });
    
    const trigger = document.querySelector('.tooltip-trigger');
    fireEvent.focus(trigger);
    await act();
    
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
  });

  it('hides tooltip on blur', async () => {
    const { unmount } = render(Tooltip, { props: { title: 'Test', trigger: 'focus', visible: true } });
    
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
    
    const trigger = document.querySelector('.tooltip-trigger');
    fireEvent.blur(trigger);
    await act();
    
    expect(document.querySelector('.tooltip-content')).not.toBeInTheDocument();
    unmount();
  });

  it('toggles tooltip on click', async () => {
    render(Tooltip, { props: { title: 'Test', trigger: 'click', visible: false } });
    
    const trigger = document.querySelector('.tooltip-trigger');
    expect(document.querySelector('.tooltip-content')).not.toBeInTheDocument();
    
    fireEvent.click(trigger);
    await act();
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
    
    fireEvent.click(trigger);
    await act();
    expect(document.querySelector('.tooltip-content')).not.toBeInTheDocument();
  });

  it('hides tooltip on Escape key via trigger keydown', async () => {
    const { unmount } = render(Tooltip, { props: { title: 'Test', visible: true } });
    
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
    
    // Fire keydown on the trigger element which has the listener
    const trigger = document.querySelector('.tooltip-trigger');
    fireEvent.keyDown(trigger, { key: 'Escape' });
    await act();
    
    expect(document.querySelector('.tooltip-content')).not.toBeInTheDocument();
    unmount();
  });

  it('handles multiple instances independently', () => {
    const { container: c1 } = render(Tooltip, { props: { title: 'Tooltip 1', visible: true } });
    const { container: c2 } = render(Tooltip, { props: { title: 'Tooltip 2', visible: true } });
    
    expect(c1).toBeTruthy();
    expect(c2).toBeTruthy();
  });

  it('handles rapid visibility changes', () => {
    const { unmount: unmount1 } = render(Tooltip, { props: { title: 'Test', visible: true } });
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
    unmount1();
    
    const { unmount: unmount2 } = render(Tooltip, { props: { title: 'Test', visible: false } });
    expect(document.querySelector('.tooltip-content')).not.toBeInTheDocument();
    unmount2();
    
    const { unmount: unmount3 } = render(Tooltip, { props: { title: 'Test', visible: true } });
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
    unmount3();
  });

  it('handles empty title', () => {
    render(Tooltip, { props: { title: '', visible: true } });
    const inner = document.querySelector('.tooltip-inner');
    expect(inner).toBeInTheDocument();
  });

  it('handles zero delays', async () => {
    render(Tooltip, { props: { title: 'Test', trigger: 'hover', visible: false, mouseEnterDelay: 0, mouseLeaveDelay: 0 } });
    
    const trigger = document.querySelector('.tooltip-trigger');
    fireEvent.mouseEnter(trigger);
    await act();
    
    expect(document.querySelector('.tooltip-content')).toBeInTheDocument();
  });

  it('applies placement classes', () => {
    const placements = ['top', 'bottom', 'left', 'right'] as const;
    
    placements.forEach(placement => {
      const { container, unmount } = render(Tooltip, { props: { title: 'Test', placement, visible: true } });
      const content = container.querySelector('.tooltip-content');
      expect(content).toHaveClass(`tooltip-${placement}`);
      unmount();
    });
  });

  it('applies custom className', () => {
    render(Tooltip, { props: { title: 'Test', className: 'custom-tooltip', visible: true } });
    const content = document.querySelector('.tooltip-content');
    expect(content).toHaveClass('custom-tooltip');
  });

  it('has tabindex for focus trigger', () => {
    render(Tooltip, { props: { trigger: 'focus' } });
    const trigger = document.querySelector('.tooltip-trigger');
    expect(trigger).toHaveAttribute('tabindex', '0');
  });

  it('does not have tabindex for hover trigger', () => {
    render(Tooltip, { props: { trigger: 'hover' } });
    const trigger = document.querySelector('.tooltip-trigger');
    expect(trigger).not.toHaveAttribute('tabindex');
  });

  it('tooltip content has role tooltip', () => {
    render(Tooltip, { props: { title: 'Test', visible: true } });
    const content = document.querySelector('.tooltip-content');
    expect(content).toHaveAttribute('role', 'tooltip');
  });

  it('tooltip content has aria-hidden based on visibility', () => {
    render(Tooltip, { props: { title: 'Test', visible: true } });
    const content = document.querySelector('.tooltip-content');
    expect(content).toHaveAttribute('aria-hidden', 'false');
  });

  it('tooltip content has tooltip-arrow', () => {
    render(Tooltip, { props: { title: 'Test', visible: true } });
    const arrow = document.querySelector('.tooltip-arrow');
    expect(arrow).toBeInTheDocument();
  });

  it('includes tooltip base styles', () => {
    render(Tooltip, { props: { title: 'Test', visible: true } });
    expect(document.querySelector('.tooltip-inner')).toBeInTheDocument();
  });

  it('includes tooltip arrow styles', () => {
    render(Tooltip, { props: { title: 'Test', visible: true } });
    expect(document.querySelector('.tooltip-arrow')).toBeInTheDocument();
  });

  it('handles undefined props gracefully', () => {
    render(Tooltip, { 
      props: { 
        title: undefined as any,
        placement: undefined as any,
        trigger: undefined as any,
        visible: undefined as any,
        mouseEnterDelay: undefined as any,
        mouseLeaveDelay: undefined as any,
        className: undefined as any
      }
    });
    expect(document.body).toBeTruthy();
  });
});
