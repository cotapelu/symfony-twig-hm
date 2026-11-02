import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import MockButton from './molecules/MockButton.svelte';
import MockInput from './molecules/MockInput.svelte';
import MockTypography from './molecules/MockTypography.svelte';

const { mockSendSMSVerification } = vi.hoisted(() => ({
  mockSendSMSVerification: vi.fn(),
}));

vi.mock('./Button.svelte', () => ({ default: MockButton }));
vi.mock('./Input.svelte', () => ({ default: MockInput }));
vi.mock('./Typography.svelte', () => ({ default: MockTypography }));
vi.mock('$lib/stores/global', () => ({ setUser: vi.fn() }));
vi.mock('$lib/api', () => ({
  sendSMSVerification: mockSendSMSVerification,
  verifyPhoneCode: vi.fn(),
}));

import PhoneRegistration from './PhoneRegistration.svelte';

function setInputValue(input: HTMLInputElement, value: string) {
  input.value = value;
  fireEvent.input(input, { target: { value } });
}

function submitPhoneForm(container: HTMLElement) {
  const form = container.querySelector('form');
  if (!form) throw new Error('Form not found');
  fireEvent.submit(form);
}

async function pumpMicrotasks(iterations = 3) {
  for (let i = 0; i < iterations; i++) {
    await Promise.resolve();
  }
}

function createResolvedMock(value = {}) {
  return () => {
    return new Promise((resolve) => {
      setTimeout(() => resolve(value), 0);
    });
  };
}

describe('resend debug 2', () => {
  it('debug resend timer with direct implementation', async () => {
    vi.useFakeTimers();
    mockSendSMSVerification.mockImplementation(createResolvedMock());

    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
    const phoneInput = screen.getByPlaceholderText('(555) 123-4567') as HTMLInputElement;
    setInputValue(displayNameInput, 'John Doe');
    setInputValue(phoneInput, '5551234567');
    
    const form = screen.getByText('Send Verification Code').closest('form')!;
    fireEvent.submit(form);
    
    console.log('after submit, loading:', screen.queryByText('Sending Code...') ? 'true' : 'false');
    console.log('mock calls:', mockSendSMSVerification.mock.calls.length);
    
    await tick();
    console.log('after tick, loading:', screen.queryByText('Sending Code...') ? 'true' : 'false');
    
    vi.advanceTimersByTime(10000);
    console.log('after advance 10000, loading:', screen.queryByText('Sending Code...') ? 'true' : 'false');
    
    await pumpMicrotasks();
    console.log('after pump, loading:', screen.queryByText('Sending Code...') ? 'true' : 'false');
    
    // Check step
    const verifyBtn = screen.queryByRole('button', { name: 'Verify Code' });
    console.log('verify button exists:', !!verifyBtn);
    
    vi.useRealTimers();
  });
});
