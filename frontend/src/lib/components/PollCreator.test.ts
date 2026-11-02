import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import PollCreator from './PollCreator.svelte';

describe('PollCreator Component', () => {
  it('renders wrapper with poll-creator class', async () => {
    const { container } = render(PollCreator);
    await tick();
    expect(container.querySelector('.poll-creator')).not.toBeNull();
  });

  it('renders header with Create Poll title', async () => {
    render(PollCreator);
    await tick();
    // Create Poll text appears in h3 (header) and in footer button. Match by h3 specifically.
    const { container } = render(PollCreator);
    await tick();
    const h3 = container.querySelector('.creator-header h3');
    expect(h3?.textContent).toBe('Create Poll');
  });

  it('renders close button with aria-label', async () => {
    const { container } = render(PollCreator);
    await tick();
    const closeBtn = container.querySelector('.close-btn');
    expect(closeBtn).not.toBeNull();
    expect(closeBtn?.getAttribute('aria-label')).toBe('Close poll creator');
  });

  it('renders question input with id and label', async () => {
    const { container } = render(PollCreator);
    await tick();
    const input = container.querySelector('#poll-question');
    expect(input).not.toBeNull();
    expect(input?.getAttribute('type')).toBe('text');
    expect(input?.getAttribute('maxlength')).toBe('200');
    expect(input?.hasAttribute('required')).toBe(true);
  });

  it('question label has required asterisk via ::after', async () => {
    const { container } = render(PollCreator);
    await tick();
    const label = container.querySelector('label[for="poll-question"]');
    expect(label?.classList.contains('required')).toBe(true);
  });

  it('renders character count showing 0/200 initially', async () => {
    render(PollCreator);
    await tick();
    expect(screen.getByText('0/200')).not.toBeNull();
  });

  it('character count updates when typing in question input', async () => {
    const { container } = render(PollCreator);
    await tick();
    const input = container.querySelector('#poll-question') as HTMLInputElement;
    await fireEvent.input(input, { target: { value: 'My question?' } });
    await tick();
    expect(container.querySelector('.char-count')?.textContent).toContain('12/200');
  });

  it('renders exactly 2 option inputs initially', async () => {
    const { container } = render(PollCreator);
    await tick();
    const inputs = container.querySelectorAll('.option-input');
    expect(inputs.length).toBe(2);
  });

  it('option inputs have correct placeholders', async () => {
    const { container } = render(PollCreator);
    await tick();
    const inputs = container.querySelectorAll('.option-input');
    expect(inputs[0]?.getAttribute('placeholder')).toBe('Option 1');
    expect(inputs[1]?.getAttribute('placeholder')).toBe('Option 2');
  });

  it('does not render remove button when only 2 options', async () => {
    const { container } = render(PollCreator);
    await tick();
    expect(container.querySelector('.remove-option-btn')).toBeNull();
  });

  it('renders Add Option button initially', async () => {
    render(PollCreator);
    await tick();
    expect(screen.getByText('+ Add Option')).not.toBeNull();
  });

  it('clicking Add Option adds a third option', async () => {
    const { container } = render(PollCreator);
    await tick();
    const addBtn = screen.getByText('+ Add Option');
    await fireEvent.click(addBtn);
    await tick();
    const inputs = container.querySelectorAll('.option-input');
    expect(inputs.length).toBe(3);
  });

  it('adds remove button when more than 2 options', async () => {
    const { container } = render(PollCreator);
    await tick();
    await fireEvent.click(screen.getByText('+ Add Option'));
    await tick();
    expect(container.querySelector('.remove-option-btn')).not.toBeNull();
  });

  it('remove button removes the corresponding option', async () => {
    const { container } = render(PollCreator);
    await tick();
    // Add an option first (3 total)
    await fireEvent.click(screen.getByText('+ Add Option'));
    await tick();
    expect(container.querySelectorAll('.option-input').length).toBe(3);
    // Click first remove button
    const removeBtn = container.querySelector('.remove-option-btn');
    if (removeBtn) {
      await fireEvent.click(removeBtn);
      await tick();
    }
    expect(container.querySelectorAll('.option-input').length).toBe(2);
  });

  it('Add Option button is hidden when 10 options reached', async () => {
    const { container } = render(PollCreator);
    await tick();
    // Add 8 more (start with 2 → 10 total)
    const addBtn = screen.getByText('+ Add Option');
    for (let i = 0; i < 8; i++) {
      await fireEvent.click(addBtn);
      await tick();
    }
    expect(container.querySelectorAll('.option-input').length).toBe(10);
    expect(container.querySelector('.add-option-btn')).toBeNull();
  });

  it('does not remove when only 2 options remain', async () => {
    const { container } = render(PollCreator);
    await tick();
    expect(container.querySelectorAll('.option-input').length).toBe(2);
    // No remove button to click (template guards against removing below 2)
    expect(container.querySelector('.remove-option-btn')).toBeNull();
  });

  it('renders Advanced Options toggle button', async () => {
    render(PollCreator);
    await tick();
    expect(screen.getByText(/Advanced Options/)).not.toBeNull();
  });

  it('advanced toggle shows ▼ when expanded, ▶ when collapsed', async () => {
    render(PollCreator);
    await tick();
    const toggle = screen.getByText(/Advanced Options/);
    expect(toggle.textContent).toContain('▶');
    await fireEvent.click(toggle);
    await tick();
    expect(screen.getByText(/Advanced Options/).textContent).toContain('▼');
  });

  it('advanced toggle has aria-expanded=false initially', async () => {
    render(PollCreator);
    await tick();
    const toggle = screen.getByText(/Advanced Options/);
    expect(toggle.getAttribute('aria-expanded')).toBe('false');
  });

  it('advanced toggle has aria-expanded=true after click', async () => {
    render(PollCreator);
    await tick();
    const toggle = screen.getByText(/Advanced Options/);
    await fireEvent.click(toggle);
    await tick();
    expect(toggle.getAttribute('aria-expanded')).toBe('true');
  });

  it('does not render advanced options initially', async () => {
    const { container } = render(PollCreator);
    await tick();
    expect(container.querySelector('.advanced-options')).toBeNull();
  });

  it('renders advanced options after toggle click', async () => {
    const { container } = render(PollCreator);
    await tick();
    await fireEvent.click(screen.getByText(/Advanced Options/));
    await tick();
    expect(container.querySelector('.advanced-options')).not.toBeNull();
  });

  it('renders allow-multiple and anonymous checkboxes in advanced section', async () => {
    const { container } = render(PollCreator);
    await tick();
    await fireEvent.click(screen.getByText(/Advanced Options/));
    await tick();
    expect(screen.getByText(/Allow multiple selections/)).not.toBeNull();
    expect(screen.getByText(/Anonymous voting/)).not.toBeNull();
  });

  it('renders expiration select with default 1 day (24h)', async () => {
    const { container } = render(PollCreator);
    await tick();
    await fireEvent.click(screen.getByText(/Advanced Options/));
    await tick();
    const select = container.querySelector('.expiry-select') as HTMLSelectElement;
    expect(select?.value).toBe('24');
  });

  it('expiration select has all expected options', async () => {
    const { container } = render(PollCreator);
    await tick();
    await fireEvent.click(screen.getByText(/Advanced Options/));
    await tick();
    const select = container.querySelector('.expiry-select');
    const options = select?.querySelectorAll('option');
    expect(options?.length).toBe(5);
  });

  it('expiration option labels include Never for 0', async () => {
    const { container } = render(PollCreator);
    await tick();
    await fireEvent.click(screen.getByText(/Advanced Options/));
    await tick();
    expect(screen.getByText('Never')).not.toBeNull();
  });

  it('renders Cancel button in footer', async () => {
    const { container } = render(PollCreator);
    await tick();
    const cancelBtn = container.querySelector('.cancel-btn');
    expect(cancelBtn).not.toBeNull();
    expect(cancelBtn?.textContent?.trim()).toBe('Cancel');
  });

  it('renders Create Poll button in footer', async () => {
    const { container } = render(PollCreator);
    await tick();
    const createBtn = container.querySelector('.create-btn');
    expect(createBtn).not.toBeNull();
    expect(createBtn?.textContent?.trim()).toBe('Create Poll');
  });

  it('Create button is disabled when question is empty', async () => {
    const { container } = render(PollCreator);
    await tick();
    const btn = container.querySelector('.create-btn');
    expect(btn?.hasAttribute('disabled')).toBe(true);
  });

  it('Create button is disabled with only 1 filled option', async () => {
    const { container } = render(PollCreator);
    await tick();
    const inputs = container.querySelectorAll('.option-input');
    await fireEvent.input(inputs[0] as HTMLInputElement, { target: { value: 'Yes' } });
    await tick();
    const btn = container.querySelector('.create-btn');
    expect(btn?.hasAttribute('disabled')).toBe(true);
  });

  it('Create button is enabled with question + 2 valid options', async () => {
    const { container } = render(PollCreator);
    await tick();
    const questionInput = container.querySelector('#poll-question') as HTMLInputElement;
    await fireEvent.input(questionInput, { target: { value: 'Pick one?' } });
    const options = container.querySelectorAll('.option-input');
    await fireEvent.input(options[0] as HTMLInputElement, { target: { value: 'A' } });
    await fireEvent.input(options[1] as HTMLInputElement, { target: { value: 'B' } });
    await tick();
    const btn = container.querySelector('.create-btn');
    expect(btn?.hasAttribute('disabled')).toBe(false);
  });

  it('clicking close button does not throw', async () => {
    render(PollCreator);
    await tick();
    const closeBtn = screen.getByLabelText('Close poll creator');
    let error: any = null;
    try {
      await fireEvent.click(closeBtn);
    } catch (e) {
      error = e;
    }
    expect(error).toBeNull();
  });

  it('clicking Cancel button does not throw', async () => {
    const { container } = render(PollCreator);
    await tick();
    const cancelBtn = container.querySelector('.cancel-btn');
    let error: any = null;
    try {
      if (cancelBtn) {
        await fireEvent.click(cancelBtn);
      }
    } catch (e) {
      error = e;
    }
    expect(error).toBeNull();
  });
});
