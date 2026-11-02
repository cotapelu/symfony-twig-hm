import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, cleanup } from '@testing-library/svelte';
import HealthcareManagement from './HealthcareManagement.svelte';

describe('HealthcareManagement', () => {
  afterEach(() => cleanup());

  it('renders the heading', () => {
    render(HealthcareManagement);
    expect(screen.getByText(/Quản lý Y tế/)).toBeInTheDocument();
  });

  it('renders the add-station action', () => {
    render(HealthcareManagement);
    expect(screen.getByText('➕ Thêm trạm')).toBeInTheDocument();
  });

  it('renders both health stations', () => {
    render(HealthcareManagement);
    expect(screen.getByText('Trạm Y tế Phường X')).toBeInTheDocument();
    expect(screen.getByText('Phòng khám Đa khoa Y')).toBeInTheDocument();
  });

  describe('computed stats', () => {
    it('computes total stations (2)', () => {
      render(HealthcareManagement);
      const values = Array.from(document.querySelectorAll('.stat-value')).map(n => n.textContent);
      expect(values).toEqual(['2', '17', '30', '70']);
    });
  });

  it('renders per-station resource counts', () => {
    render(HealthcareManagement);
    expect(screen.getAllByText('12').length).toBe(1); // doctors of station 2
    expect(screen.getAllByText('20').length).toBeGreaterThan(0); // nurses/beds value 20
  });

  it('renders service tags (some shared across stations)', () => {
    render(HealthcareManagement);
    expect(screen.getAllByText('Khám bệnh').length).toBe(2); // both stations
    expect(screen.getByText('Siêu âm')).toBeInTheDocument(); // unique
  });
});
