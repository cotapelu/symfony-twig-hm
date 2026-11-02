import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import BroadcastCenter from './BroadcastCenter.svelte';

describe('BroadcastCenter Component', () => {
  it('renders the broadcast center header', () => {
    render(BroadcastCenter);
    expect(screen.getByText(/Trung tâm phát sóng/i)).toBeTruthy();
  });

  it('displays campaigns section', () => {
    render(BroadcastCenter);
    expect(screen.getByText('Chiến dịch')).toBeTruthy();
  });

  it('renders campaign names', () => {
    render(BroadcastCenter);
    expect(screen.getByText('Khuyến mãi Tết')).toBeTruthy();
    expect(screen.getByText('Thông báo bảo trì')).toBeTruthy();
  });

  it('shows campaign type badges', () => {
    render(BroadcastCenter);
    const emailBadge = screen.getByText('email');
    const pushBadge = screen.getByText('push');
    expect(emailBadge).toHaveClass('type-badge');
    expect(pushBadge).toHaveClass('type-badge');
  });

  it('displays campaign progress (sent/recipients)', () => {
    render(BroadcastCenter);
    expect(screen.getByText('4,500/5,000 đã gửi')).toBeTruthy();
    expect(screen.getByText('9,800/10,000 đã gửi')).toBeTruthy();
  });

  it('renders status badges with correct classes', () => {
    render(BroadcastCenter);
    const completedStatus = screen.getByText('completed');
    const sendingStatus = screen.getByText('sending');
    expect(completedStatus).toHaveClass('status', 'completed');
    expect(sendingStatus).toHaveClass('status', 'sending');
  });

  it('displays scheduled section', () => {
    render(BroadcastCenter);
    expect(screen.getByText(/Lên lịch/)).toBeTruthy();
  });

  it('renders scheduled campaign', () => {
    render(BroadcastCenter);
    expect(screen.getByText('Newsletter tháng 2')).toBeTruthy();
    expect(screen.getByText(/2024-02-01 09:00/)).toBeTruthy();
  });

  it('sets correct progress bar widths', () => {
    render(BroadcastCenter);
    const progressBars = document.querySelectorAll('.progress');
    expect(progressBars).toHaveLength(2);
    expect(progressBars[0].getAttribute('style')).toContain('width: 90%'); // 4500/5000 = 0.9
    expect(progressBars[1].getAttribute('style')).toContain('width: 98%'); // 9800/10000 = 0.98
  });
});
