import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import MockTextarea from './MockTextarea.svelte';

describe('MockTextarea Component', () => {
  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(MockTextarea);
      expect(screen.getByTestId('mock-textarea')).toBeInTheDocument();
    });

    it('renders with label', () => {
      render(MockTextarea, { props: { label: 'Description' } });
      expect(screen.getByLabelText('Description')).toBeInTheDocument();
    });

    it('renders with placeholder', () => {
      render(MockTextarea, { props: { placeholder: 'Enter description' } });
      expect(screen.getByPlaceholderText('Enter description')).toBeInTheDocument();
    });

    it('renders with value', () => {
      render(MockTextarea, { props: { value: 'Initial text' } });
      expect(screen.getByTestId('mock-textarea')).toHaveValue('Initial text');
    });

    it('renders with disabled', () => {
      render(MockTextarea, { props: { disabled: true } });
      expect(screen.getByTestId('mock-textarea')).toBeDisabled();
    });

    it('renders with readonly', () => {
      render(MockTextarea, { props: { readonly: true } });
      expect(screen.getByTestId('mock-textarea')).toHaveAttribute('readonly');
    });

    it('renders with rows', () => {
      render(MockTextarea, { props: { rows: 5 } });
      expect(screen.getByTestId('mock-textarea')).toHaveAttribute('rows', '5');
    });

    it('renders with name', () => {
      render(MockTextarea, { props: { name: 'description' } });
      expect(screen.getByTestId('mock-textarea')).toHaveAttribute('name', 'description');
    });
  });

  describe('Props', () => {
    it('accepts label prop', () => {
      render(MockTextarea, { props: { label: 'Description' } });
      expect(screen.getByLabelText('Description')).toBeInTheDocument();
    });

    it('accepts placeholder prop', () => {
      render(MockTextarea, { props: { placeholder: 'Enter text' } });
      expect(screen.getByPlaceholderText('Enter text')).toBeInTheDocument();
    });

    it('accepts value prop', () => {
      render(MockTextarea, { props: { value: 'Test content' } });
      expect(screen.getByTestId('mock-textarea')).toHaveValue('Test content');
    });

    it('accepts rows prop', () => {
      render(MockTextarea, { props: { rows: 10 } });
      expect(screen.getByTestId('mock-textarea')).toHaveAttribute('rows', '10');
    });

    it('accepts disabled prop', () => {
      render(MockTextarea, { props: { disabled: true } });
      expect(screen.getByTestId('mock-textarea')).toBeDisabled();
    });

    it('accepts readonly prop', () => {
      render(MockTextarea, { props: { readonly: true } });
      expect(screen.getByTestId('mock-textarea')).toHaveAttribute('readonly');
    });

    it('accepts name prop', () => {
      render(MockTextarea, { props: { name: 'bio' } });
      expect(screen.getByTestId('mock-textarea')).toHaveAttribute('name', 'bio');
    });

    it('accepts id prop', () => {
      render(MockTextarea, { props: { id: 'custom-id' } });
      expect(screen.getByTestId('mock-textarea')).toHaveAttribute('id', 'custom-id');
    });

    it('accepts className prop', () => {
      render(MockTextarea, { props: { className: 'custom-class' } });
      expect(screen.getByTestId('mock-textarea')).toHaveClass('custom-class');
    });

    it('accepts style prop', () => {
      render(MockTextarea, { props: { style: 'border: 1px solid red' } });
      expect(screen.getByTestId('mock-textarea')).toHaveAttribute('style');
    });
  });

  describe('Events', () => {
    it('calls oninput handler', async () => {
      const handleInput = vi.fn();
      render(MockTextarea, { props: { oninput: handleInput } });
      
      await fireEvent.input(screen.getByTestId('mock-textarea'), { target: { value: 'test' } });
      
      expect(handleInput).toHaveBeenCalled();
    });

    it('calls onchange handler', async () => {
      const handleChange = vi.fn();
      render(MockTextarea, { props: { onchange: handleChange } });
      
      await fireEvent.change(screen.getByTestId('mock-textarea'), { target: { value: 'test' } });
      
      expect(handleChange).toHaveBeenCalled();
    });

    it('calls onblur handler', async () => {
      const handleBlur = vi.fn();
      render(MockTextarea, { props: { onblur: handleBlur } });
      
      await fireEvent.blur(screen.getByTestId('mock-textarea'));
      
      expect(handleBlur).toHaveBeenCalled();
    });

    it('calls onfocus handler', async () => {
      const handleFocus = vi.fn();
      render(MockTextarea, { props: { onfocus: handleFocus } });
      
      await fireEvent.focus(screen.getByTestId('mock-textarea'));
      
      expect(handleFocus).toHaveBeenCalled();
    });
  });

  describe('Accessibility', () => {
    it('has proper label association', () => {
      render(MockTextarea, { props: { label: 'Description', id: 'desc' } });
      const textarea = screen.getByTestId('mock-textarea');
      const label = screen.getByText('Description');
      expect(textarea).toHaveAttribute('id', 'desc');
      expect(label).toHaveAttribute('for', 'desc');
    });

    it('has required indicator when required', () => {
      render(MockTextarea, { props: { label: 'Required', required: true } });
      expect(screen.getByText('*')).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    it('handles empty value', () => {
      render(MockTextarea, { props: { value: '' } });
      expect(screen.getByTestId('mock-textarea')).toHaveValue('');
    });

    it('handles special characters', () => {
      render(MockTextarea, { props: { value: 'Special: 🎉 ñáéíóú 中文' } });
      expect(screen.getByTestId('mock-textarea')).toHaveValue('Special: 🎉 ñáéíóú 中文');
    });

    it('handles very long text', () => {
      const longText = 'x'.repeat(10000);
      render(MockTextarea, { props: { value: longText } });
      expect(screen.getByTestId('mock-textarea')).toHaveValue(longText);
    });

    it('handles multiline text', () => {
      const multiline = 'Line 1\nLine 2\nLine 3';
      render(MockTextarea, { props: { value: multiline } });
      expect(screen.getByTestId('mock-textarea')).toHaveValue(multiline);
    });

    it('handles newlines', () => {
      render(MockTextarea, { props: { value: 'Line 1\nLine 2' } });
      expect(screen.getByTestId('mock-textarea')).toHaveValue('Line 1\nLine 2');
    });
  });

  describe('Disabled State', () => {
    it('disables textarea when disabled', () => {
      render(MockTextarea, { props: { disabled: true } });
      expect(screen.getByTestId('mock-textarea')).toBeDisabled();
    });
  });

  describe('Readonly State', () => {
    it('sets readonly attribute', () => {
      render(MockTextarea, { props: { readonly: true } });
      expect(screen.getByTestId('mock-textarea')).toHaveAttribute('readonly');
    });
  });

  describe('Events', () => {
    it('calls oninput handler', async () => {
      const handleInput = vi.fn();
      render(MockTextarea, { props: { oninput: handleInput } });
      
      await fireEvent.input(screen.getByTestId('mock-textarea'), { target: { value: 'test' } });
      
      expect(handleInput).toHaveBeenCalled();
    });

    it('calls onchange handler', async () => {
      const handleChange = vi.fn();
      render(MockTextarea, { props: { onchange: handleChange } });
      
      await fireEvent.change(screen.getByTestId('mock-textarea'), { target: { value: 'test' } });
      
      expect(handleChange).toHaveBeenCalled();
    });
  });
});