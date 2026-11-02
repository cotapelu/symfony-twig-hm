import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import userEvent from '@testing-library/user-event';

// Import mock components (these are Svelte files without lifecycle hooks)
import MockButton from './molecules/MockButton.svelte';
import MockCard from './molecules/MockCard.svelte';
import MockInput from './molecules/MockInput.svelte';
import MockTextarea from './molecules/MockTextarea.svelte';
import MockCheckbox from './molecules/MockCheckbox.svelte';

// Mock the exact relative imports used by FormTemplates.svelte
vi.mock('./atoms/Button.svelte', () => ({ default: MockButton }));
vi.mock('./molecules/Card.svelte', () => ({ default: MockCard }));
vi.mock('./atoms/Input.svelte', () => ({ default: MockInput }));
vi.mock('./atoms/Textarea.svelte', () => ({ default: MockTextarea }));
vi.mock('./atoms/Checkbox.svelte', () => ({ default: MockCheckbox }));

// Import after mocks are set up
import FormTemplates from './FormTemplates.svelte';

describe('FormTemplates Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders template selection when no template is selected', () => {
    render(FormTemplates);
    expect(screen.getByText('Choose a Form Template')).toBeInTheDocument();
    expect(screen.getByText('Contact Form')).toBeInTheDocument();
    expect(screen.getByText('User Registration')).toBeInTheDocument();
    expect(screen.getByText('Simple Survey')).toBeInTheDocument();
  });

  it('renders all three template cards with descriptions', () => {
    render(FormTemplates);
    expect(screen.getByText('Basic contact form with name, email, message')).toBeInTheDocument();
    expect(screen.getByText('Registration form with username, email, password')).toBeInTheDocument();
    expect(screen.getByText('Survey form with questions')).toBeInTheDocument();
  });

  it('shows "Use This Template" button on each card', () => {
    render(FormTemplates);
    const buttons = screen.getAllByText('Use This Template');
    expect(buttons).toHaveLength(3);
  });

  it('selects Contact Form template when clicked', async () => {
    render(FormTemplates);
    const contactCard = screen.getByText('Contact Form').closest('[role="button"]');
    await userEvent.click(contactCard!);
    await waitFor(() => {
      expect(screen.queryByText('Choose a Form Template')).not.toBeInTheDocument();
      expect(screen.getByText('Contact Form')).toBeInTheDocument();
    });
  });

  it('shows form fields for selected template', async () => {
    render(FormTemplates);
    const contactCard = screen.getByText('Contact Form').closest('[role="button"]');
    await userEvent.click(contactCard!);
    await waitFor(() => {
      // Labels include required asterisk
      expect(screen.getByLabelText('Name *')).toBeInTheDocument();
      expect(screen.getByLabelText('Email *')).toBeInTheDocument();
      expect(screen.getByLabelText('Message *')).toBeInTheDocument();
    });
  });

  it('shows "Back to Templates" button when template is selected', async () => {
    render(FormTemplates);
    const contactCard = screen.getByText('Contact Form').closest('[role="button"]');
    await userEvent.click(contactCard!);
    await waitFor(() => {
      expect(screen.getByText('Back to Templates')).toBeInTheDocument();
    });
  });

  it('returns to template selection when "Back to Templates" is clicked', async () => {
    render(FormTemplates);
    const contactCard = screen.getByText('Contact Form').closest('[role="button"]');
    await userEvent.click(contactCard!);
    await waitFor(() => {
      expect(screen.getByText('Back to Templates')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByText('Back to Templates'));
    await waitFor(() => {
      expect(screen.getByText('Choose a Form Template')).toBeInTheDocument();
    });
  });

  it('renders Registration template with username, email, password, checkbox', async () => {
    render(FormTemplates);
    const regCard = screen.getByText('User Registration').closest('[role="button"]');
    await userEvent.click(regCard!);
    await waitFor(() => {
      expect(screen.getByLabelText('Username *')).toBeInTheDocument();
      expect(screen.getByLabelText('Email *')).toBeInTheDocument();
      expect(screen.getByLabelText('Password *')).toBeInTheDocument();
      expect(screen.getByLabelText('Agree to terms *')).toBeInTheDocument();
    });
  });

  it('renders Survey template with name, age, comments', async () => {
    render(FormTemplates);
    const surveyCard = screen.getByText('Simple Survey').closest('[role="button"]');
    await userEvent.click(surveyCard!);
    await waitFor(() => {
      expect(screen.getByLabelText('What is your name? *')).toBeInTheDocument();
      expect(screen.getByLabelText('How old are you? *')).toBeInTheDocument();
      expect(screen.getByLabelText('Any comments?')).toBeInTheDocument();
    });
  });

  it('handles keyboard Enter key on template card', async () => {
    render(FormTemplates);
    const contactCard = screen.getByText('Contact Form').closest('[role="button"]');
    contactCard!.focus();
    await userEvent.keyboard('{Enter}');
    await waitFor(() => {
      expect(screen.queryByText('Choose a Form Template')).not.toBeInTheDocument();
    });
  });

  it('handles keyboard Space key on template card', async () => {
    render(FormTemplates);
    const contactCard = screen.getByText('Contact Form').closest('[role="button"]');
    contactCard!.focus();
    await userEvent.keyboard(' ');
    await waitFor(() => {
      expect(screen.queryByText('Choose a Form Template')).not.toBeInTheDocument();
    });
  });

  it('shows Submit button in selected template form', async () => {
    render(FormTemplates);
    const contactCard = screen.getByText('Contact Form').closest('[role="button"]');
    await userEvent.click(contactCard!);
    await waitFor(() => {
      expect(screen.getByText('Submit')).toBeInTheDocument();
    });
  });

  it('selects template and shows form when card is clicked', async () => {
    render(FormTemplates);
    const contactCard = screen.getByText('Contact Form').closest('[role="button"]');
    await userEvent.click(contactCard!);
    await waitFor(() => {
      expect(screen.queryByText('Choose a Form Template')).not.toBeInTheDocument();
      expect(screen.getByText('Contact Form')).toBeInTheDocument();
      expect(screen.getByLabelText('Name *')).toBeInTheDocument();
    });
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(FormTemplates);
    const { container: container2 } = render(FormTemplates);
    expect(container1).toBeTruthy();
    expect(container2).toBeTruthy();
  });

  it('accepts custom templates prop', () => {
    const customTemplates = [
      {
        id: 'custom',
        name: 'Custom Form',
        description: 'A custom form',
        fields: []
      }
    ];
    render(FormTemplates, { props: { templates: customTemplates } });
    expect(screen.getByText('Custom Form')).toBeInTheDocument();
    expect(screen.getByText('A custom form')).toBeInTheDocument();
  });
});