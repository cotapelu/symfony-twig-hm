import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, cleanup } from '@testing-library/svelte';
import TransactionItem from './TransactionItem.svelte';

/**
 * Pure presentational component. Covers:
 * compact vs full view, type→icon/color/bg/sign, status color,
 * currency formatting + fallback, optional {@link Transaction} fields.
 */
function makeTx(overrides = {}) {
  return {
    id: 't1',
    walletId: 'w1',
    type: 'deposit',
    amount: 100.5, // <1000 avoids locale thousands-grouping ambiguity
    currency: 'USD',
    status: 'completed',
    date: '2026-08-11T09:30:00.000Z',
    ...overrides,
  };
}

afterEach(() => cleanup());

describe('TransactionItem', () => {
  describe('currency formatting', () => {
    it('formats a USD amount with the currency symbol', () => {
      render(TransactionItem, { props: { transaction: makeTx() } });
      expect(screen.getByText(/100\.50/)).toBeInTheDocument();
      expect(screen.getByText(/100\.50/)).toHaveTextContent(/\$/);
    });

    it("falls back to USD when currency is empty", () => {
      render(TransactionItem, {
        props: { transaction: makeTx({ currency: '' }) },
      });
      // Empty currency -> Intl default to USD symbol
      expect(screen.getByText(/100\.50/)).toHaveTextContent(/\$/);
    });

    it('formats a non-USD currency', () => {
      render(TransactionItem, {
        props: { transaction: makeTx({ currency: 'EUR' }) },
      });
      expect(screen.getByText(/100\.50/)).toHaveTextContent(/€/);
    });
  });

  describe('type → icon / sign / color / bg', () => {
    it('renders a deposit icon and positive sign + green styling', () => {
      render(TransactionItem, { props: { transaction: makeTx({ type: 'deposit' }) } });
      expect(screen.getByText('⬇️')).toHaveClass('bg-green-50');
      const amount = screen.getByText(/100\.50/);
      expect(amount).toHaveTextContent(/\+\$100\.50/); // sign + symbol + amount
      expect(amount).toHaveClass('text-green-600');
    });

    it('renders a withdraw icon and negative sign + red styling', () => {
      render(TransactionItem, {
        props: { transaction: makeTx({ type: 'withdraw', amount: 75 }) },
      });
      expect(screen.getByText('⬆️')).toHaveClass('bg-red-50');
      const amount = screen.getByText(/75\.00/);
      expect(amount).toHaveTextContent(/-\$75\.00/); // sign + symbol + amount
      expect(amount).toHaveClass('text-red-600');
    });

    it('renders each known type icon', () => {
      const map = {
        deposit: '⬇️', withdraw: '⬆️', transfer_in: '↙️', transfer_out: '↗️',
        payment: '💳', refund: '↩️', fee: '💸',
      };
      for (const [type, icon] of Object.entries(map)) {
        const { unmount } = render(TransactionItem, {
          props: { transaction: makeTx({ type }) },
        });
        expect(screen.getByText(icon)).toBeInTheDocument();
        unmount();
      }
    });

    it('applies no sign + gray styling for an unknown type (default branch)', () => {
      render(TransactionItem, {
        // deliberately out-of-union to hit the default switch branch
        props: { transaction: makeTx({ type: 'cashback' }) },
      });
      expect(screen.getByText('💰')).toHaveClass('bg-gray-50');
      const amount = screen.getByText(/100\.50/);
      expect(amount.textContent).not.toMatch(/^[+-]/); // no +/- sign for unknown type
      expect(amount).toHaveTextContent(/\$100\.50/);
      expect(amount).toHaveClass('text-gray-600');
    });

    it('displays the type label with underscores replaced by spaces', () => {
      render(TransactionItem, {
        props: { transaction: makeTx({ type: 'transfer_in' }) },
      });
      expect(screen.getByText('transfer in')).toBeInTheDocument();
    });
  });

  describe('status badge colors', () => {
    it('completed → green', () => {
      render(TransactionItem, { props: { transaction: makeTx({ status: 'completed' }) } });
      expect(screen.getByText('completed')).toHaveClass('text-green-800');
    });

    it('pending → yellow', () => {
      render(TransactionItem, { props: { transaction: makeTx({ status: 'pending' }) } });
      expect(screen.getByText('pending')).toHaveClass('text-yellow-800');
    });

    it('failed → red', () => {
      render(TransactionItem, { props: { transaction: makeTx({ status: 'failed' }) } });
      expect(screen.getByText('failed')).toHaveClass('text-red-800');
    });
  });

  describe('compact vs full view', () => {
    it('renders description only in full view', () => {
      const { unmount } = render(TransactionItem, {
        props: { transaction: makeTx({ description: 'Monthly top-up' }) },
      });
      expect(screen.getByText('Monthly top-up')).toBeInTheDocument();
      unmount();
      cleanup();
      render(TransactionItem, {
        props: { transaction: makeTx({ description: 'Monthly top-up' }), compact: true },
      });
      expect(screen.queryByText('Monthly top-up')).not.toBeInTheDocument();
    });

    it('renders recipient/sender labels in full view when provided', () => {
      render(TransactionItem, {
        props: { transaction: makeTx({ recipientId: 'acc-9', senderId: 'acc-1' }) },
      });
      expect(screen.getByText(/To: acc-9/)).toBeInTheDocument();
      expect(screen.getByText(/From: acc-1/)).toBeInTheDocument();
    });

    it('omits recipient/sender labels in full view when absent', () => {
      render(TransactionItem, { props: { transaction: makeTx() } });
      expect(screen.queryByText(/To: /)).not.toBeInTheDocument();
      expect(screen.queryByText(/From: /)).not.toBeInTheDocument();
    });

    it('renders amount + status in compact view', () => {
      render(TransactionItem, {
        props: { transaction: makeTx({ amount: 42 }), compact: true },
      });
      expect(screen.getByText(/42\.00/)).toBeInTheDocument();
      expect(screen.getByText('completed')).toBeInTheDocument();
    });
  });
});
