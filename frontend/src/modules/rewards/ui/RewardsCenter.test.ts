import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import RewardsCenter from './RewardsCenter.svelte';

describe('RewardsCenter Component', () => {
  it('renders the rewards center header', () => {
    render(RewardsCenter);
    expect(screen.getByText(/Trung tâm phần thưởng/i)).toBeTruthy();
  });

  it('displays the user points with proper formatting', () => {
    render(RewardsCenter);
    expect(screen.getByText('2,500 ⭐')).toBeTruthy();
  });

  it('shows points card heading', () => {
    render(RewardsCenter);
    expect(screen.getByText('Điểm của bạn')).toBeTruthy();
  });

  it('renders badges section with all badges', () => {
    render(RewardsCenter);
    expect(screen.getByText('Huy hiệu')).toBeTruthy();
    expect(screen.getByText('Ngườí dùng mới')).toBeTruthy();
    expect(screen.getByText('VIP Member')).toBeTruthy();
    expect(screen.getByText('Super Active')).toBeTruthy();
  });

  it('applies earned class to earned badges', () => {
    render(RewardsCenter);
    const earnedBadges = screen.getAllByText(/Ngườí dùng mới|VIP Member/).forEach(el => {
      const badgeItem = el.closest('.badge-item');
      expect(badgeItem).toHaveClass('earned');
    });
  });

  it('applies locked class to unearned badge', () => {
    render(RewardsCenter);
    const lockedBadge = screen.getByText('Super Active').closest('.badge-item');
    expect(lockedBadge).toHaveClass('locked');
  });

  it('renders rewards section with all rewards', () => {
    render(RewardsCenter);
    expect(screen.getByText('Đổi quà')).toBeTruthy();
    expect(screen.getByText('Voucher 100k')).toBeTruthy();
    expect(screen.getByText('Premium 1 tháng')).toBeTruthy();
    expect(screen.getByText('Quà tặng đặc biệt')).toBeTruthy();
  });

  it('enables button for available reward with sufficient points', () => {
    render(RewardsCenter);
    const voucherButton = screen.getByRole('button', { name: '1,000 điểm' });
    expect(voucherButton.disabled).toBe(false);
  });

  it('enables button for available reward with sufficient points (premium)', () => {
    render(RewardsCenter);
    const premiumButton = screen.getByRole('button', { name: '2,000 điểm' });
    expect(premiumButton.disabled).toBe(false);
  });

  it('disables button for unavailable reward', () => {
    render(RewardsCenter);
    const specialButton = screen.getByRole('button', { name: '5,000 điểm' });
    expect(specialButton.disabled).toBe(true);
  });
});
