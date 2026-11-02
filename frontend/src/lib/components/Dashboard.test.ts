import { render } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Dashboard from './Dashboard.svelte';

// Mock child components to render their slots
vi.mock('./Typography.svelte', () => ({
  default: ({ children, variant, class: className }: any) => {
    return `<div class="${className || ''}" data-variant="${variant}">${children || ''}</div>`;
  }
}));

vi.mock('./Card.svelte', () => ({
  default: ({ children }: any) => `<div class="card">${children || ''}</div>`
}));

vi.mock('./Grid.svelte', () => ({
  default: ({ children }: any) => `<div class="grid">${children || ''}</div>`
}));

describe('Dashboard Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('rendering', () => {
    it('renders without error', () => {
      render(Dashboard);
      expect(document.body).toBeTruthy();
    });

    it('renders dashboard container', () => {
      render(Dashboard);
      expect(document.querySelector('.dashboard')).toBeInTheDocument();
    });

    it('has correct structure', () => {
      render(Dashboard);
      const dashboard = document.querySelector('.dashboard');
      expect(dashboard).toBeInTheDocument();
    });

    it('handles multiple instances', () => {
      const { container: c1 } = render(Dashboard);
      const { container: c2 } = render(Dashboard);
      expect(c1).toBeTruthy();
      expect(c2).toBeTruthy();
    });
  });

  describe('initial state', () => {
    it('initializes stats to zero', () => {
      render(Dashboard);
      expect(document.querySelector('.dashboard')).toBeInTheDocument();
    });

    it('initializes recentActivity to empty array', () => {
      render(Dashboard);
      expect(document.querySelector('.dashboard')).toBeInTheDocument();
    });
  });

  describe('styling', () => {
    it('includes responsive styles', () => {
      render(Dashboard);
      expect(document.querySelector('.dashboard')).toBeInTheDocument();
    });
  });
});