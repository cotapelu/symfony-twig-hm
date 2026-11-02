import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, cleanup } from '@testing-library/svelte';
import RevenueManagement from './RevenueManagement.svelte';

describe('RevenueManagement', () => {
  afterEach(() => cleanup());

  describe('rendering', () => {
    it('renders the heading and subtitle', () => {
      render(RevenueManagement);
      expect(screen.getByText(/Quản lý Thu ngân sách/)).toBeInTheDocument();
      expect(screen.getByText(/Thu thuế và các khoản thu/)).toBeInTheDocument();
    });

    it('renders the action buttons', () => {
      render(RevenueManagement);
      expect(screen.getByText('➕ Thu mới')).toBeInTheDocument();
      expect(screen.getByText('📊 Báo cáo')).toBeInTheDocument();
    });

    it('renders all four seeded revenue taxpayers', () => {
      render(RevenueManagement);
      expect(screen.getByText('Công ty TNHH A')).toBeInTheDocument();
      expect(screen.getByText('Hộ kinh doanh B')).toBeInTheDocument();
      expect(screen.getByText('Công ty Cổ phần C')).toBeInTheDocument();
      expect(screen.getByText('Hộ kinh doanh D')).toBeInTheDocument();
    });

    it('renders table headers', () => {
      render(RevenueManagement);
      expect(screen.getByText('Người nộp thuế')).toBeInTheDocument();
      expect(screen.getByText('Loại thuế')).toBeInTheDocument();
      expect(screen.getByText('Trạng thái')).toBeInTheDocument();
    });
  });

  describe('computed stats', () => {
    it('computes total revenue (7,750,000)', () => {
      render(RevenueManagement);
      expect(screen.getByText('7,750,000')).toBeInTheDocument();
    });
    it('computes collected revenue from paid only (6,500,000)', () => {
      render(RevenueManagement);
      expect(screen.getByText('6,500,000')).toBeInTheDocument();
    });
    it('computes pending count (1)', () => {
      render(RevenueManagement);
      // The pending stat-value is a lone "1" element
      const statValues = Array.from(document.querySelectorAll('.stat-value')).map(n => n.textContent);
      expect(statValues).toContain('1');
    });
  });

  describe('status mapping', () => {
    it('maps paid -> Đã nộp', () => {
      render(RevenueManagement);
      expect(screen.getAllByText('Đã nộp').length).toBe(2);
    });
    it('maps pending -> Chờ nộp', () => {
      render(RevenueManagement);
      expect(screen.getByText('Chờ nộp')).toBeInTheDocument();
    });
    it('maps overdue -> Quá hạn', () => {
      render(RevenueManagement);
      expect(screen.getByText('Quá hạn')).toBeInTheDocument();
    });
  });

  describe('empty payment date rendering', () => {
    it('shows "-" for records without a payment date', () => {
      render(RevenueManagement);
      const dashCells = screen.getAllByText('-');
      expect(dashCells.length).toBe(2); // pending + overdue records have no date
    });
  });
});
