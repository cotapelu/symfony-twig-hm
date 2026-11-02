import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import userEvent from '@testing-library/user-event';
import MockIcon from './molecules/MockIcon.svelte';
import MockTypography from './molecules/MockTypography.svelte';
import MockBadge from './molecules/MockBadge.svelte';

// Mock the exact relative imports used by AuditLog.svelte
vi.mock('./Icon.svelte', () => ({ default: MockIcon }));
vi.mock('./Typography.svelte', () => ({ default: MockTypography }));
vi.mock('./Badge.svelte', () => ({ default: MockBadge }));

// Import after mocks are set up
import AuditLog from './AuditLog.svelte';

describe('AuditLog Component', () => {
  const sampleLogs = [
    {
      id: 'log1',
      action: 'CREATE',
      entity: 'User',
      details: 'Created new user account',
      userId: 'user1',
      userName: 'John Doe',
      timestamp: new Date('2024-01-15T10:30:00Z'),
      severity: 'info' as const,
      ipAddress: '192.168.1.1',
      userAgent: 'Mozilla/5.0...'
    },
    {
      id: 'log2',
      action: 'DELETE',
      entity: 'Post',
      details: 'Deleted post #123',
      userId: 'user2',
      userName: 'Jane Smith',
      timestamp: new Date('2024-01-15T11:45:00Z'),
      severity: 'warning' as const,
      ipAddress: '192.168.1.1',
      userAgent: 'Mozilla/5.0...'
    },
    {
      id: 'log3',
      action: 'LOGIN_FAILED',
      entity: 'Auth',
      details: 'Failed login attempt',
      userId: 'user3',
      userName: 'Bob Wilson',
      timestamp: new Date('2024-01-15T12:00:00Z'),
      severity: 'error' as const,
      ipAddress: '10.0.0.1',
      userAgent: 'Mozilla/5.0...'
    },
    {
      id: 'log4',
      action: 'DATA_BREACH',
      entity: 'Database',
      details: 'Potential data breach detected',
      userId: 'system',
      userName: 'System',
      timestamp: new Date('2024-01-15T13:00:00Z'),
      severity: 'critical' as const,
      ipAddress: '10.0.0.1',
      userAgent: 'Internal'
    }
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without error with default props', () => {
    render(AuditLog);
    expect(screen.getByText('Audit Log')).toBeInTheDocument();
  });

  it('renders audit log header', () => {
    render(AuditLog, { props: { logs: sampleLogs } });
    expect(screen.getByText('Audit Log')).toBeInTheDocument();
  });

  it('renders search input when searchable is true', () => {
    render(AuditLog, { props: { logs: sampleLogs, searchable: true } });
    expect(screen.getByPlaceholderText('Search logs...')).toBeInTheDocument();
  });

  it('hides search input when searchable is false', () => {
    render(AuditLog, { props: { logs: sampleLogs, searchable: false } });
    expect(screen.queryByPlaceholderText('Search logs...')).not.toBeInTheDocument();
  });

  it('renders export button when exportable is true', () => {
    render(AuditLog, { props: { logs: sampleLogs, exportable: true } });
    expect(screen.getByText('Export')).toBeInTheDocument();
  });

  it('hides export button when exportable is false', () => {
    render(AuditLog, { props: { logs: sampleLogs, exportable: false } });
    expect(screen.queryByText('Export')).not.toBeInTheDocument();
  });

  it('shows loading state when loading is true', () => {
    render(AuditLog, { props: { logs: sampleLogs, loading: true } });
    expect(screen.getByText('Loading logs...')).toBeInTheDocument();
  });

  it('shows empty state when no logs match', () => {
    render(AuditLog, { props: { logs: [], filter: { action: 'NONEXISTENT' } } });
    expect(screen.getByText('No audit logs found')).toBeInTheDocument();
  });

  it('renders log items with action, entity, and user', () => {
    render(AuditLog, { props: { logs: sampleLogs } });
    expect(screen.getByText('CREATE')).toBeInTheDocument();
    // Text is "User by John Doe" not just "User"
    expect(screen.getByText(/User by/)).toBeInTheDocument();
    expect(screen.getByText(/John Doe/)).toBeInTheDocument();
  });

  it('renders severity badges with correct variants', () => {
    render(AuditLog, { props: { logs: sampleLogs } });
    // Info severity should have outlined variant (default)
    // Warning should have default variant
    // Error and critical should have filled variant
    expect(screen.getByText('info')).toBeInTheDocument();
    expect(screen.getByText('warning')).toBeInTheDocument();
    expect(screen.getByText('error')).toBeInTheDocument();
    expect(screen.getByText('critical')).toBeInTheDocument();
  });

  it('renders timestamps in formatted format', () => {
    render(AuditLog, { props: { logs: sampleLogs } });
    // Should render formatted timestamps (includes time)
    // Multiple elements match, so use getAllByText
    expect(screen.getAllByText(/Jan 15, 2024/)).toHaveLength(4);
  });

  it('shows loading spinner when loading', () => {
    render(AuditLog, { props: { logs: sampleLogs, loading: true } });
    expect(screen.getByText('Loading logs...')).toBeInTheDocument();
  });

  it('filters logs by search query', async () => {
    render(AuditLog, { props: { logs: sampleLogs, searchable: true } });
    const searchInput = screen.getByPlaceholderText('Search logs...');
    await userEvent.type(searchInput, 'DELETE');
    // Should filter to only show DELETE action
    expect(screen.getByText('DELETE')).toBeInTheDocument();
    expect(screen.queryByText('CREATE')).not.toBeInTheDocument();
  });

  it('expands log details when clicked', async () => {
    render(AuditLog, { props: { logs: sampleLogs, showDetails: true } });
    const logItem = screen.getByText('CREATE').closest('.audit-log-item');
    await userEvent.click(logItem!);
    // Should expand to show details
    await waitFor(() => {
      expect(screen.getByText('Details')).toBeInTheDocument();
      expect(screen.getByText('Created new user account')).toBeInTheDocument();
    });
  });

  it('collapses log details when clicked again', async () => {
    render(AuditLog, { props: { logs: sampleLogs, showDetails: true } });
    const logItem = screen.getByText('CREATE').closest('.audit-log-item');
    await userEvent.click(logItem!);
    await waitFor(() => {
      expect(screen.getByText('Details')).toBeInTheDocument();
    });
    await userEvent.click(logItem!);
    await waitFor(() => {
      expect(screen.queryByText('Details')).not.toBeInTheDocument();
    });
  });

  it('shows metadata when showMetadata is true', async () => {
    render(AuditLog, { 
      props: { logs: sampleLogs, showDetails: true, showMetadata: true } 
    });
    const logItem = screen.getByText('CREATE').closest('.audit-log-item');
    await userEvent.click(logItem!);
    await waitFor(() => {
      expect(screen.getByText('User ID')).toBeInTheDocument();
      expect(screen.getByText('user1')).toBeInTheDocument();
      expect(screen.getByText('IP Address')).toBeInTheDocument();
      expect(screen.getByText('192.168.1.1')).toBeInTheDocument();
    });
  });

  it('does not show metadata fields when not present', async () => {
    const logWithoutMeta = [{
      id: 'log1',
      action: 'CREATE',
      entity: 'User',
      details: 'Created new user account',
      userId: 'user1',
      userName: 'John Doe',
      timestamp: new Date('2024-01-15T10:30:00Z'),
      severity: 'info' as const
    }];
    render(AuditLog, { 
      props: { logs: logWithoutMeta, showDetails: true, showMetadata: true } 
    });
    const logItem = screen.getByText('CREATE').closest('.audit-log-item');
    await userEvent.click(logItem!);
    await waitFor(() => {
      expect(screen.queryByText('IP Address')).not.toBeInTheDocument();
      expect(screen.queryByText('User Agent')).not.toBeInTheDocument();
    });
  });

  it('renders pagination when more than one page', () => {
    const manyLogs = Array.from({ length: 25 }, (_, i) => ({
      id: `log${i}`,
      action: 'ACTION',
      entity: 'Entity',
      details: `Detail ${i}`,
      userId: 'user1',
      userName: 'User',
      timestamp: new Date(),
      severity: 'info' as const
    }));
    render(AuditLog, { props: { logs: manyLogs, itemsPerPage: 10 } });
    expect(screen.getByText('Page 1 of 3')).toBeInTheDocument();
    // Pagination buttons don't have accessible names, find by class
    const paginationButtons = document.querySelectorAll('.pagination-button');
    expect(paginationButtons.length).toBe(2);
  });

  it('disables previous button on first page', () => {
    const manyLogs = Array.from({ length: 25 }, (_, i) => ({
      id: `log${i}`,
      action: 'ACTION',
      entity: 'Entity',
      details: `Detail ${i}`,
      userId: 'user1',
      userName: 'User',
      timestamp: new Date(),
      severity: 'info' as const
    }));
    render(AuditLog, { props: { logs: manyLogs, itemsPerPage: 10 } });
    const prevBtn = document.querySelectorAll('.pagination-button')[0];
    expect(prevBtn).toBeDisabled();
  });

  it('navigates pages when pagination buttons clicked', async () => {
    const manyLogs = Array.from({ length: 25 }, (_, i) => ({
      id: `log${i}`,
      action: 'ACTION',
      entity: 'Entity',
      details: `Detail ${i}`,
      userId: 'user1',
      userName: 'User',
      timestamp: new Date(),
      severity: 'info' as const
    }));
    render(AuditLog, { props: { logs: manyLogs, itemsPerPage: 10 } });
    const nextBtn = document.querySelectorAll('.pagination-button')[1];
    await userEvent.click(nextBtn);
    await waitFor(() => {
      expect(screen.getByText('Page 2 of 3')).toBeInTheDocument();
    });
  });

  it('shows critical severity with filled badge', () => {
    render(AuditLog, { props: { logs: sampleLogs } });
    const criticalBadge = screen.getByText('critical').closest('.badge');
    expect(criticalBadge).toHaveClass('badge-filled');
  });

  it('shows error severity with filled badge', () => {
    render(AuditLog, { props: { logs: sampleLogs } });
    const errorBadge = screen.getByText('error').closest('.badge');
    expect(errorBadge).toHaveClass('badge-filled');
  });

  it('shows warning severity with default badge', () => {
    render(AuditLog, { props: { logs: sampleLogs } });
    const warningBadge = screen.getByText('warning').closest('.badge');
    expect(warningBadge).toHaveClass('badge-default');
  });

  it('shows info severity with outlined badge', () => {
    render(AuditLog, { props: { logs: sampleLogs } });
    const infoBadge = screen.getByText('info').closest('.badge');
    expect(infoBadge).toHaveClass('badge-outlined');
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(AuditLog, { props: { logs: sampleLogs } });
    const { container: container2 } = render(AuditLog, { props: { logs: [] } });
    expect(container1).toBeTruthy();
    expect(container2).toBeTruthy();
  });

  it('handles keyboard navigation for log items', async () => {
    render(AuditLog, { props: { logs: sampleLogs, showDetails: true } });
    const logItem = screen.getByText('CREATE').closest('.audit-log-item');
    logItem!.focus();
    await userEvent.keyboard('{Enter}');
    await waitFor(() => {
      expect(screen.getByText('Details')).toBeInTheDocument();
    });
  });

  it('renders chevron icons for expandable items', () => {
    render(AuditLog, { props: { logs: sampleLogs } });
    // Chevron icons are SVG paths, find by SVG path data
    const chevronIcons = document.querySelectorAll('path[d*="M3 9l9-7"]');
    expect(chevronIcons.length).toBeGreaterThan(0);
  });

  it('shows export button disabled when no logs', () => {
    render(AuditLog, { props: { logs: [], exportable: true } });
    const exportBtn = screen.getByText('Export');
    expect(exportBtn).toBeDisabled();
  });

  it('shows user agent in metadata when present', async () => {
    render(AuditLog, { 
      props: { logs: sampleLogs, showDetails: true, showMetadata: true } 
    });
    const logItem = screen.getByText('CREATE').closest('.audit-log-item');
    await userEvent.click(logItem!);
    await waitFor(() => {
      expect(screen.getByText('User Agent')).toBeInTheDocument();
      expect(screen.getByText('Mozilla/5.0...')).toBeInTheDocument();
    });
  });
});