import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import TicketSystem from './TicketSystem.svelte';

describe('TicketSystem Component', () => {
  it('renders the maintenance header', () => {
    render(TicketSystem);
    expect(screen.getByText(/Hệ thống bảo trì/i)).toBeTruthy();
  });

  it('displays stats section with correct counts', () => {
    render(TicketSystem);
    const statBoxes = document.querySelectorAll('.stat-box h3');
    expect(statBoxes).toHaveLength(3);
    expect(statBoxes[0].textContent).toBe('2'); // open tickets
    expect(statBoxes[1].textContent).toBe('1'); // in-progress
    expect(statBoxes[2].textContent).toBe('1'); // closed
  });

  it('shows stats labels', () => {
    render(TicketSystem);
    expect(screen.getByText('Đang mở')).toBeTruthy();
    expect(screen.getByText('Đang xử lý')).toBeTruthy();
    expect(screen.getByText('Đã đóng')).toBeTruthy();
  });

  it('renders all tickets', () => {
    render(TicketSystem);
    expect(screen.getByText('Lỗi đăng nhập')).toBeTruthy();
    expect(screen.getByText('Yêu cầu tính năng mới')).toBeTruthy();
    expect(screen.getByText('Cập nhật giao diện')).toBeTruthy();
  });

  it('displays ticket IDs', () => {
    render(TicketSystem);
    expect(screen.getByText('TKT-001')).toBeTruthy();
    expect(screen.getByText('TKT-002')).toBeTruthy();
    expect(screen.getByText('TKT-003')).toBeTruthy();
  });

  it('shows priority badges', () => {
    render(TicketSystem);
    expect(screen.getByText('high')).toBeTruthy();
    expect(screen.getByText('medium')).toBeTruthy();
    expect(screen.getByText('low')).toBeTruthy();
  });

  it('applies priority class to badges', () => {
    render(TicketSystem);
    const highBadge = screen.getByText('high');
    const mediumBadge = screen.getByText('medium');
    const lowBadge = screen.getByText('low');
    expect(highBadge).toHaveClass('priority');
    expect(mediumBadge).toHaveClass('priority');
    expect(lowBadge).toHaveClass('priority');
  });

  it('displays ticket creation dates', () => {
    render(TicketSystem);
    expect(screen.getByText('2024-01-15')).toBeTruthy();
    expect(screen.getByText('2024-01-14')).toBeTruthy();
    expect(screen.getByText('2024-01-13')).toBeTruthy();
  });

  it('renders status badges with inline style colors', () => {
    render(TicketSystem);
    const statuses = document.querySelectorAll('.status');
    expect(statuses).toHaveLength(4);
    // Find each status by text content or order matching tickets array order
    const openStatus = Array.from(statuses).find(s => s.textContent === 'open');
    const inProgressStatus = Array.from(statuses).find(s => s.textContent === 'in-progress');
    const closedStatus = Array.from(statuses).find(s => s.textContent === 'closed');
    expect(openStatus).toBeTruthy();
    expect(window.getComputedStyle(openStatus).backgroundColor).toBe('rgb(239, 68, 68)');
    expect(inProgressStatus).toBeTruthy();
    expect(window.getComputedStyle(inProgressStatus).backgroundColor).toBe('rgb(245, 158, 11)');
    expect(closedStatus).toBeTruthy();
    expect(window.getComputedStyle(closedStatus).backgroundColor).toBe('rgb(16, 185, 129)');
  });
});
