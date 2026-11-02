import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import userEvent from '@testing-library/user-event';
import FacetedSearch from './FacetedSearch.svelte';

describe('FacetedSearch', () => {
  const sampleTreeData = [
    {
      id: '1',
      label: 'Parent 1',
      expanded: true,
      children: [
        { id: '1-1', label: 'Child 1' },
        { id: '1-2', label: 'Child 2' }
      ]
    },
    {
      id: '2',
      label: 'Parent 2',
      expanded: false,
      children: [
        { id: '2-1', label: 'Child 3' }
      ]
    },
    {
      id: '3',
      label: 'Leaf Node'
    }
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders with default props (empty treeData)', () => {
    render(FacetedSearch);
    expect(screen.queryByText('Parent 1')).not.toBeInTheDocument();
    expect(screen.getByRole('list')).toBeInTheDocument();
  });

  it('renders tree with sample data', () => {
    render(FacetedSearch, { props: { treeData: sampleTreeData } });
    expect(screen.getByText('Parent 1')).toBeInTheDocument();
    expect(screen.getByText('Parent 2')).toBeInTheDocument();
    expect(screen.getByText('Leaf Node')).toBeInTheDocument();
  });

  it('renders child nodes when parent is expanded', () => {
    render(FacetedSearch, { props: { treeData: sampleTreeData } });
    expect(screen.getByText('Child 1')).toBeInTheDocument();
    expect(screen.getByText('Child 2')).toBeInTheDocument();
  });

  it('shows checkboxes when checkable is true', () => {
    render(FacetedSearch, { props: { treeData: sampleTreeData, checkable: true } });
    const checkboxes = screen.getAllByRole('checkbox');
    expect(checkboxes.length).toBe(3); // Parent 1, Parent 2, Leaf Node
  });

  it('does not show checkboxes when checkable is false', () => {
    render(FacetedSearch, { props: { treeData: sampleTreeData, checkable: false } });
    const checkboxes = screen.queryAllByRole('checkbox');
    expect(checkboxes.length).toBe(0);
  });

  it('applies custom className', () => {
    const { container } = render(FacetedSearch, { props: { treeData: sampleTreeData, className: 'custom-class' } });
    expect(container.querySelector('.custom-class')).toBeInTheDocument();
  });

  it('shows expand buttons for nodes with children when expandible', () => {
    render(FacetedSearch, { props: { treeData: sampleTreeData, expandible: true } });
    const expandButtons = screen.getAllByRole('button', { name: /toggle children/i });
    expect(expandButtons.length).toBe(2); // Parent 1 and Parent 2
  });

  it('shows expand buttons for nodes with children (component always renders them)', () => {
    // Component renders expand buttons for nodes with children regardless of expandible prop
    render(FacetedSearch, { props: { treeData: sampleTreeData, expandible: false } });
    const expandButtons = screen.getAllByRole('button', { name: /toggle children/i });
    expect(expandButtons.length).toBe(2);
  });

  it('toggles checkbox state when clicked', async () => {
    const user = userEvent.setup();
    render(FacetedSearch, { props: { treeData: sampleTreeData, checkable: true } });
    const checkbox = screen.getAllByRole('checkbox')[0];
    await user.click(checkbox);
    expect(checkbox).toBeChecked();
  });

  it('removes from checked when unchecking', async () => {
    const user = userEvent.setup();
    render(FacetedSearch, { props: { treeData: sampleTreeData, checkable: true } });
    const checkbox = screen.getAllByRole('checkbox')[0];
    await user.click(checkbox);
    expect(checkbox).toBeChecked();
    await user.click(checkbox);
    expect(checkbox).not.toBeChecked();
  });

  it('handles empty treeData', () => {
    render(FacetedSearch, { props: { treeData: [] } });
    expect(screen.queryByText('Parent 1')).not.toBeInTheDocument();
    expect(screen.getByRole('list')).toBeInTheDocument();
  });

  it('renders multiple instances independently', () => {
    const { container: c1 } = render(FacetedSearch, { props: { treeData: sampleTreeData } });
    const { container: c2 } = render(FacetedSearch, { props: { treeData: [] } });
    expect(c1.querySelector('.tree')).toBeInTheDocument();
    expect(c2.querySelector('.tree')).toBeInTheDocument();
  });

  it('renders tree-leaf for items without children', () => {
    render(FacetedSearch, { props: { treeData: sampleTreeData } });
    expect(screen.getByText('Leaf Node')).toBeInTheDocument();
  });

  it('checkable prop adds checkable class to tree', () => {
    const { container } = render(FacetedSearch, { props: { treeData: sampleTreeData, checkable: true } });
    expect(container.querySelector('.checkable')).toBeInTheDocument();
  });

  it('checkable prop false does not add checkable class', () => {
    const { container } = render(FacetedSearch, { props: { treeData: sampleTreeData, checkable: false } });
    expect(container.querySelector('.checkable')).not.toBeInTheDocument();
  });

  it('expandible prop enables expand buttons', () => {
    render(FacetedSearch, { props: { treeData: sampleTreeData, expandible: true } });
    const expandButtons = screen.getAllByRole('button', { name: /toggle children/i });
    expect(expandButtons.length).toBe(2);
  });

  it('handles children with only labels', () => {
    const data = [
      {
        id: '1',
        label: 'Parent',
        children: [
          { id: '1-1', label: 'Child' }
        ]
      }
    ];
    render(FacetedSearch, { props: { treeData: data } });
    expect(screen.getByText('Child')).toBeInTheDocument();
  });

  it('renders tree structure with proper nesting classes', () => {
    const { container } = render(FacetedSearch, { props: { treeData: sampleTreeData } });
    expect(container.querySelector('.tree-list')).toBeInTheDocument();
    expect(container.querySelector('.tree-node')).toBeInTheDocument();
    expect(container.querySelector('.tree-leaf')).toBeInTheDocument();
  });

  it('renders child nodes with tree-children class', () => {
    const { container } = render(FacetedSearch, { props: { treeData: sampleTreeData } });
    expect(container.querySelector('.tree-children')).toBeInTheDocument();
  });

  it('expanded class applied when node is expanded', () => {
    const { container } = render(FacetedSearch, { props: { treeData: sampleTreeData } });
    expect(container.querySelector('.tree-children.expanded')).toBeInTheDocument();
  });
});