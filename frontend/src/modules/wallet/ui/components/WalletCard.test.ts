import { describe, it, expect, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, cleanup } from '@testing-library/svelte';
import WalletCard from './WalletCard.svelte';

// Minimal Wallet shape (component only reads balance, currency, status)
const makeWallet = (over: Partial<any> = {}) => ({
  id: 'w1', userId: 1, balance: 1500.5, currency: 'USD', status: 'active', ...over
});

describe('WalletCard', () => {
  afterEach(() => cleanup());

  describe('rendering', () => {
    it('renders the Available Balance label', () => {
      render(WalletCard, { props: { wallet: makeWallet() } });
      expect(screen.getByText('Available Balance')).toBeInTheDocument();
    });

    it('formats the balance as currency', () => {
      render(WalletCard, { props: { wallet: makeWallet({ balance: 1500.5, currency: 'USD' }) } });
      expect(screen.getByText('$1,500.50')).toBeInTheDocument();
    });

    it('formats EUR balance using EUR formatting', () => {
      render(WalletCard, { props: { wallet: makeWallet({ balance: 1000, currency: 'EUR' }) } });
      expect(screen.getByText(/1,000/)).toBeInTheDocument();
      expect(screen.queryByText('USD')).not.toBeInTheDocument();
    });

    it('shows currency and status badges', () => {
      render(WalletCard, { props: { wallet: makeWallet({ currency: 'GBP', status: 'frozen' }) } });
      expect(screen.getByText('GBP')).toBeInTheDocument();
      expect(screen.getByText('frozen')).toBeInTheDocument();
    });

    it('renders Deposit, Withdraw and Transfer buttons', () => {
      render(WalletCard, { props: { wallet: makeWallet() } });
      expect(screen.getByText('Deposit')).toBeInTheDocument();
      expect(screen.getByText('Withdraw')).toBeInTheDocument();
      expect(screen.getByText('Transfer')).toBeInTheDocument();
    });
  });

  describe('getCardGradient', () => {
    it('applies USD gradient for USD', () => {
      render(WalletCard, { props: { wallet: makeWallet({ currency: 'USD' }) } });
      expect(document.querySelector('.from-blue-600.to-blue-800')).toBeInTheDocument();
    });
    it('applies EUR gradient for EUR', () => {
      render(WalletCard, { props: { wallet: makeWallet({ currency: 'EUR' }) } });
      expect(document.querySelector('.from-blue-500.to-indigo-600')).toBeInTheDocument();
    });
    it('applies GBP gradient for GBP', () => {
      render(WalletCard, { props: { wallet: makeWallet({ currency: 'GBP' }) } });
      expect(document.querySelector('.from-purple-600.to-purple-800')).toBeInTheDocument();
    });
    it('falls back to default gradient for unknown currency', () => {
      render(WalletCard, { props: { wallet: makeWallet({ currency: 'XYZ' }) } });
      expect(document.querySelector('.from-blue-600.to-purple-700')).toBeInTheDocument();
    });
  });

  describe('action callbacks', () => {
    it('invokes onDeposit when Deposit clicked', () => {
      const onDeposit = vi.fn();
      render(WalletCard, { props: { wallet: makeWallet(), onDeposit } });
      fireEvent.click(screen.getByText('Deposit'));
      expect(onDeposit).toHaveBeenCalledTimes(1);
    });
    it('invokes onWithdraw when Withdraw clicked', () => {
      const onWithdraw = vi.fn();
      render(WalletCard, { props: { wallet: makeWallet(), onWithdraw } });
      fireEvent.click(screen.getByText('Withdraw'));
      expect(onWithdraw).toHaveBeenCalledTimes(1);
    });
    it('invokes onTransfer when Transfer clicked', () => {
      const onTransfer = vi.fn();
      render(WalletCard, { props: { wallet: makeWallet(), onTransfer } });
      fireEvent.click(screen.getByText('Transfer'));
      expect(onTransfer).toHaveBeenCalledTimes(1);
    });
    it('does not throw when callbacks are omitted (optional chaining)', () => {
      render(WalletCard, { props: { wallet: makeWallet() } });
      expect(() => {
        fireEvent.click(screen.getByText('Deposit'));
        fireEvent.click(screen.getByText('Withdraw'));
        fireEvent.click(screen.getByText('Transfer'));
      }).not.toThrow();
    });
  });
});
