import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import MockCheckbox from './MockCheckbox.svelte';

describe('MockCheckbox Component', () => {
  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(MockCheckbox);
      expect(screen.getByTestId('mock-checkbox')).toBeInTheDocument();
    });

    it('renders with label', () => {
      render(MockCheckbox, { props: { label: 'I agree' } });
      expect(screen.getByLabelText('I agree')).toBeInTheDocument();
    });

    it('renders checked', () => {
      render(MockCheckbox, { props: { value: true } });
      expect(screen.getByTestId('mock-checkbox')).toBeChecked();
    });

    it('renders unchecked', () => {
      render(MockCheckbox, { props: { value: false } });
      expect(screen.getByTestId('mock-checkbox')).not.toBeChecked();
    });

    it('renders with disabled', () => {
      render(MockCheckbox, { props: { disabled: true } });
      expect(screen.getByTestId('mock-checkbox')).toBeDisabled();
    });

    it('renders with required', () => {
      render(MockCheckbox, { props: { required: true } });
      expect(screen.getByTestId('mock-checkbox')).toBeRequired();
    });

    it('renders with description', () => {
      render(MockCheckbox, { props: { description: 'I agree to terms' } });
      expect(screen.getByText('I agree to terms')).toBeInTheDocument();
    });

    it('renders with error', () => {
      render(MockCheckbox, { props: { error: 'Required' } });
      expect(screen.getByText('Required')).toBeInTheDocument();
    });
  });

  describe('Props', () => {
    it('accepts label prop', () => {
      render(MockCheckbox, { props: { label: 'Accept terms' } });
      expect(screen.getByLabelText('Accept terms')).toBeInTheDocument();
    });

    it('accepts value prop', () => {
      render(MockCheckbox, { props: { value: true } });
      expect(screen.getByTestId('mock-checkbox')).toBeChecked();
    });

    it('accepts required prop', () => {
      render(MockCheckbox, { props: { required: true } });
      expect(screen.getByTestId('mock-checkbox')).toBeRequired();
    });

    it('accepts disabled prop', () => {
      render(MockCheckbox, { props: { disabled: true } });
      expect(screen.getByTestId('mock-checkbox')).toBeDisabled();
    });

    it('accepts size prop', () => {
      render(MockCheckbox, { props: { size: 'sm' } });
      const wrapper = screen.getByTestId('mock-checkbox').closest('.checkbox-input-wrapper');
      const label = wrapper?.querySelector('label');
      expect(label).toHaveClass('checkbox-sm');
    });

    it('accepts description prop', () => {
      render(MockCheckbox, { props: { description: 'Description text' } });
      expect(screen.getByText('Description text')).toBeInTheDocument();
    });

    it('accepts error prop', () => {
      render(MockCheckbox, { props: { error: 'Error message' } });
      expect(screen.getByText('Error message')).toBeInTheDocument();
    });

    it('accepts error class', () => {
      render(MockCheckbox, { props: { error: 'Error' } });
      const wrapper = screen.getByTestId('mock-checkbox').closest('.checkbox-input-wrapper');
      const label = wrapper?.querySelector('label');
      expect(label).toHaveClass('checkbox-error');
    });

    it('accepts disabled class', () => {
      render(MockCheckbox, { props: { disabled: true } });
      const wrapper = screen.getByTestId('mock-checkbox').closest('.checkbox-input-wrapper');
      const label = wrapper?.querySelector('label');
      expect(label).toHaveClass('checkbox-disabled');
    });
  });

  describe('Error State', () => {
    it('shows error message', () => {
      render(MockCheckbox, { props: { error: 'Required' } });
      expect(screen.getByText('Required')).toBeInTheDocument();
    });

    it('applies error class to label', () => {
      render(MockCheckbox, { props: { error: 'Error' } });
      const wrapper = screen.getByTestId('mock-checkbox').closest('.checkbox-input-wrapper');
      const label = wrapper?.querySelector('label');
      expect(label).toHaveClass('checkbox-error');
    });
  });

  describe('Size Variants', () => {
    it('renders sm size', () => {
      render(MockCheckbox, { props: { size: 'sm' } });
      const wrapper = screen.getByTestId('mock-checkbox').closest('.checkbox-input-wrapper');
      const label = wrapper?.querySelector('label');
      expect(label).toHaveClass('checkbox-sm');
    });

    it('renders md size', () => {
      render(MockCheckbox, { props: { size: 'md' } });
      const wrapper = screen.getByTestId('mock-checkbox').closest('.checkbox-input-wrapper');
      const label = wrapper?.querySelector('label');
      expect(label).toHaveClass('checkbox-md');
    });

    it('renders lg size', () => {
      render(MockCheckbox, { props: { size: 'lg' } });
      const wrapper = screen.getByTestId('mock-checkbox').closest('.checkbox-input-wrapper');
      const label = wrapper?.querySelector('label');
      expect(label).toHaveClass('checkbox-lg');
    });
  });

  describe('Disabled State', () => {
    it('renders disabled checkbox', () => {
      render(MockCheckbox, { props: { disabled: true } });
      expect(screen.getByTestId('mock-checkbox')).toBeDisabled();
    });

    it('applies disabled class to label', () => {
      render(MockCheckbox, { props: { disabled: true } });
      const wrapper = screen.getByTestId('mock-checkbox').closest('.checkbox-input-wrapper');
      const label = wrapper?.querySelector('label');
      expect(label).toHaveClass('checkbox-disabled');
    });

    it.skip('does not change value when clicked', () => {
      render(MockCheckbox, { props: { disabled: true, value: false } });
      fireEvent.click(screen.getByTestId('mock-checkbox'));
      expect(screen.getByTestId('mock-checkbox')).not.toBeChecked();
    });
  });

  describe('Label and Content', () => {
    it('renders label', () => {
      render(MockCheckbox, { props: { label: 'I agree' } });
      expect(screen.getByLabelText('I agree')).toBeInTheDocument();
    });

    it('renders description', () => {
      render(MockCheckbox, { props: { description: 'Description text' } });
      expect(screen.getByText('Description text')).toBeInTheDocument();
    });

    it('renders error message', () => {
      render(MockCheckbox, { props: { error: 'Error message' } });
      expect(screen.getByText('Error message')).toBeInTheDocument();
    });

    it('shows required indicator', () => {
      render(MockCheckbox, { props: { label: 'Choose', required: true } });
      expect(screen.getByText('*')).toBeInTheDocument();
    });

    it('shows error message', () => {
      render(MockCheckbox, { props: { error: 'Required' } });
      expect(screen.getByText('Required')).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    it('handles empty label', () => {
      render(MockCheckbox, { props: { label: '' } });
      expect(screen.getByTestId('mock-checkbox')).toBeInTheDocument();
    });

    it('handles special characters in label', () => {
      render(MockCheckbox, { props: { label: 'Special: 🎉 ñáéíóú 中文' } });
      expect(screen.getByLabelText('Special: 🎉 ñáéíóú 中文')).toBeInTheDocument();
    });

    it('handles empty description', () => {
      render(MockCheckbox, { props: { description: '' } });
      expect(screen.getByTestId('mock-checkbox')).toBeInTheDocument();
    });

    it('handles unicode in label', () => {
      render(MockCheckbox, { props: { label: '模态框 🎉' } });
      expect(screen.getByLabelText('模态框 🎉')).toBeInTheDocument();
    });
  });

  describe('Interaction', () => {
    it.skip('handles keyboard interaction', async () => {
      render(MockCheckbox, { props: { value: false } });
      const checkbox = screen.getByTestId('mock-checkbox');
      
      await fireEvent.keyDown(checkbox, { key: ' ' });
      
      expect(screen.getByTestId('mock-checkbox')).toBeChecked();
    });
  });
});
