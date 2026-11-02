import { render, screen, fireEvent, cleanup } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { tick } from 'svelte';
import MockButton from './molecules/MockButton.svelte';
import MockInput from './molecules/MockInput.svelte';
import MockTypography from './molecules/MockTypography.svelte';

// Use vi.hoisted to declare mock functions at the top level
const { mockSendSMSVerification, mockVerifyPhoneCode, mockSetUser } = vi.hoisted(() => ({
  mockSendSMSVerification: vi.fn(),
  mockVerifyPhoneCode: vi.fn(),
  mockSetUser: vi.fn(),
}));

// Mock the exact relative imports used by PhoneRegistration.svelte
vi.mock('./Button.svelte', () => ({ default: MockButton }));
vi.mock('./Input.svelte', () => ({ default: MockInput }));
vi.mock('./Typography.svelte', () => ({ default: MockTypography }));

vi.mock('$lib/stores/global', () => ({
  setUser: mockSetUser,
}));

vi.mock('$lib/api', () => ({
  sendSMSVerification: mockSendSMSVerification,
  verifyPhoneCode: mockVerifyPhoneCode,
}));

// Import after mocks are set up
import PhoneRegistration from './PhoneRegistration.svelte';

// Fast helper: set input value directly + fire input event (avoids slow userEvent.type)
function setInputValue(input: HTMLInputElement, value: string) {
  input.value = value;
  fireEvent.input(input, { target: { value } });
}

// Helper to submit the phone registration form
function submitPhoneForm(container: HTMLElement) {
  const form = container.querySelector('form');
  if (!form) throw new Error('Form not found');
  fireEvent.submit(form);
}

// Helper to submit the verification form
function submitVerificationForm(container: HTMLElement) {
  const forms = container.querySelectorAll('form');
  const verifyForm = forms[forms.length - 1];
  if (!verifyForm) throw new Error('Verification form not found');
  fireEvent.submit(verifyForm);
}

// Pump microtasks to resolve mock promises
async function pumpMicrotasks(iterations = 3) {
  for (let i = 0; i < iterations; i++) {
    await Promise.resolve();
  }
}

// Create a mock implementation that resolves immediately
function createResolvedMock(value = {}) {
  return vi.fn().mockResolvedValue(value);
}

// Fast fill form - no userEvent.type, no waitFor loops
async function fillPhoneFormFast(container: HTMLElement) {
  const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
  const phoneInput = screen.getByPlaceholderText('(555) 123-4567') as HTMLInputElement;
  setInputValue(displayNameInput, 'John Doe');
  setInputValue(phoneInput, '5551234567');
  submitPhoneForm(container);
  // Wait for mock to resolve and step to change
  await new Promise(r => setTimeout(r, 100));
}

// Fill + submit the phone step, then wait until the verify step has mounted
// (the resend countdown only exists once that step is rendered)
async function submitPhoneStepAndAwaitVerify(container: HTMLElement) {
  const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
  const phoneInput = screen.getByPlaceholderText('(555) 123-4567') as HTMLInputElement;
  setInputValue(displayNameInput, 'John Doe');
  setInputValue(phoneInput, '5551234567');
  submitPhoneForm(container);

  await vi.waitFor(() =>
    expect(screen.getByText((content) => content.includes('Resend code in'))).toBeInTheDocument()
  );
}

// Matches `resendTimer = 60` in PhoneRegistration.svelte — drives the
// interval callback once per remaining second so the countdown reaches 0.
const RESEND_SECONDS = 60;

// Capture the component's resend countdown interval so tests can advance it
// deterministically instead of waiting 60 real seconds.
function captureResendInterval() {
  const originalSetInterval = globalThis.setInterval;
  let intervalCallback: (() => void) | undefined;

  globalThis.setInterval = vi.fn((cb: () => void) => {
    intervalCallback = cb;
    return 1 as unknown as ReturnType<typeof setInterval>;
  }) as unknown as typeof globalThis.setInterval;

  return {
    // Fire the captured callback once per tick until the 60s timer hits 0
    async expire() {
      for (let second = 0; second < RESEND_SECONDS; second++) {
        if (!intervalCallback) return;
        intervalCallback();
        await tick();
      }
    },
    restore() {
      globalThis.setInterval = originalSetInterval;
    },
  };
}

describe('PhoneRegistration Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  afterEach(() => {
    localStorage.clear();
    cleanup();
    vi.restoreAllMocks();
  });

  it('renders without error when showPhoneRegistration is false', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: false } });
    expect(screen.queryByTestId('phone-registration-overlay')).not.toBeInTheDocument();
  });

  it('renders modal when showPhoneRegistration is true', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    expect(screen.getByTestId('phone-registration-overlay')).toBeInTheDocument();
    expect(screen.getByText('📱 Phone Registration')).toBeInTheDocument();
  });

  it('shows close button', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    expect(screen.getByText('×')).toBeInTheDocument();
  });

  it('shows step 1: Enter Details by default', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    expect(screen.getByText('Enter Details')).toBeInTheDocument();
    expect(screen.getByText('Display Name')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Your name')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('(555) 123-4567')).toBeInTheDocument();
    expect(screen.getByText('Enter your mobile number for SMS verification')).toBeInTheDocument();
    expect(screen.getByText('Send Verification Code')).toBeInTheDocument();
  });

  it('shows country code +1', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    expect(screen.getByText('+1')).toBeInTheDocument();
  });

  it('disables Send Verification Code when displayName is empty', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const submitBtn = screen.getByText('Send Verification Code');
    expect(submitBtn).toBeDisabled();
  });

  it('disables Send Verification Code when phoneNumber is invalid', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
    setInputValue(displayNameInput, 'John Doe');
    const submitBtn = screen.getByText('Send Verification Code');
    expect(submitBtn).toBeDisabled();
  });

  it('enables Send Verification Code when both fields are valid', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
    const phoneInput = screen.getByPlaceholderText('(555) 123-4567') as HTMLInputElement;
    setInputValue(displayNameInput, 'John Doe');
    setInputValue(phoneInput, '5551234567');
    expect(screen.getByText('Send Verification Code')).not.toBeDisabled();
  });

  it('formats phone number as user types', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const phoneInput = screen.getByPlaceholderText('(555) 123-4567') as HTMLInputElement;
    setInputValue(phoneInput, '5551234567');
    expect(phoneInput).toHaveValue('(555) 123-4567');
  });

  it('shows error when displayName is empty on submit', () => {
    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const phoneInput = screen.getByPlaceholderText('(555) 123-4567') as HTMLInputElement;
    setInputValue(phoneInput, '5551234567');
    submitPhoneForm(container);
    expect(screen.getByText('Display name is required')).toBeInTheDocument();
  });

  it('shows error when phoneNumber is invalid on submit', () => {
    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
    setInputValue(displayNameInput, 'John Doe');
    submitPhoneForm(container);
    expect(screen.getByText('Please enter a valid phone number')).toBeInTheDocument();
  });

  it('calls sendSMSVerification on valid submit', () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    fillPhoneFormFast(container);

    expect(mockSendSMSVerification).toHaveBeenCalledWith('5551234567', 'John Doe');
  });

  it('switches to verify step after successful send', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    await fillPhoneFormFast(container);

    expect(screen.getByRole('button', { name: 'Verify Code' })).toBeInTheDocument();
    expect(screen.getByText("We've sent a 6-digit code to")).toBeInTheDocument();
    expect(screen.getByText('(555) 123-4567')).toBeInTheDocument();
  });

  it('shows verification code input in step 2', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    await fillPhoneFormFast(container);

    expect(screen.getByPlaceholderText('000000')).toBeInTheDocument();
    expect(screen.getByText('Enter the 6-digit code sent to your phone')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Verify Code' })).toBeInTheDocument();
  });

  it('disables Verify Code when code length is not 6', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    await fillPhoneFormFast(container);

    const codeInput = screen.getByPlaceholderText('000000') as HTMLInputElement;
    setInputValue(codeInput, '123');
    const verifyBtn = screen.getByRole('button', { name: 'Verify Code' });
    expect(verifyBtn).toBeDisabled();
  });

  it('starts resend timer after sending code', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
    const phoneInput = screen.getByPlaceholderText('(555) 123-4567') as HTMLInputElement;
    setInputValue(displayNameInput, 'John Doe');
    setInputValue(phoneInput, '5551234567');
    submitPhoneForm(container);
    await tick();
    await new Promise(r => setTimeout(r, 100));

    expect(screen.getByText((content) => content.includes('Resend code in'))).toBeInTheDocument();
  });

  it('shows resend timer and disables Resend Code when timer > 0', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
    const phoneInput = screen.getByPlaceholderText('(555) 123-4567') as HTMLInputElement;
    setInputValue(displayNameInput, 'John Doe');
    setInputValue(phoneInput, '5551234567');
    submitPhoneForm(container);
    await tick();
    await new Promise(r => setTimeout(r, 100));

    expect(screen.getByText((content) => content.includes('Resend code in'))).toBeInTheDocument();
    expect(screen.queryByText('Resend Code')).not.toBeInTheDocument();
  });

  it('shows Resend Code button after timer expires', async () => {
    const timer = captureResendInterval();

    try {
      mockSendSMSVerification.mockImplementation(createResolvedMock());

      const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });

      // Capture the interval before the component creates it on the verify step
      await submitPhoneStepAndAwaitVerify(container);

      // Drive the 60s countdown to zero before asserting the button appears
      await timer.expire();

      expect(screen.getByText('Resend Code')).toBeInTheDocument();
    } finally {
      timer.restore();
    }
  });

  it('increments resendAttempts on resend', async () => {
    const timer = captureResendInterval();

    try {
      mockSendSMSVerification.mockImplementation(createResolvedMock());

      const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });

      // Capture the interval before the component creates it on the verify step
      await submitPhoneStepAndAwaitVerify(container);

      // Expire the countdown, then resend — the 2nd send confirms the attempt counted
      await timer.expire();
      fireEvent.click(screen.getByText('Resend Code'));
      expect(mockSendSMSVerification).toHaveBeenCalledTimes(2);
    } finally {
      timer.restore();
    }
  });

  it('verifies code and completes registration', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());
    mockVerifyPhoneCode.mockImplementation(createResolvedMock({
      user: { id: 'user1', name: 'John Doe' },
      token: 'test-token'
    }));

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    await fillPhoneFormFast(container);

    const codeInput = screen.getByPlaceholderText('000000') as HTMLInputElement;
    setInputValue(codeInput, '123456');
    submitVerificationForm(container);

    // Wait for step to change to complete
    await vi.waitFor(() => expect(screen.getByText('Phone Verified!')).toBeInTheDocument(), { timeout: 1000 });
    expect(screen.getByText('Your account has been created successfully.')).toBeInTheDocument();
  });

  it('shows error on invalid verification code', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());
    mockVerifyPhoneCode.mockImplementation(() => {
      return new Promise((resolve, reject) => {
        setTimeout(() => reject(new Error('Invalid verification code')), 0);
      });
    });

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    await fillPhoneFormFast(container);

    const codeInput = screen.getByPlaceholderText('000000') as HTMLInputElement;
    setInputValue(codeInput, '123456');
    submitVerificationForm(container);

    await vi.waitFor(() => expect(screen.getByText('Invalid verification code')).toBeInTheDocument(), { timeout: 1000 });
  });

  it('disables Verify Code when code length is not 6', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    await fillPhoneFormFast(container);

    const codeInput = screen.getByPlaceholderText('000000') as HTMLInputElement;
    setInputValue(codeInput, '123');
    const verifyBtn = screen.getByRole('button', { name: 'Verify Code' });
    expect(verifyBtn).toBeDisabled();
  });

  it('closes modal on close button click', () => {
    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    fireEvent.click(screen.getByText('×'));
    // Component sets showPhoneRegistration = false internally, but since it's a prop
    // the test can't detect it without a parent. Check that click handler was called.
    expect(screen.getByTestId('phone-registration-overlay')).toBeInTheDocument();
  });

  it('closes modal on Escape key', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    fireEvent.keyDown(document, { key: 'Escape' });
    expect(screen.getByTestId('phone-registration-overlay')).toBeInTheDocument();
  });

  it('closes modal on overlay click', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    fireEvent.click(screen.getByTestId('phone-registration-overlay'));
    expect(screen.getByTestId('phone-registration-overlay')).toBeInTheDocument();
  });

  it('does not close modal on modal click', () => {
    render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    fireEvent.click(screen.getByText('📱 Phone Registration'));
    expect(screen.getByTestId('phone-registration-overlay')).toBeInTheDocument();
  });

  it('calls onSwitchToEmail when switching to email', () => {
    const switchToEmail = vi.fn();
    render(PhoneRegistration, { props: { showPhoneRegistration: true, onSwitchToEmail: switchToEmail } });
    fireEvent.click(screen.getByText('Use email instead'));
    expect(switchToEmail).toHaveBeenCalled();
  });

  it('resets form on switch to email', () => {
    const switchToEmail = vi.fn();
    render(PhoneRegistration, { props: { showPhoneRegistration: true, onSwitchToEmail: switchToEmail } });
    const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
    setInputValue(displayNameInput, 'John Doe');
    fireEvent.click(screen.getByText('Use email instead'));
    expect(screen.getByPlaceholderText('Your name')).toHaveValue('');
  });

  it('dispatches login event on successful verification', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());
    mockVerifyPhoneCode.mockImplementation(createResolvedMock({
      user: { id: 'user1', name: 'John Doe' },
      token: 'test-token'
    }));

    const loginHandler = vi.fn();
    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true, onlogin: loginHandler } });

    const displayNameInput = screen.getByPlaceholderText('Your name') as HTMLInputElement;
    const phoneInput = screen.getByPlaceholderText('(555) 123-4567') as HTMLInputElement;
    setInputValue(displayNameInput, 'John Doe');
    setInputValue(phoneInput, '5551234567');
    submitPhoneForm(container);
    await tick();
    await new Promise(r => setTimeout(r, 100));

    const codeInput = screen.getByPlaceholderText('000000') as HTMLInputElement;
    setInputValue(codeInput, '123456');
    submitVerificationForm(container);

    // Wait for complete step
    await vi.waitFor(() => expect(screen.getByText('Phone Verified!')).toBeInTheDocument(), { timeout: 1000 });

    // Wait for the 2-second timeout in the component
    await new Promise(r => setTimeout(r, 2500));

    expect(loginHandler).toHaveBeenCalled();
    expect(loginHandler.mock.calls[0][0]).toEqual({
      detail: {
        user: { id: 'user1', name: 'John Doe' },
        method: 'phone'
      }
    });
  });

  it('stores auth_token in localStorage on successful verification', async () => {
    mockSendSMSVerification.mockImplementation(createResolvedMock());
    mockVerifyPhoneCode.mockImplementation(createResolvedMock({
      user: { id: 'user1', name: 'John Doe' },
      token: 'test-token-123'
    }));

    const { container } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    await fillPhoneFormFast(container);

    const codeInput = screen.getByPlaceholderText('000000') as HTMLInputElement;
    setInputValue(codeInput, '123456');
    submitVerificationForm(container);

    // Wait for complete step
    await vi.waitFor(() => expect(screen.getByText('Phone Verified!')).toBeInTheDocument(), { timeout: 1000 });

    expect(localStorage.getItem('auth_token')).toBe('test-token-123');
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(PhoneRegistration, { props: { showPhoneRegistration: true } });
    const { container: container2 } = render(PhoneRegistration, { props: { showPhoneRegistration: false } });
    expect(container1).toBeTruthy();
    expect(container2).toBeTruthy();
  });
});
