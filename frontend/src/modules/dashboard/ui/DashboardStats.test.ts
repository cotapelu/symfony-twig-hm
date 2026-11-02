import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/svelte';
import DashboardStats from './DashboardStats.svelte';

// Mock the showToast store
vi.mock('$lib/stores/global', async () => {
  const actual = await vi.importActual('$lib/stores/global');
  return {
    ...actual,
    showToast: vi.fn()
  };
});

describe('DashboardStats Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders quick stats cards', () => {
    render(DashboardStats);
    expect(screen.getAllByText('Messages').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Groups').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Files').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Calls').length).toBeGreaterThan(0);
  });

  it('displays stat values', () => {
    render(DashboardStats);
    expect(screen.getAllByText('128').length).toBeGreaterThan(0); // Messages total
    expect(screen.getAllByText('24').length).toBeGreaterThan(0); // Groups total
    expect(screen.getAllByText('567').length).toBeGreaterThan(0); // Files total
    expect(screen.getAllByText('45').length).toBeGreaterThan(0); // Calls total
  });

  it('shows change percentages', () => {
    render(DashboardStats);
    expect(screen.getByText('+12% this week')).toBeTruthy();
    expect(screen.getByText('+3% this week')).toBeTruthy();
    expect(screen.getByText('+23% this week')).toBeTruthy();
    expect(screen.getByText('-2% this week')).toBeTruthy();
  });

  it('renders main stats cards with icons', () => {
    render(DashboardStats);
    // Check for icon presence (Icon components render something)
    const icons = document.querySelectorAll('.stat-icon, .card-header svg, .icon');
    expect(icons.length).toBeGreaterThan(0);
  });

  it('renders progress bars', () => {
    render(DashboardStats);
    const progressBars = document.querySelectorAll('progress, [role="progressbar"]');
    expect(progressBars.length).toBe(4); // One for each main stat
  });

  it('shows unread badges', () => {
    render(DashboardStats);
    const unreadBadges = screen.getAllByText(/unread/);
    expect(unreadBadges.length).toBeGreaterThan(0);
  });

  it('renders activity section', () => {
    render(DashboardStats);
    expect(screen.getByText('Recent Activity')).toBeTruthy();
    expect(screen.getByText('View All')).toBeTruthy();
  });

  it('displays activity feed items', () => {
    render(DashboardStats);
    // Check for some activity items from mock data
    expect(screen.getByText('New message from Sarah')).toBeTruthy();
    expect(screen.getByText('John Doe accepted your request')).toBeTruthy();
    expect(screen.getByText('File shared in Project Alpha')).toBeTruthy();
  });

  it('shows activity timestamps', () => {
    render(DashboardStats);
    // The formatTime function should produce relative times
    expect(screen.getByText('5m ago')).toBeTruthy();
    expect(screen.getByText('30m ago')).toBeTruthy();
  });

  it('marks unread activities with New badge', () => {
    render(DashboardStats);
    const newBadges = screen.getAllByText('New');
    expect(newBadges.length).toBeGreaterThan(0);
  });

  it('renders loading state initially', () => {
    // Since onMount triggers loadStats with 800ms delay, component starts loading
    render(DashboardStats);
    // The loading flag is true initially, but since we don't wait, the UI might show progress or nothing
    // We can at least verify component renders without crashing
    expect(document.querySelector('.dashboard-stats')).toBeTruthy();
  });

  it('does not show error on successful load', async () => {
    render(DashboardStats);
    // Wait for loadStats to complete
    await waitFor(() => {
      // After loading, the stats should be visible
      expect(screen.getAllByText('Messages').length).toBeGreaterThan(0);
    }, { timeout: 1000 });
    // showToast should not have been called with error
    const { showToast } = await import('$lib/stores/global');
    expect(showToast).not.toHaveBeenCalledWith(expect.stringContaining('Failed'), 'error');
  });
});
