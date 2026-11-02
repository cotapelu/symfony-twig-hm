import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import MockInput from './MockInput.svelte';

describe('MockInput Component', () => {
  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(MockInput);
      expect(screen.getByTestId('mock-input')).toBeInTheDocument();
    });

    it('renders with label', () => {
      render(MockInput, { props: { label: 'Email' } });
      expect(screen.getByLabelText('Email')).toBeInTheDocument();
    });

    it('renders with placeholder', () => {
      render(MockInput, { props: { placeholder: 'Enter email' } });
      expect(screen.getByPlaceholderText('Enter email')).toBeInTheDocument();
    });

    it('renders with value', () => {
      render(MockInput, { props: { value: 'test@example.com' } });
      expect(screen.getByTestId('mock-input')).toHaveValue('test@example.com');
    });

    it('renders with type', () => {
      render(MockInput, { props: { type: 'email' } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('type', 'email');
    });

    it('renders with required', () => {
      render(MockInput, { props: { required: true } });
      expect(screen.getByTestId('mock-input')).toBeRequired();
    });

    it('renders with disabled', () => {
      render(MockInput, { props: { disabled: true } });
      expect(screen.getByTestId('mock-input')).toBeDisabled();
    });

    it('renders with readonly', () => {
      render(MockInput, { props: { readonly: true } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('readonly');
    });

    it('renders with name', () => {
      render(MockInput, { props: { name: 'email' } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('name', 'email');
    });

    it('renders with id', () => {
      render(MockInput, { props: { id: 'custom-id' } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('id', 'custom-id');
    });
  });

  describe('Props', () => {
    it('accepts label prop', () => {
      render(MockInput, { props: { label: 'Username' } });
      expect(screen.getByLabelText('Username')).toBeInTheDocument();
    });

    it('accepts type prop', () => {
      render(MockInput, { props: { type: 'password' } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('type', 'password');
    });

    it('accepts placeholder prop', () => {
      render(MockInput, { props: { placeholder: 'Enter text' } });
      expect(screen.getByPlaceholderText('Enter text')).toBeInTheDocument();
    });

    it('accepts value prop', () => {
      render(MockInput, { props: { value: 'test' } });
      expect(screen.getByTestId('mock-input')).toHaveValue('test');
    });

    it('accepts required prop', () => {
      render(MockInput, { props: { required: true } });
      expect(screen.getByTestId('mock-input')).toBeRequired();
    });

    it('accepts disabled prop', () => {
      render(MockInput, { props: { disabled: true } });
      expect(screen.getByTestId('mock-input')).toBeDisabled();
    });

    it('accepts readonly prop', () => {
      render(MockInput, { props: { readonly: true } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('readonly');
    });

    it('accepts name prop', () => {
      render(MockInput, { props: { name: 'username' } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('name', 'username');
    });

    it('accepts size prop', () => {
      render(MockInput, { props: { size: 'sm' } });
      expect(screen.getByTestId('mock-input')).toHaveClass('input-sm');
    });

    it('accepts variant prop', () => {
      render(MockInput, { props: { variant: 'error' } });
      expect(screen.getByTestId('mock-input')).toHaveClass('input-error');
    });

    it('accepts error prop', () => {
      render(MockInput, { props: { error: 'Invalid email' } });
      expect(screen.getByText('Invalid email')).toBeInTheDocument();
    });

    it('accepts className prop', () => {
      render(MockInput, { props: { className: 'custom-class' } });
      expect(screen.getByTestId('mock-input')).toHaveClass('custom-class');
    });

    it('accepts id prop', () => {
      render(MockInput, { props: { id: 'custom-id' } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('id', 'custom-id');
    });
  });

  describe('Events', () => {
    it.skip('dispatches input event on input', async () => {
      const dispatchSpy = vi.fn();
      const { component } = render(MockInput);
      component.$on('input', dispatchSpy);
      
      await fireEvent.input(screen.getByTestId('mock-input'), { target: { value: 'test' } });
      
      expect(dispatchSpy).toHaveBeenCalledWith(expect.objectContaining({
        detail: 'test'
      }));
    });

    it('calls oninput handler', async () => {
      const handleInput = vi.fn();
      render(MockInput, { props: { oninput: handleInput } });
      
      await fireEvent.input(screen.getByTestId('mock-input'), { target: { value: 'test' } });
      
      expect(handleInput).toHaveBeenCalled();
    });

    it('calls onchange handler', async () => {
      const handleChange = vi.fn();
      render(MockInput, { props: { onchange: handleChange } });
      
      await fireEvent.change(screen.getByTestId('mock-input'), { target: { value: 'test' } });
      
      expect(handleChange).toHaveBeenCalled();
    });

    it('calls onblur handler', async () => {
      const handleBlur = vi.fn();
      render(MockInput, { props: { onblur: handleBlur } });
      
      await fireEvent.blur(screen.getByTestId('mock-input'));
      
      expect(handleBlur).toHaveBeenCalled();
    });

    it('calls onfocus handler', async () => {
      const handleFocus = vi.fn();
      render(MockInput, { props: { onfocus: handleFocus } });
      
      await fireEvent.focus(screen.getByTestId('mock-input'));
      
      expect(handleFocus).toHaveBeenCalled();
    });
  });

  describe('Accessibility', () => {
    it('has proper data attributes', () => {
      render(MockInput);
      expect(screen.getByTestId('mock-input')).toBeInTheDocument();
    });

    it('has proper label association', () => {
      render(MockInput, { props: { label: 'Email', id: 'email' } });
      const input = screen.getByTestId('mock-input');
      const label = screen.getByText('Email');
      expect(input).toHaveAttribute('id', 'email');
      expect(label).toHaveAttribute('for', 'email');
    });

    it('has required indicator when required', () => {
      render(MockInput, { props: { label: 'Email', required: true } });
      expect(screen.getByText('*')).toBeInTheDocument();
    });

    it('has aria-invalid when error', () => {
      render(MockInput, { props: { error: 'Invalid' } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('aria-invalid', 'true');
    });

    it('has proper aria-describedby for error', () => {
      render(MockInput, { props: { error: 'Invalid', id: 'test-id' } });
      const input = screen.getByTestId('mock-input');
      expect(input).toHaveAttribute('aria-describedby', 'test-id-description');
    });
  });

  describe('Error State', () => {
    it('shows error message', () => {
      render(MockInput, { props: { error: 'Invalid email' } });
      expect(screen.getByText('Invalid email')).toBeInTheDocument();
    });

    it('applies error class', () => {
      render(MockInput, { props: { error: 'Invalid' } });
      expect(screen.getByTestId('mock-input')).toHaveClass('input-error');
    });
  });

  describe('Edge Cases', () => {
    it('handles empty value', () => {
      render(MockInput, { props: { value: '' } });
      expect(screen.getByTestId('mock-input')).toHaveValue('');
    });

    it('handles special characters', () => {
      render(MockInput, { props: { value: 'test@domain.com' } });
      expect(screen.getByTestId('mock-input')).toHaveValue('test@domain.com');
    });

    it('handles unicode characters', () => {
      render(MockInput, { props: { value: '测试' } });
      expect(screen.getByTestId('mock-input')).toHaveValue('测试');
    });

    it('handles long value', () => {
      const longValue = 'x'.repeat(1000);
      render(MockInput, { props: { value: longValue } });
      expect(screen.getByTestId('mock-input')).toHaveValue(longValue);
    });
  });

  describe('Size Variants', () => {
    it('renders sm size', () => {
      render(MockInput, { props: { size: 'sm' } });
      expect(screen.getByTestId('mock-input')).toHaveClass('input-sm');
    });

    it('renders lg size', () => {
      render(MockInput, { props: { size: 'lg' } });
      expect(screen.getByTestId('mock-input')).toHaveClass('input-lg');
    });
  });

  describe('Variant Styles', () => {
    it('applies default variant', () => {
      render(MockInput, { props: { variant: 'default' } });
      expect(screen.getByTestId('mock-input')).toHaveClass('input-default');
    });

    it('applies error variant', () => {
      render(MockInput, { props: { variant: 'error' } });
      expect(screen.getByTestId('mock-input')).toHaveClass('input-error');
    });
  });

  describe('Disabled State', () => {
    it('disables input when disabled', () => {
      render(MockInput, { props: { disabled: true } });
      expect(screen.getByTestId('mock-input')).toBeDisabled();
    });

    it('applies disabled styles', () => {
      render(MockInput, { props: { disabled: true } });
      expect(screen.getByTestId('mock-input')).toHaveClass('input-disabled');
    });
  });

  describe('Readonly State', () => {
    it('sets readonly attribute', () => {
      render(MockInput, { props: { readonly: true } });
      expect(screen.getByTestId('mock-input')).toHaveAttribute('readonly');
    });
  });
});