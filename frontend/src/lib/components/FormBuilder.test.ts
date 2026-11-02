import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import FormBuilder from './FormBuilder.svelte';

describe('FormBuilder Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const sampleSchema = {
    fields: [
      { name: 'username', label: 'Username', type: 'text', placeholder: 'Enter username', required: true },
      { name: 'email', label: 'Email', type: 'email', placeholder: 'Enter email', required: true },
      { name: 'password', label: 'Password', type: 'password', placeholder: 'Enter password', required: true },
      { name: 'bio', label: 'Bio', type: 'textarea', placeholder: 'Tell us about yourself', required: false },
    ],
    submitLabel: 'Create Account'
  };

  describe('rendering', () => {
    it('renders form element', () => {
      render(FormBuilder, { props: { schema: sampleSchema } });
      const form = document.querySelector('form.dynamic-form');
      expect(form).toBeInTheDocument();
    });

    it('renders without schema', () => {
      render(FormBuilder);
      const form = document.querySelector('form.dynamic-form');
      expect(form).toBeInTheDocument();
    });

    it('renders empty form when schema has no fields', () => {
      render(FormBuilder, { props: { schema: { fields: [] } } });
      const form = document.querySelector('form.dynamic-form');
      expect(form).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /submit/i })).toBeInTheDocument();
    });

    it('renders text input fields', () => {
      render(FormBuilder, { props: { schema: sampleSchema, data: { username: 'john' } } });
      const usernameInput = screen.getByLabelText('Username');
      expect(usernameInput).toBeInTheDocument();
      expect(usernameInput).toHaveAttribute('type', 'text');
      expect(usernameInput).toHaveAttribute('required');
      expect(usernameInput).toHaveValue('john');
    });

    it('renders email input fields', () => {
      render(FormBuilder, { props: { schema: sampleSchema, data: { email: 'john@example.com' } } });
      const emailInput = screen.getByLabelText('Email');
      expect(emailInput).toBeInTheDocument();
      expect(emailInput).toHaveAttribute('type', 'email');
      expect(emailInput).toHaveAttribute('required');
      expect(emailInput).toHaveValue('john@example.com');
    });

    it('renders password input fields', () => {
      render(FormBuilder, { props: { schema: sampleSchema, data: { password: 'secret123' } } });
      const passwordInput = screen.getByLabelText('Password');
      expect(passwordInput).toBeInTheDocument();
      expect(passwordInput).toHaveAttribute('type', 'password');
      expect(passwordInput).toHaveAttribute('required');
      expect(passwordInput).toHaveValue('secret123');
    });

    it('renders textarea fields', () => {
      render(FormBuilder, { props: { schema: sampleSchema, data: { bio: 'Hello world' } } });
      const bioTextarea = screen.getByLabelText('Bio');
      expect(bioTextarea).toBeInTheDocument();
      expect(bioTextarea.tagName).toBe('TEXTAREA');
      expect(bioTextarea).toHaveValue('Hello world');
    });

    it('renders submit button with custom label', () => {
      render(FormBuilder, { props: { schema: sampleSchema } });
      expect(screen.getByRole('button', { name: 'Create Account' })).toBeInTheDocument();
    });

    it('renders default submit label when not provided', () => {
      const schemaWithoutLabel = { fields: [] };
      render(FormBuilder, { props: { schema: schemaWithoutLabel } });
      expect(screen.getByRole('button', { name: /submit/i })).toBeInTheDocument();
    });

    it('applies error class when field has error', () => {
      render(FormBuilder, { 
        props: { 
          schema: sampleSchema, 
          errors: { username: 'Username is required' } 
        } 
      });
      const usernameInput = screen.getByLabelText('Username');
      expect(usernameInput).toHaveClass('error');
    });

    it('renders error message when field has error', () => {
      render(FormBuilder, { 
        props: { 
          schema: sampleSchema, 
          errors: { email: 'Invalid email format' } 
        } 
      });
      expect(screen.getByText('Invalid email format')).toBeInTheDocument();
    });
  });

  describe('form submission', () => {
    it('calls onSubmit callback on valid form', async () => {
      const onSubmit = vi.fn();
      render(FormBuilder, { props: { schema: sampleSchema, onSubmit, data: { username: 'john', email: 'john@example.com', password: 'secret' } } });
      
      const form = document.querySelector('form.dynamic-form');
      await fireEvent.submit(form!);
      
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          username: 'john',
          email: 'john@example.com',
          password: 'secret'
        })
      );
    });

    it('does not call onSubmit when required fields are empty', async () => {
      const onSubmit = vi.fn();
      render(FormBuilder, { props: { schema: sampleSchema, onSubmit, data: {} } });
      
      const form = document.querySelector('form.dynamic-form');
      await fireEvent.submit(form!);
      
      expect(onSubmit).not.toHaveBeenCalled();
    });

    it('shows email validation error for invalid format', async () => {
      const onSubmit = vi.fn();
      render(FormBuilder, { props: { schema: sampleSchema, onSubmit, data: { username: 'john', email: 'invalid-email', password: 'secret' } } });
      
      const form = document.querySelector('form.dynamic-form');
      await fireEvent.submit(form!);
      
      expect(onSubmit).not.toHaveBeenCalled();
    });

    it('does not show error for optional empty fields', async () => {
      const onSubmit = vi.fn();
      const data = { username: 'john', email: 'john@example.com', password: 'secret', bio: '' };
      render(FormBuilder, { props: { schema: sampleSchema, onSubmit, data } });
      
      const form = document.querySelector('form.dynamic-form');
      await fireEvent.submit(form!);
      
      expect(onSubmit).toHaveBeenCalled();
      expect(screen.queryByText('Bio is required')).not.toBeInTheDocument();
    });
  });

  describe('data binding', () => {
    it('populates fields with data prop', () => {
      render(FormBuilder, { 
        props: { 
          schema: sampleSchema, 
          data: { username: 'prefilled', email: 'pre@fill.com' } 
        } 
      });
      
      expect(screen.getByLabelText('Username')).toHaveValue('prefilled');
      expect(screen.getByLabelText('Email')).toHaveValue('pre@fill.com');
    });

    it('updates field values on input', async () => {
      render(FormBuilder, { props: { schema: sampleSchema, data: {} } });
      
      const usernameInput = screen.getByLabelText('Username');
      await fireEvent.input(usernameInput, { target: { value: 'newuser' } });
      
      expect(usernameInput).toHaveValue('newuser');
    });

    it('handles multiple field types simultaneously', () => {
      render(FormBuilder, { 
        props: { 
          schema: sampleSchema, 
          data: { 
            username: 'test',
            email: 'test@test.com',
            password: 'pass123',
            bio: 'Test bio'
          } 
        } 
      });
      
      expect(screen.getByLabelText('Username')).toHaveValue('test');
      expect(screen.getByLabelText('Email')).toHaveValue('test@test.com');
      expect(screen.getByLabelText('Password')).toHaveValue('pass123');
      expect(screen.getByLabelText('Bio')).toHaveValue('Test bio');
    });
  });

  describe('edge cases', () => {
    it('handles undefined schema gracefully', () => {
      render(FormBuilder, { props: { schema: undefined as any } });
      const form = document.querySelector('form.dynamic-form');
      expect(form).toBeInTheDocument();
    });

    it('handles schema without fields', () => {
      render(FormBuilder, { props: { schema: {} } });
      const form = document.querySelector('form.dynamic-form');
      expect(form).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /submit/i })).toBeInTheDocument();
    });

    it('handles schema without submitLabel', () => {
      const schemaNoLabel = { fields: [{ name: 'test', label: 'Test', type: 'text' }] };
      render(FormBuilder, { props: { schema: schemaNoLabel } });
      expect(screen.getByRole('button', { name: /submit/i })).toBeInTheDocument();
    });

    it('handles field without placeholder (renders empty placeholder)', () => {
      const schemaNoPlaceholder = { 
        fields: [{ name: 'test', label: 'Test', type: 'text' }] 
      };
      render(FormBuilder, { props: { schema: schemaNoPlaceholder } });
      const input = screen.getByLabelText('Test');
      expect(input).toHaveAttribute('placeholder', '');
    });

    it('handles field without required (renders required="false")', () => {
      const schemaNoRequired = { 
        fields: [{ name: 'test', label: 'Test', type: 'text', required: false }] 
      };
      render(FormBuilder, { props: { schema: schemaNoRequired, data: {} } });
      const input = screen.getByLabelText('Test');
      expect(input).toHaveAttribute('required', 'false');
    });

    it('handles multiple instances independently', () => {
      const { container: c1 } = render(FormBuilder, { props: { schema: sampleSchema, data: { username: 'a' } } });
      const { container: c2 } = render(FormBuilder, { props: { schema: sampleSchema, data: { username: 'b' } } });
      
      expect(c1.querySelector('input[name="username"]')).toHaveValue('a');
      expect(c2.querySelector('input[name="username"]')).toHaveValue('b');
    });

    it('handles rapid schema changes', () => {
      const { component, rerender } = render(FormBuilder, { props: { schema: sampleSchema } });
      
      const newSchema = {
        fields: [{ name: 'newfield', label: 'New Field', type: 'text' }],
        submitLabel: 'Save'
      };
      
      rerender({ schema: newSchema });
      expect(component).toBeTruthy();
    });
  });

  describe('accessibility', () => {
    it('has proper label associations for input fields', () => {
      render(FormBuilder, { props: { schema: sampleSchema } });
      
      const usernameInput = screen.getByLabelText('Username');
      expect(usernameInput).toHaveAttribute('id', 'username');
      
      const emailInput = screen.getByLabelText('Email');
      expect(emailInput).toHaveAttribute('id', 'email');
    });

    it('has required attribute on required fields', () => {
      render(FormBuilder, { props: { schema: sampleSchema } });
      
      expect(screen.getByLabelText('Username')).toHaveAttribute('required');
      expect(screen.getByLabelText('Email')).toHaveAttribute('required');
      expect(screen.getByLabelText('Password')).toHaveAttribute('required');
    });

    it('has required="false" on optional fields', () => {
      render(FormBuilder, { props: { schema: sampleSchema } });
      
      expect(screen.getByLabelText('Bio')).toHaveAttribute('required', 'false');
    });

    it('form has submit button', () => {
      render(FormBuilder, { props: { schema: sampleSchema } });
      expect(screen.getByRole('button', { name: 'Create Account' })).toBeInTheDocument();
    });
  });
});