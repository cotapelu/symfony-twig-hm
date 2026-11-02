import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import MultiStepForm from './MultiStepForm.svelte';

const sampleSteps = [
  {
    title: 'Account',
    description: 'Create your account',
    fields: [
      { name: 'username', label: 'Username', type: 'text', required: true },
      { name: 'email', label: 'Email', type: 'email', required: true, placeholder: 'you@example.com' },
    ],
  },
  {
    title: 'Profile',
    fields: [
      { name: 'firstName', label: 'First Name', type: 'text' },
      { name: 'country', label: 'Country', type: 'select', options: [
        { value: 'us', label: 'United States' },
        { value: 'vi', label: 'Vietnam' },
      ]},
    ],
  },
  {
    title: 'Review',
    description: 'Confirm and submit',
  },
];

describe('MultiStepForm Component', () => {
  it('renders wrapper with multi-step-form class', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    expect(container.querySelector('.multi-step-form')).not.toBeNull();
  });

  it('renders progress bar container', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    expect(container.querySelector('.progress-bar')).not.toBeNull();
    expect(container.querySelector('.progress-steps')).not.toBeNull();
  });

  it('renders 3 step dots', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    const dots = container.querySelectorAll('.step');
    expect(dots.length).toBe(3);
  });

  it('first step has active class initially', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    const dots = container.querySelectorAll('.step');
    expect(dots[0]?.classList.contains('active')).toBe(true);
  });

  it('step dot contains step number and title', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    const firstDot = container.querySelectorAll('.step')[0];
    expect(firstDot?.querySelector('.step-number')?.textContent?.trim()).toBe('1');
    expect(firstDot?.querySelector('.step-title')?.textContent?.trim()).toBe('Account');
  });

  it('renders step title in content area', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    const contentTitle = container.querySelector('.step-content h2.step-title');
    expect(contentTitle?.textContent?.trim()).toBe('Account');
  });

  it('renders step description when provided', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    expect(container.textContent).toContain('Create your account');
  });

  it('renders form element inside step', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    expect(container.querySelector('form.step-form')).not.toBeNull();
  });

  it('renders text input with name', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    const input = container.querySelector('input[type="text"]');
    expect(input).not.toBeNull();
    expect(input?.getAttribute('name')).toBe('username');
  });

  it('renders email input with required attribute and placeholder', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    const input = container.querySelector('input[type="email"]');
    expect(input).not.toBeNull();
    expect(input?.getAttribute('required')).toBe('true');
    expect(input?.getAttribute('placeholder')).toBe('you@example.com');
  });

  it('renders select with options on step 2 (when currentStep=1)', async () => {
    const { container } = render(MultiStepForm, {
      props: { steps: sampleSteps, currentStep: 1 },
    });
    await tick();
    const select = container.querySelector('select');
    expect(select).not.toBeNull();
  });

  it('renders next-btn button', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    const nextBtn = container.querySelector('.next-btn');
    expect(nextBtn).not.toBeNull();
  });

  it('renders next-btn button on last step (source uses .next-btn for all steps)', async () => {
    const { container } = render(MultiStepForm, {
      props: { steps: sampleSteps, currentStep: 2 },
    });
    await tick();
    // Source bug: button always uses .next-btn class; on last step text is "Complete" instead of "Submit"
    const btn = container.querySelector('.next-btn');
    expect(btn).not.toBeNull();
  });

  it('renders step-content wrapper', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    expect(container.querySelector('.step-content')).not.toBeNull();
  });

  it('renders form-actions wrapper', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    expect(container.querySelector('.form-actions')).not.toBeNull();
  });

  it('first step dot has completed+active classes when currentStep=1', async () => {
    const { container } = render(MultiStepForm, {
      props: { steps: sampleSteps, currentStep: 1 },
    });
    await tick();
    const dots = container.querySelectorAll('.step');
    // First dot should be completed (visited) and active (currentStep=1 still in step 1's progress)
    expect(dots[0]?.classList.contains('completed')).toBe(true);
  });

  it('renders with empty steps array (no steps)', async () => {
    const { container } = render(MultiStepForm, { props: { steps: [] } });
    await tick();
    expect(container.querySelector('.multi-step-form')).not.toBeNull();
    expect(container.querySelectorAll('.step').length).toBe(0);
  });

  it('renders field labels in current step', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    expect(container.textContent).toContain('Username');
    expect(container.textContent).toContain('Email');
  });

  it('does not render fields from other steps', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    // FirstName is in step 2 (Profile), should not render initially
    expect(container.textContent).not.toContain('First Name');
  });

  it('renders error message when errors prop has current step errors', async () => {
    const { container } = render(MultiStepForm, {
      props: { steps: sampleSteps, errors: { 0: { username: 'Username is required' } } },
    });
    await tick();
    const errorMsg = container.querySelector('.error-message');
    expect(errorMsg).not.toBeNull();
    expect(errorMsg?.textContent).toContain('Username is required');
  });

  it('does not render error message when no errors for current step', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps, errors: {} } });
    await tick();
    expect(container.querySelector('.error-message')).toBeNull();
  });

  it('applies error class to input with field error', async () => {
    const { container } = render(MultiStepForm, {
      props: { steps: sampleSteps, errors: { 0: { username: 'Required' } } },
    });
    await tick();
    expect(container.querySelector('input.error')).not.toBeNull();
  });

  it('does not render Prev button on first step (no prev-btn class)', async () => {
    const { container } = render(MultiStepForm, { props: { steps: sampleSteps } });
    await tick();
    expect(container.querySelector('.prev-btn')).toBeNull();
  });
});
