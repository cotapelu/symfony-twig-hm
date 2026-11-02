import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, cleanup } from '@testing-library/svelte';
import PublicServices from './PublicServices.svelte';

/**
 * Self-contained presentational panel: component seeds its own local writable
 * with 6 fixed services (4 online, 2 offline; fees incl. 0 and >0). Deterministic
 * render/output only — covers headings, list rendering, derived stats, fee
 * formatting (0 -> "Mien phi"), online/offline badges, and action buttons.
 */
afterEach(() => cleanup());

const NAMES = [
  'Cấp CCCD', 'Đăng ký thường trú', 'Khai sinh',
  'Khai tử', 'Cấp GPLX', 'Đăng ký kinh doanh',
];

describe('PublicServices', () => {
  describe('header + structure', () => {
    it('renders the header title and subtitle', () => {
      render(PublicServices, { props: {} });
      expect(screen.getByText('🌐 Dịch vụ Công trực tuyến')).toBeInTheDocument();
      expect(screen.getByText('Danh mục dịch vụ hành chính công')).toBeInTheDocument();
    });

    it('renders the add-service action', () => {
      render(PublicServices, { props: {} });
      expect(screen.getByRole('button', { name: /Thêm dịch vụ/ })).toBeInTheDocument();
    });

    it('renders a card + apply button for each of the 6 services', () => {
      render(PublicServices, { props: {} });
      for (const name of NAMES) expect(screen.getByText(name)).toBeInTheDocument();
      expect(screen.getAllByRole('button', { name: 'Nộp hồ sơ' })).toHaveLength(6);
    });
  });

  describe('derived stats', () => {
    it('shows total=6 and online=4 in the stats bar', () => {
      render(PublicServices, { props: {} });
      expect(screen.getByText('6')).toBeInTheDocument();       // total
      expect(screen.getByText('4')).toBeInTheDocument();       // online count
      expect(screen.getByText('Tổng dịch vụ')).toBeInTheDocument();
      expect(screen.getByText('Trực tuyến')).toBeInTheDocument();
    });
  });

  describe('fee formatting', () => {
    it("shows 'Miễn phí' for zero-fee services (3 of them)", () => {
      render(PublicServices, { props: {} });
      expect(screen.getAllByText('Miễn phí')).toHaveLength(3);
    });

    it('formats non-zero fees with thousands separator + VNĐ', () => {
      render(PublicServices, { props: {} });
      // Node ICU uses ',' ; allow '.' for a small-icu runtime (never assert a bare number)
      expect(screen.getByText(/30[,.]000\s*VNĐ/)).toBeInTheDocument();
      expect(screen.getByText(/500[,.]000\s*VNĐ/)).toBeInTheDocument();
    });
  });

  describe('online/offline badges', () => {
    it('renders 4 online badges and 2 offline badges', () => {
      render(PublicServices, { props: {} });
      expect(screen.getAllByText('🌐 Trực tuyến')).toHaveLength(4);
      expect(screen.getAllByText('📍 Trực tiếp')).toHaveLength(2);
    });
  });

  describe('service metadata', () => {
    it('renders category, description, and processing time', () => {
      render(PublicServices, { props: {} });
      expect(screen.getByText('Cấp căn cước công dân lần đầu')).toBeInTheDocument();
      expect(screen.getByText('7 ngày')).toBeInTheDocument();   // processingTime of Cấp CCCD
      expect(screen.getByText('Hộ khẩu')).toBeInTheDocument();  // category
    });
  });
});
