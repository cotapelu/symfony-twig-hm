import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import Steps from './Steps.svelte';

describe('Steps', () => {
  const items = [
    { title: 'Step 1', description: 'First step' },
    { title: 'Step 2', description: 'Second step' },
    { title: 'Step 3', description: 'Third step' }
  ];

  const renderSteps = (props = {}) => render(Steps, { props: { items, ...props } });

  it('renders all steps', () => {
    renderSteps();
    expect(screen.getByText('Step 1')).toBeInTheDocument();
    expect(screen.getByText('Step 2')).toBeInTheDocument();
    expect(screen.getByText('Step 3')).toBeInTheDocument();
  });

  it('shows descriptions when present and appropriate', () => {
    renderSteps();
    expect(screen.getByText('First step')).toBeInTheDocument();
  });

  it('marks current step with status process by default', () => {
    renderSteps();
    const firstStep = screen.getByText('Step 1').closest('div[role="tab"]');
    expect(firstStep).toHaveClass('step-process');
    expect(firstStep).toHaveAttribute('aria-current', 'step');
  });

  it('applies step-finish class to completed steps', () => {
    renderSteps({ current: 2 }); // zero-based index, current=2 means step 3 is current, steps 1-2 finished
    const step1 = screen.getByText('Step 1').closest('div[role="tab"]');
    const step2 = screen.getByText('Step 2').closest('div[role="tab"]');
    const step3 = screen.getByText('Step 3').closest('div[role="tab"]');
    expect(step1).toHaveClass('step-finish');
    expect(step2).toHaveClass('step-finish');
    expect(step3).toHaveClass('step-process');
  });

  it('marks step as error when status is error', () => {
    renderSteps({ current: 1, status: 'error' });
    const currentStep = screen.getByText('Step 2').closest('div[role="tab"]');
    expect(currentStep).toHaveClass('step-error');
  });

  it('sets tabindex to 0 for steps before current and -1 for current and after', () => {
    renderSteps({ current: 1 });
    // Step 0 (index 0) < current => clickable
    const step0 = screen.getByText('Step 1').closest('div[role="tab"]');
    // Step 1 (current) => not clickable
    const step1 = screen.getByText('Step 2').closest('div[role="tab"]');
    // Step 2 > current => not clickable
    const step2 = screen.getByText('Step 3').closest('div[role="tab"]');
    expect(step0).toHaveAttribute('tabindex', '0');
    expect(step1).toHaveAttribute('tabindex', '-1');
    expect(step2).toHaveAttribute('tabindex', '-1');
  });

  it('does not allow clicking on non-clickable steps (tabindex -1)', async () => {
    renderSteps({ current: 1 });
    const step2 = screen.getByText('Step 3').closest('div[role="tab"]');
    expect(step2).toHaveAttribute('tabindex', '-1');
    // Even if we force click, nothing changes in UI since component is controlled
    await fireEvent.click(step2!);
    // Step1 (current) still process
    const step1 = screen.getByText('Step 2').closest('div[role="tab"]');
    expect(step1).toHaveClass('step-process');
  });

  it('applies direction class to container', () => {
    const { container } = renderSteps({ direction: 'vertical' });
    const root = container.querySelector('div[role="tablist"]');
    expect(root).toHaveClass('steps-vertical');
  });

  it('applies size class to container', () => {
    const { container } = renderSteps({ size: 'small' });
    const root = container.querySelector('div[role="tablist"]');
    expect(root).toHaveClass('steps-small');
  });

  it('applies custom className to container', () => {
    const { container } = renderSteps({ className: 'my-steps' });
    const root = container.querySelector('div[role="tablist"]');
    expect(root).toHaveClass('my-steps');
  });

  it('renders step icons when provided', () => {
    const itemsWithIcons = [
      { title: 'Home', icon: '🏠' },
      { title: 'Settings', icon: '⚙️' }
    ];
    renderSteps({ items: itemsWithIcons });
    expect(screen.getByText('🏠')).toBeInTheDocument();
    expect(screen.getByText('⚙️')).toBeInTheDocument();
  });

  it('shows progress dot when progressDot true', () => {
    const { container } = renderSteps({ progressDot: true });
    const dots = container.querySelectorAll('.step-dot');
    expect(dots.length).toBe(items.length);
  });

  it('hides description in vertical direction by default', () => {
    renderSteps({ direction: 'vertical' });
    // labelPlacement default is horizontal; description hidden in vertical
    expect(screen.queryByText('First step')).not.toBeInTheDocument();
  });

  it('shows description in vertical when labelPlacement vertical', () => {
    renderSteps({ direction: 'vertical', labelPlacement: 'vertical' });
    expect(screen.getByText('First step')).toBeInTheDocument();
  });
});
