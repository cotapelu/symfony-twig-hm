import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import TreeSelect from './TreeSelect.svelte';

describe('TreeSelect', () => {
  const sampleTreeData = [
    {
      key: '1',
      title: 'Parent 1',
      children: [
        { key: '1-1', title: 'Child 1-1' },
        { key: '1-2', title: 'Child 1-2' },
      ],
    },
    {
      key: '2',
      title: 'Parent 2',
      children: [
        { key: '2-1', title: 'Child 2-1' },
      ],
    },
    { key: '3', title: 'Leaf Node' },
  ];

  it('renders with default props', () => {
    render(TreeSelect);
    const selector = document.querySelector('.tree-select');
    expect(selector).toBeInTheDocument();
  });

  it('renders placeholder when no value', () => {
    render(TreeSelect, { props: { placeholder: 'Select...' } });
    expect(screen.getByText('Select...')).toBeInTheDocument();
  });

  it('renders custom placeholder', () => {
    render(TreeSelect, { props: { placeholder: 'Choose option' } });
    expect(screen.getByText('Choose option')).toBeInTheDocument();
  });

  it('shows dropdown when clicked', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    expect(document.querySelector('.tree-select-dropdown')).toBeInTheDocument();
    expect(document.querySelector('.tree-select')).toHaveClass('tree-select-open');
  });

  it('hides dropdown when clicked again', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    await fireEvent.click(selector!);
    expect(document.querySelector('.tree-select-dropdown')).not.toBeInTheDocument();
    expect(document.querySelector('.tree-select')).not.toHaveClass('tree-select-open');
  });

  it('shows search input when showSearch is true', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData, showSearch: true } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    expect(document.querySelector('.tree-select-search-input')).toBeInTheDocument();
  });

  it('hides search input when showSearch is false', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData, showSearch: false } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    expect(document.querySelector('.tree-select-search-input')).not.toBeInTheDocument();
  });

  it('renders clear button when allowClear is true and value exists', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData, value: '1', allowClear: true } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    expect(screen.getByText('✕')).toBeInTheDocument();
  });

  it('does not show clear button when allowClear is false', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData, value: '1', allowClear: false } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    expect(screen.queryByText('✕')).not.toBeInTheDocument();
  });

  it('does not show clear button when no value', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData, allowClear: true } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    expect(screen.queryByText('✕')).not.toBeInTheDocument();
  });

  it('applies disabled class when disabled is true', () => {
    render(TreeSelect, { props: { disabled: true } });
    expect(document.querySelector('.tree-select')).toHaveClass('tree-select-disabled');
  });

  it('does not open dropdown when disabled', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData, disabled: true } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    expect(document.querySelector('.tree-select-dropdown')).not.toBeInTheDocument();
  });

  it('applies custom className', () => {
    render(TreeSelect, { props: { className: 'custom-tree-select' } });
    expect(document.querySelector('.tree-select')).toHaveClass('custom-tree-select');
  });

  it('applies custom dropdownClassName', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData, dropdownClassName: 'custom-dropdown' } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    expect(document.querySelector('.tree-select-dropdown')).toHaveClass('custom-dropdown');
  });

  it('shows selected value in placeholder when single select', () => {
    // Note: Tree component renders as segmented buttons, not tree structure
    render(TreeSelect, { props: { treeData: sampleTreeData, value: '3' } });
    expect(screen.getByText('Leaf Node')).toBeInTheDocument();
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(TreeSelect, { props: { treeData: sampleTreeData } });
    const { container: container2 } = render(TreeSelect, { props: { treeData: sampleTreeData, className: 'second-select' } });
    expect(container1).toBeInTheDocument();
    expect(container2).toBeInTheDocument();
    expect(container2.querySelector('.tree-select')).toHaveClass('second-select');
  });

  it('has tree-select container', () => {
    render(TreeSelect);
    expect(document.querySelector('.tree-select')).toBeInTheDocument();
  });

  it('has selector with arrow', () => {
    render(TreeSelect);
    const selector = document.querySelector('.tree-select-selector');
    expect(selector).toBeInTheDocument();
    expect(screen.getByText('▼')).toBeInTheDocument();
  });

  it('shows arrow pointing up when open', async () => {
    render(TreeSelect, { props: { treeData: sampleTreeData } });
    const selector = document.querySelector('.tree-select-selector');
    await fireEvent.click(selector!);
    expect(screen.getByText('▲')).toBeInTheDocument();
  });
});