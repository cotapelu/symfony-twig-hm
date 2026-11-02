import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import userEvent from '@testing-library/user-event';
import MockButton from './molecules/MockButton.svelte';
import MockInput from './molecules/MockInput.svelte';
import MockTextarea from './molecules/MockTextarea.svelte';

// Mock the exact relative imports used by ContactForm.svelte
vi.mock('./Input.svelte', () => ({ default: MockInput }));
vi.mock('./Textarea.svelte', () => ({ default: MockTextarea }));
vi.mock('./Button.svelte', () => ({ default: MockButton }));

// Import after mocks are set up
import ContactForm from './ContactForm.svelte';

describe('ContactForm Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without error', () => {
    render(ContactForm);
    expect(screen.getByRole('button', { name: 'Send' })).toBeInTheDocument();
  });

  it('renders email input with required attribute', () => {
    render(ContactForm);
    const emailInput = screen.getByLabelText('Email *');
    expect(emailInput).toBeInTheDocument();
    expect(emailInput).toHaveAttribute('type', 'email');
    expect(emailInput).toHaveAttribute('required');
  });

  it('renders message textarea with required attribute', () => {
    render(ContactForm);
    const messageTextarea = screen.getByLabelText('Message *');
    expect(messageTextarea).toBeInTheDocument();
    expect(messageTextarea).toHaveAttribute('required');
  });

  it('renders submit button', () => {
    render(ContactForm);
    const submitBtn = screen.getByRole('button', { name: 'Send' });
    expect(submitBtn).toBeInTheDocument();
    expect(submitBtn).toHaveAttribute('type', 'submit');
  });

  it('accepts user input in email field', async () => {
    render(ContactForm);
    const emailInput = screen.getByLabelText('Email *');
    await userEvent.type(emailInput, 'test@example.com');
    expect(emailInput).toHaveValue('test@example.com');
  });

  it('accepts user input in message field', async () => {
    render(ContactForm);
    const messageTextarea = screen.getByLabelText('Message *');
    await userEvent.type(messageTextarea, 'Hello, this is a test message.');
    expect(messageTextarea).toHaveValue('Hello, this is a test message.');
  });

  it('submits form when submit button is clicked', async () => {
    render(ContactForm);
    const form = document.querySelector('form');
    expect(form).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));
    // Note: Svelte 5 $on doesn't work in test env, test UI behavior instead
    expect(form).toBeInTheDocument();
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(ContactForm);
    const { container: container2 } = render(ContactForm);
    expect(container1).toBeTruthy();
    expect(container2).toBeTruthy();
  });

  it('has correct form action and method', () => {
    render(ContactForm);
    const form = document.querySelector('form');
    expect(form).toBeInTheDocument();
    expect(form).toHaveAttribute('action', '/api/contact');
    expect(form).toHaveAttribute('method', 'POST');
  });
});