import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Form from './Form.svelte';

describe('Form Component', () => {
  describe('Rendering', () => {
    it('renders submit and reset buttons with default labels', () => {
      render(Form);
      expect(screen.getByRole('button', { name: 'Submit' })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'Reset' })).toBeInTheDocument();
    });

    it('renders custom submit and reset labels', () => {
      render(Form, { props: { submitLabel: 'Save', resetLabel: 'Clear' } });
      expect(screen.getByRole('button', { name: 'Save' })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'Clear' })).toBeInTheDocument();
    });

    it('hides reset button when showReset=false', () => {
      render(Form, { props: { showReset: false } });
      expect(screen.queryByRole('button', { name: 'Reset' })).not.toBeInTheDocument();
    });

    it('applies disabled class when disabled', () => {
      const { container } = render(Form, { props: { disabled: true } });
      expect(container.firstChild).toHaveClass('disabled');
    });

    it('applies loading class when loading', () => {
      const { container } = render(Form, { props: { loading: true } });
      expect(container.firstChild).toHaveClass('loading');
    });

    // Slot content rendering skipped for now.
  });
});
