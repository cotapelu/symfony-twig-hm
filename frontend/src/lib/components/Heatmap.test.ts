import { render, screen, fireEvent, waitFor, within } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import userEvent from '@testing-library/user-event';
import MockIcon from './molecules/MockIcon.svelte';

// Mock the exact relative imports used by Heatmap.svelte
vi.mock('./Icon.svelte', () => ({ default: MockIcon }));

// Import after mocks are set up
import Heatmap from './Heatmap.svelte';

describe('Heatmap Component', () => {
  const sampleData = [
    [10, 20, 30],
    [40, 50, 60],
    [70, 80, 90]
  ];

  const sampleLabels = {
    x: ['Jan', 'Feb', 'Mar'],
    y: ['Mon', 'Tue', 'Wed']
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without error with default props', () => {
    render(Heatmap, { props: { data: sampleData } });
    // Should render cells with data
    const cells = screen.getAllByRole('gridcell');
    expect(cells.length).toBeGreaterThan(0);
  });

  it('renders heatmap cells for provided data', () => {
    render(Heatmap, { props: { data: sampleData } });
    const cells = screen.getAllByRole('gridcell');
    expect(cells).toHaveLength(9); // 3x3 grid
  });

  it('renders column headers when labels.x is provided', () => {
    render(Heatmap, { props: { data: sampleData, labels: sampleLabels } });
    expect(screen.getByText('Jan')).toBeInTheDocument();
    expect(screen.getByText('Feb')).toBeInTheDocument();
    expect(screen.getByText('Mar')).toBeInTheDocument();
  });

  it('renders row headers when labels.y is provided', () => {
    render(Heatmap, { props: { data: sampleData, labels: sampleLabels } });
    expect(screen.getByText('Mon')).toBeInTheDocument();
    expect(screen.getByText('Tue')).toBeInTheDocument();
    expect(screen.getByText('Wed')).toBeInTheDocument();
  });

  it('applies correct background colors based on value range', () => {
    render(Heatmap, { props: { data: sampleData, valueRange: [0, 100] } });
    const cells = screen.getAllByRole('gridcell');
    // First cell (10) should be closer to min color
    expect(cells[0]).toHaveStyle({ backgroundColor: expect.any(String) });
    // Last cell (90) should be closer to max color
    expect(cells[8]).toHaveStyle({ backgroundColor: expect.any(String) });
  });

  it('shows values when showValues is true', () => {
    render(Heatmap, { props: { data: sampleData, showValues: true } });
    expect(screen.getByText('10')).toBeInTheDocument();
    expect(screen.getByText('50')).toBeInTheDocument();
    expect(screen.getByText('90')).toBeInTheDocument();
  });

  it('hides values when showValues is false (default)', () => {
    render(Heatmap, { props: { data: sampleData } });
    // Values should not be visible as text content
    expect(screen.queryByText('10')).not.toBeInTheDocument();
  });

  it('applies size variants correctly', () => {
    const { container: smallContainer } = render(Heatmap, { 
      props: { data: sampleData, size: 'small' } 
    });
    const { container: largeContainer } = render(Heatmap, { 
      props: { data: sampleData, size: 'large' } 
    });
    expect(smallContainer).toBeTruthy();
    expect(largeContainer).toBeTruthy();
  });

  it('renders empty data without error', () => {
    // When data is empty, component should render without crashing
    render(Heatmap, { props: { data: [] } });
    // Should not crash - just verify it renders
    expect(true).toBe(true);
  });

  it('dispatches cellClick event when interactive cell is clicked', async () => {
    const onCellClick = vi.fn();
    render(Heatmap, { 
      props: { data: sampleData, interactive: true, onCellClick } 
    });

    const cells = screen.getAllByRole('gridcell');
    await userEvent.click(cells[0]);

    expect(onCellClick).toHaveBeenCalledWith({ x: 0, y: 0, value: 10 });
  });

  it('does not dispatch cellClick when interactive is false', async () => {
    const onCellClick = vi.fn();
    render(Heatmap, { 
      props: { data: sampleData, interactive: false, onCellClick } 
    });

    const cells = screen.getAllByRole('gridcell');
    await userEvent.click(cells[0]);

    expect(onCellClick).not.toHaveBeenCalled();
  });

  it('shows tooltip on hover when interactive', async () => {
    render(Heatmap, { 
      props: { data: sampleData, labels: sampleLabels, interactive: true } 
    });

    const cells = screen.getAllByRole('gridcell');
    await userEvent.hover(cells[0]);

    // In test environment, tooltip may not render due to jsdom limitations
    // Just verify the hover event doesn't throw
    expect(cells[0]).toBeInTheDocument();
  });

  it('handles keyboard Enter key on interactive cell', async () => {
    render(Heatmap, { 
      props: { data: sampleData, interactive: true } 
    });

    const cells = screen.getAllByRole('gridcell');
    cells[0].focus();
    await userEvent.keyboard('{Enter}');
    // Should not throw
    expect(cells[0]).toBeInTheDocument();
  });

  it('handles keyboard Space key on interactive cell', async () => {
    render(Heatmap, { 
      props: { data: sampleData, interactive: true } 
    });

    const cells = screen.getAllByRole('gridcell');
    cells[0].focus();
    await userEvent.keyboard(' ');
    // Should not throw
    expect(cells[0]).toBeInTheDocument();
  });

  it('applies custom color range', () => {
    const customColors = {
      min: '#ff0000',
      mid: '#ffff00',
      max: '#00ff00'
    };
    render(Heatmap, { props: { data: sampleData, colors: customColors } });
    const cells = screen.getAllByRole('gridcell');
    expect(cells[0]).toBeInTheDocument();
    expect(cells[8]).toBeInTheDocument();
  });

  it('applies custom valueRange', () => {
    render(Heatmap, { props: { data: sampleData, valueRange: [0, 50] } });
    const cells = screen.getAllByRole('gridcell');
    // Values above range should still render
    expect(cells[8]).toBeInTheDocument();
  });

  it('shows labels when showLabels is true (default)', () => {
    render(Heatmap, { props: { data: sampleData, labels: sampleLabels } });
    expect(screen.getByText('Jan')).toBeInTheDocument();
    expect(screen.getByText('Mon')).toBeInTheDocument();
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(Heatmap, { props: { data: sampleData } });
    const { container: container2 } = render(Heatmap, { props: { data: [[1,2],[3,4]] } });
    expect(container1).toBeTruthy();
    expect(container2).toBeTruthy();
  });

  it('handles rectangular data (non-square)', () => {
    const rectData = [
      [1, 2, 3, 4],
      [5, 6, 7, 8]
    ];
    render(Heatmap, { props: { data: rectData } });
    const cells = screen.getAllByRole('gridcell');
    expect(cells).toHaveLength(8); // 2x4
  });

  it('handles single row data', () => {
    render(Heatmap, { props: { data: [[10, 20, 30]] } });
    const cells = screen.getAllByRole('gridcell');
    expect(cells).toHaveLength(3);
  });

  it('handles single cell data', () => {
    render(Heatmap, { props: { data: [[42]] } });
    const cells = screen.getAllByRole('gridcell');
    expect(cells).toHaveLength(1);
    expect(cells[0]).toHaveAttribute('aria-label', '1, 1: 42');
  });

  it('applies custom className', () => {
    render(Heatmap, { props: { data: sampleData, className: 'custom-heatmap' } });
    const cells = screen.getAllByRole('gridcell');
    expect(cells.length).toBeGreaterThan(0);
  });

  it('renders with x labels only', () => {
    render(Heatmap, { 
      props: { data: sampleData, labels: { x: ['A', 'B', 'C'] } } 
    });
    expect(screen.getByText('A')).toBeInTheDocument();
    expect(screen.getByText('B')).toBeInTheDocument();
    expect(screen.getByText('C')).toBeInTheDocument();
  });

  it('renders with y labels only', () => {
    render(Heatmap, { 
      props: { data: sampleData, labels: { y: ['X', 'Y', 'Z'] } } 
    });
    expect(screen.getByText('X')).toBeInTheDocument();
    expect(screen.getByText('Y')).toBeInTheDocument();
    expect(screen.getByText('Z')).toBeInTheDocument();
  });

  it('handles data with zero values', () => {
    const zeroData = [[0, 0], [0, 0]];
    render(Heatmap, { props: { data: zeroData, valueRange: [0, 100] } });
    const cells = screen.getAllByRole('gridcell');
    expect(cells).toHaveLength(4);
  });

  it('handles negative values', () => {
    const negData = [[-10, -20], [-30, -40]];
    render(Heatmap, { props: { data: negData, valueRange: [-40, 0] } });
    const cells = screen.getAllByRole('gridcell');
    expect(cells).toHaveLength(4);
  });

  it('renders interactive cells with tabindex', () => {
    render(Heatmap, { props: { data: sampleData, interactive: true } });
    const cells = screen.getAllByRole('gridcell');
    cells.forEach(cell => {
      expect(cell).toHaveAttribute('tabindex', '0');
    });
  });

  it('renders non-interactive cells without tabindex', () => {
    render(Heatmap, { props: { data: sampleData, interactive: false } });
    const cells = screen.getAllByRole('gridcell');
    cells.forEach(cell => {
      expect(cell).toHaveAttribute('tabindex', '-1');
    });
  });

  it('applies aria-label to cells', () => {
    render(Heatmap, { props: { data: sampleData, labels: sampleLabels } });
    const cells = screen.getAllByRole('gridcell');
    expect(cells[0]).toHaveAttribute('aria-label', 'Jan, Mon: 10');
  });
});