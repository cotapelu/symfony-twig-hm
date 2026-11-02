import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import ConditionalForm from './ConditionalForm.svelte';

const baseSchema = {
  fields: [
    { name: 'firstName', label: 'First Name', type: 'text', required: true, placeholder: 'Enter first name' },
    { name: 'email', label: 'Email', type: 'email', required: true, placeholder: 'you@example.com' },
    { name: 'role', label: 'Role', type: 'select', options: [
      { value: 'user', label: 'User' },
      { value: 'admin', label: 'Admin' },
    ]},
    { name: 'gender', label: 'Gender', type: 'radio', options: [
      { value: 'm', label: 'Male' },
      { value: 'f', label: 'Female' },
    ]},
    { name: 'adminCode', label: 'Admin Code', type: 'text', condition: { dependsOn: 'role', value: 'admin' } },
  ],
  submitLabel: 'Save',
};

describe('ConditionalForm Component', () => {
  it('renders wrapper with conditional-form class', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    expect(container.querySelector('.conditional-form')).not.toBeNull();
  });

  it('renders form element', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    expect(container.querySelector('form')).not.toBeNull();
  });

  it('renders a field wrapper per visible field (no conditions met)', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    // With role !== 'admin', adminCode is hidden → 4 fields (firstName, email, role, gender)
    const fields = container.querySelectorAll('.form-field');
    expect(fields.length).toBe(4);
  });

  it('renders text input with name and id', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    const input = container.querySelector('input[type="text"][name="firstName"]');
    expect(input).not.toBeNull();
    expect(input?.getAttribute('id')).toBe('firstName');
    expect(input?.getAttribute('placeholder')).toBe('Enter first name');
  });

  it('renders email input with required attribute', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    const input = container.querySelector('input[type="email"]');
    expect(input).not.toBeNull();
    expect(input?.getAttribute('required')).toBe('true');
  });

  it('renders select with options', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    const select = container.querySelector('select');
    expect(select).not.toBeNull();
    expect(select?.querySelectorAll('option').length).toBeGreaterThanOrEqual(3); // placeholder + 2 options
  });

  it('renders radio group', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    expect(container.querySelector('.radio-group')).not.toBeNull();
  });

  it('renders 2 radio options for gender field', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    const radios = container.querySelectorAll('input[type="radio"][name="gender"]');
    expect(radios.length).toBe(2);
  });

  it('renders labels for each field', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    expect(container.textContent).toContain('First Name');
    expect(container.textContent).toContain('Email');
    expect(container.textContent).toContain('Role');
    expect(container.textContent).toContain('Gender');
  });

  it('renders default Submit button when no submitLabel', async () => {
    const noLabelSchema = { fields: [] };
    render(ConditionalForm, { props: { schema: noLabelSchema } });
    await tick();
    expect(screen.getByText('Submit')).not.toBeNull();
  });

  it('renders custom submitLabel', async () => {
    render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    expect(screen.getByText('Save')).not.toBeNull();
  });

  it('renders submit button as type=submit inside form-actions', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    const btn = container.querySelector('.submit-btn');
    expect(btn).not.toBeNull();
    expect(btn?.getAttribute('type')).toBe('submit');
  });

  it('renders conditional field adminCode when role === admin', async () => {
    const { container } = render(ConditionalForm, {
      props: { schema: baseSchema, data: { role: 'admin' } },
    });
    await tick();
    const adminInput = container.querySelector('input[name="adminCode"]');
    expect(adminInput).not.toBeNull();
  });

  it('hides conditional field adminCode when role !== admin', async () => {
    const { container } = render(ConditionalForm, {
      props: { schema: baseSchema, data: { role: 'user' } },
    });
    await tick();
    const adminInput = container.querySelector('input[name="adminCode"]');
    expect(adminInput).toBeNull();
  });

  it('hides conditional field adminCode when role is empty', async () => {
    const { container } = render(ConditionalForm, {
      props: { schema: baseSchema, data: { role: '' } },
    });
    await tick();
    const adminInput = container.querySelector('input[name="adminCode"]');
    expect(adminInput).toBeNull();
  });

  it('renders with empty schema (no fields)', async () => {
    const { container } = render(ConditionalForm, {
      props: { schema: { fields: [] } },
    });
    await tick();
    expect(container.querySelector('.form-field')).toBeNull();
    expect(container.querySelector('.submit-btn')).not.toBeNull();
  });

  it('renders with undefined fields array', async () => {
    const { container } = render(ConditionalForm, {
      props: { schema: {} },
    });
    await tick();
    expect(container.querySelector('.form-field')).toBeNull();
  });

  it('renders error message when errors prop has field errors', async () => {
    const { container } = render(ConditionalForm, {
      props: { schema: baseSchema, errors: { firstName: 'First Name is required' } },
    });
    await tick();
    const errorMsg = container.querySelector('.error-message');
    expect(errorMsg).not.toBeNull();
    expect(errorMsg?.textContent).toContain('First Name is required');
  });

  it('does not render error message when no errors', async () => {
    const { container } = render(ConditionalForm, {
      props: { schema: baseSchema, errors: {} },
    });
    await tick();
    expect(container.querySelector('.error-message')).toBeNull();
  });

  it('applies error class to input when field has error', async () => {
    const { container } = render(ConditionalForm, {
      props: { schema: baseSchema, errors: { firstName: 'Required' } },
    });
    await tick();
    const errorInput = container.querySelector('input[name="firstName"].error');
    expect(errorInput).not.toBeNull();
  });

  it('renders value attribute from data prop', async () => {
    const { container } = render(ConditionalForm, {
      props: { schema: baseSchema, data: { firstName: 'Alice', email: 'a@b.com' } },
    });
    await tick();
    expect(container.querySelector('input[name="firstName"]')?.getAttribute('value')).toBe('Alice');
    expect(container.querySelector('input[name="email"]')?.getAttribute('value')).toBe('a@b.com');
  });

  it('renders form-actions wrapper', async () => {
    const { container } = render(ConditionalForm, { props: { schema: baseSchema } });
    await tick();
    expect(container.querySelector('.form-actions')).not.toBeNull();
  });
});
