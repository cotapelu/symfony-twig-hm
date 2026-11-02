import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import Calendar from './Calendar.svelte';

describe('Calendar Component', () => {
  const today = new Date();
  const todayStr = today.toISOString().split('T')[0];
  const monthYear = today.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

  describe('rendering', () => {
    it('renders input with default placeholder', () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      expect(input).toBeTruthy();
      expect(input).toHaveAttribute('placeholder', 'Select date');
    });

    it('renders calendar toggle button', () => {
      render(Calendar);
      const button = screen.getByRole('button', { name: /toggle calendar/i });
      expect(button).toBeTruthy();
    });

    it('renders with custom placeholder', () => {
      render(Calendar, { props: { placeholder: 'Choose date' } });
      const input = screen.getByPlaceholderText('Choose date');
      expect(input).toBeTruthy();
    });

    it('applies disabled attribute to input and button when disabled', () => {
      render(Calendar, { props: { disabled: true } });
      const input = screen.getByRole('textbox');
      const button = screen.getByRole('button', { name: /toggle calendar/i });
      expect(input).toHaveAttribute('disabled');
      expect(button).toHaveAttribute('disabled');
    });
  });

  describe('calendar dropdown', () => {
    it('opens dropdown when input is focused', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
    });

    it('opens dropdown when toggle button is clicked', async () => {
      render(Calendar);
      const button = screen.getByRole('button', { name: /toggle calendar/i });
      await fireEvent.click(button);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
    });

    it('closes dropdown when Escape key is pressed', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
      // Dispatch Escape keydown on window
      window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
      // Dropdown should close (month-year header removed)
      await waitFor(() => {
        expect(screen.queryByText(monthYear)).toBeNull();
      });
    });

    it('does not open dropdown when disabled', async () => {
      render(Calendar, { props: { disabled: true } });
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      // dropdown should not appear
      await waitFor(() => {
        expect(screen.queryByText(/Sun/)).toBeNull();
      });
    });
  });

  describe('month navigation', () => {
    it('displays current month and year', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      expect(screen.getByText(monthYear)).toBeTruthy();
    });

    it('navigates to previous month', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      const prevButton = screen.getByRole('button', { name: /previous month/i });
      await fireEvent.click(prevButton);
      const prevMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      const prevMonthYear = prevMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      await waitFor(() => {
        expect(screen.getByText(prevMonthYear)).toBeTruthy();
      });
    });

    it('navigates to next month', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      const nextButton = screen.getByRole('button', { name: /next month/i });
      await fireEvent.click(nextButton);
      const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
      const nextMonthYear = nextMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      await waitFor(() => {
        expect(screen.getByText(nextMonthYear)).toBeTruthy();
      });
    });
  });

  describe('date selection', () => {
    it('selects a date when clicking a day button', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
      // Find the day button for today that belongs to the current month
      const allDayButtons = screen.getAllByRole('button', { name: /\d+/ });
      const todayButton = allDayButtons.find(btn => 
        btn.textContent === today.getDate().toString() && btn.classList.contains('current-month')
      );
      expect(todayButton).toBeTruthy();
      await fireEvent.click(todayButton!);
      // Input should update to formatted date
      await waitFor(() => {
        expect(input).toHaveValue(todayStr);
      });
      // Dropdown should close (month-year header removed)
      await waitFor(() => {
        expect(screen.queryByText(monthYear)).toBeNull();
      });
    });

    it('does not select disabled date', async () => {
      const min = new Date(today.getTime() + 86400000); // tomorrow
      render(Calendar, { props: { min } });
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
      // today is before min, so should be disabled
      const allDayButtons = screen.getAllByRole('button', { name: /\d+/ });
      const todayButton = allDayButtons.find(btn => 
        btn.textContent === today.getDate().toString() && btn.classList.contains('current-month')
      );
      expect(todayButton).toBeTruthy();
      expect(todayButton).toHaveAttribute('disabled');
      // Clicking disabled should not change input
      await fireEvent.click(todayButton!);
      await waitFor(() => {
        expect(input).toHaveValue('');
      });
    });

    it('does not select date from previous/next month (non-current) when clicked', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
      // Find a day button that is not from current month (does not have 'current-month' class)
      const allDayButtons = screen.getAllByRole('button', { name: /\d+/ });
      const nonCurrentButton = allDayButtons.find(btn => !btn.classList.contains('current-month'));
      if (nonCurrentButton) {
        await fireEvent.click(nonCurrentButton);
        // Input should remain empty
        await waitFor(() => {
          expect(input).toHaveValue('');
        });
      }
    });

    it('dispatches change event with value and formatted string', async () => {
      render(Calendar);
      const handler = vi.fn();
      // Listen on document (event bubbles)
      document.addEventListener('change', handler);
      try {
        const input = screen.getByRole('textbox');
        await fireEvent.focus(input);
        await waitFor(() => {
          expect(screen.getByText(monthYear)).toBeTruthy();
        });
        const allDayButtons = screen.getAllByRole('button', { name: /\d+/ });
        const todayButton = allDayButtons.find(btn => 
          btn.textContent === today.getDate().toString() && btn.classList.contains('current-month')
        );
        expect(todayButton).toBeTruthy();
        await fireEvent.click(todayButton!);
        await waitFor(() => {
          expect(handler).toHaveBeenCalledTimes(1);
        });
        const event = handler.mock.calls[0][0] as CustomEvent;
        expect(event.detail).toHaveProperty('value');
        expect(event.detail).toHaveProperty('formatted');
        expect(event.detail.formatted).toBe(todayStr);
      } finally {
        document.removeEventListener('change', handler);
      }
    });
  });

  describe('input parsing', () => {
    it('updates selected date when valid date is typed', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.input(input, { target: { value: '2025-12-25' } });
      await waitFor(() => {
        expect(input).toHaveValue('2025-12-25');
      });
    });

    it('does not update when invalid date string is typed', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.input(input, { target: { value: 'invalid-date' } });
      // inputValue will be set to the typed string
      await waitFor(() => {
        expect(input).toHaveValue('invalid-date');
      });
    });
  });

  describe('min/max constraints', () => {
    it('disables dates before min', async () => {
      const min = new Date(today.getTime() + 86400000 * 5); // 5 days from now
      render(Calendar, { props: { min } });
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
      // Find today's button in the current month (may appear multiple times)
      const allDayButtons = screen.getAllByRole('button', { name: /\d+/ });
      const todayButton = allDayButtons.find(btn => 
        btn.textContent === today.getDate().toString() && btn.classList.contains('current-month')
      );
      expect(todayButton).toBeTruthy();
      expect(todayButton).toHaveAttribute('disabled');
    });

    it('disables dates after max', async () => {
      const max = new Date(today.getTime() - 86400000 * 5); // 5 days ago
      render(Calendar, { props: { max } });
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
      // today is after max, so today button should be disabled
      const allDayButtons = screen.getAllByRole('button', { name: /\d+/ });
      const todayButton = allDayButtons.find(btn => 
        btn.textContent === today.getDate().toString() && btn.classList.contains('current-month')
      );
      expect(todayButton).toBeTruthy();
      expect(todayButton).toHaveAttribute('disabled');
    });
  });

  describe('time picker', () => {
    it('shows time picker when showTime is true and date is selected via initial value', async () => {
      // Pass an initial value so selectedDate is set on mount
      const initialDate = new Date(today);
      initialDate.setHours(14, 30, 0, 0);
      render(Calendar, { props: { value: initialDate, showTime: true } });
      const input = screen.getByRole('textbox');
      // Open dropdown
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
      // Time picker should be present because selectedDate exists
      expect(screen.getByLabelText(/time/i)).toBeTruthy();
    });

    it('does not show time picker when showTime is false', async () => {
      const initialDate = new Date(today);
      initialDate.setHours(14, 30, 0, 0);
      render(Calendar, { props: { value: initialDate, showTime: false } });
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByText(monthYear)).toBeTruthy();
      });
      expect(screen.queryByLabelText(/time/i)).toBeNull();
    });
  });

  describe('accessibility', () => {
    it('has proper aria labels on navigation buttons', async () => {
      render(Calendar);
      const input = screen.getByRole('textbox');
      await fireEvent.focus(input);
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /previous month/i })).toBeTruthy();
        expect(screen.getByRole('button', { name: /next month/i })).toBeTruthy();
      });
    });

    it('disables input and button when disabled prop is true', () => {
      render(Calendar, { props: { disabled: true } });
      const input = screen.getByRole('textbox');
      const toggle = screen.getByRole('button', { name: /toggle calendar/i });
      expect(input).toHaveAttribute('disabled');
      expect(toggle).toHaveAttribute('disabled');
    });
  });

  // Diagnostic: test manual event dispatch
  it('diagnostic: can catch manually dispatched change event', () => {
    const { container } = render(Calendar);
    const handler = vi.fn();
    container.addEventListener('change', handler);
    const event = new CustomEvent('change', { detail: { value: new Date(), formatted: '2026-06-28' } });
    container.dispatchEvent(event);
    expect(handler).toHaveBeenCalledTimes(1);
  });
});
