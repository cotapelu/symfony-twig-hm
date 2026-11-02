import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, fireEvent, cleanup } from '@testing-library/svelte';
import EducationManagement from './EducationManagement.svelte';

describe('EducationManagement', () => {
  afterEach(() => cleanup());

  it('renders the heading', () => {
    render(EducationManagement);
    expect(screen.getByText(/Quản lý Giáo dục/)).toBeInTheDocument();
  });

  it('renders all three schools by default', () => {
    render(EducationManagement);
    expect(screen.getByText('Tiểu học Nguyễn Trãi')).toBeInTheDocument();
    expect(screen.getByText('THCS Lê Lợi')).toBeInTheDocument();
    expect(screen.getByText('THPT Quang Trung')).toBeInTheDocument();
  });

  describe('computed stats', () => {
    it('computes total schools (3), students (1650), teachers (100)', () => {
      render(EducationManagement);
      const values = Array.from(document.querySelectorAll('.stat-value')).map(n => n.textContent);
      expect(values).toEqual(['3', '1650', '100']);
    });
  });

  describe('type filter tabs', () => {
    it('filters to primary schools when "Tiểu học" clicked', () => {
      render(EducationManagement);
      fireEvent.click(screen.getByText('Tiểu học'));
      expect(screen.getByText('Tiểu học Nguyễn Trãi')).toBeInTheDocument();
      expect(screen.queryByText('THCS Lê Lợi')).not.toBeInTheDocument();
      expect(screen.queryByText('THPT Quang Trung')).not.toBeInTheDocument();
    });
    it('filters to secondary schools when "THCS" clicked', () => {
      render(EducationManagement);
      fireEvent.click(screen.getByText('THCS'));
      expect(screen.queryByText('Tiểu học Nguyễn Trãi')).not.toBeInTheDocument();
      expect(screen.getByText('THCS Lê Lợi')).toBeInTheDocument();
    });
    it('filters to high schools when "THPT" clicked', () => {
      render(EducationManagement);
      fireEvent.click(screen.getByText('THPT'));
      expect(screen.getByText('THPT Quang Trung')).toBeInTheDocument();
      expect(screen.queryByText('THCS Lê Lợi')).not.toBeInTheDocument();
    });
    it('returns to all schools when "Tất cả" clicked after filtering', () => {
      render(EducationManagement);
      fireEvent.click(screen.getByText('Tiểu học'));
      expect(screen.queryByText('THPT Quang Trung')).not.toBeInTheDocument();
      fireEvent.click(screen.getByText('Tất cả'));
      expect(screen.getByText('THPT Quang Trung')).toBeInTheDocument();
      expect(screen.getByText('THCS Lê Lợi')).toBeInTheDocument();
    });
    it('marks the active tab', () => {
      render(EducationManagement);
      const allTab = screen.getByText('Tất cả');
      expect(allTab).toHaveClass('active');
      fireEvent.click(screen.getByText('Tiểu học'));
      expect(screen.getByText('Tất cả')).not.toHaveClass('active');
      expect(screen.getByText('Tiểu học')).toHaveClass('active');
    });
  });

  it('renders facility tags (some shared across schools)', () => {
    render(EducationManagement);
    expect(screen.getAllByText('Thư viện').length).toBe(3); // all schools
    expect(screen.getByText('Sân thể thao')).toBeInTheDocument(); // unique
  });
});
