import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import userEvent from '@testing-library/user-event';
import MockButton from './molecules/MockButton.svelte';
import MockIcon from './molecules/MockIcon.svelte';

// Mock the exact relative imports used by FieldGroups.svelte
vi.mock('./Button.svelte', () => ({ default: MockButton }));
vi.mock('./Icon.svelte', () => ({ default: MockIcon }));

// Import after mocks are set up
import FieldGroups from './FieldGroups.svelte';

describe('FieldGroups Component', () => {
  const sampleGroups = [
    {
      id: 'group1',
      title: 'Personal Information',
      fields: [] // Empty fields to avoid svelte:component issues
    },
    {
      id: 'group2',
      title: 'Address',
      fields: [], // Empty fields to avoid svelte:component issues
      collapsed: true
    }
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without error when groups is empty', () => {
    render(FieldGroups, { props: { groups: [] } });
    const container = document.querySelector('.field-groups');
    expect(container).toBeInTheDocument();
  });

  it('renders group headers with titles', () => {
    render(FieldGroups, { props: { groups: sampleGroups } });
    expect(screen.getByText('Personal Information')).toBeInTheDocument();
    expect(screen.getByText('Address')).toBeInTheDocument();
  });

  it('renders group1 expanded by default (not collapsed)', () => {
    render(FieldGroups, { props: { groups: sampleGroups } });
    const group1Button = screen.getByText('Personal Information').closest('button');
    expect(group1Button).toHaveAttribute('aria-expanded', 'true');
  });

  it('renders group2 collapsed by default', () => {
    render(FieldGroups, { props: { groups: sampleGroups } });
    const group2Button = screen.getByText('Address').closest('button');
    expect(group2Button).toHaveAttribute('aria-expanded', 'false');
  });

  it('toggles group when header is clicked', async () => {
    render(FieldGroups, { props: { groups: sampleGroups } });
    const group1Button = screen.getByText('Personal Information').closest('button');
    await userEvent.click(group1Button!);
    // Clicking should toggle - group1 was expanded, now collapsed
    await waitFor(() => {
      expect(group1Button).toHaveAttribute('aria-expanded', 'false');
    });
  });

  it('applies defaultCollapsed to groups without explicit collapsed', () => {
    render(FieldGroups, { 
      props: { 
        groups: [{ id: 'g1', title: 'Test', fields: [] }], 
        defaultCollapsed: true 
      } 
    });
    const button = screen.getByText('Test').closest('button');
    expect(button).toHaveAttribute('aria-expanded', 'false');
  });

  it('handles groups with empty fields array', () => {
    render(FieldGroups, { 
      props: { groups: [{ id: 'empty', title: 'Empty Group', fields: [] }] } 
    });
    expect(screen.getByText('Empty Group')).toBeInTheDocument();
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(FieldGroups, { props: { groups: sampleGroups } });
    const { container: container2 } = render(FieldGroups, { props: { groups: [] } });
    expect(container1).toBeTruthy();
    expect(container2).toBeTruthy();
  });

  it('handles dynamic groups array changes', async () => {
    const { rerender } = render(FieldGroups, { 
      props: { groups: [{ id: 'g1', title: 'Group 1', fields: [] }] } 
    });
    expect(screen.getByText('Group 1')).toBeInTheDocument();
    
    await rerender({ 
      groups: [
        { id: 'g1', title: 'Group 1', fields: [] },
        { id: 'g2', title: 'Group 2', fields: [] }
      ] 
    });
    expect(screen.getByText('Group 1')).toBeInTheDocument();
    expect(screen.getByText('Group 2')).toBeInTheDocument();
  });

  it('handles group with undefined collapsed (uses defaultCollapsed)', () => {
    render(FieldGroups, { 
      props: { 
        groups: [{ id: 'g1', title: 'Test', fields: [] }], 
        defaultCollapsed: false 
      } 
    });
    const button = screen.getByText('Test').closest('button');
    expect(button).toHaveAttribute('aria-expanded', 'true');
  });

  it('renders field group container with correct classes', () => {
    render(FieldGroups, { props: { groups: sampleGroups } });
    const container = screen.getByText('Personal Information').closest('.field-groups');
    expect(container).toHaveClass('field-groups');
    expect(container).toHaveClass('space-y-4');
  });

  it('renders each group with border and rounded classes', () => {
    render(FieldGroups, { props: { groups: sampleGroups } });
    const groupContainers = screen.getAllByText('Personal Information').map(el => el.closest('.field-group'));
    groupContainers.forEach(container => {
      expect(container).toHaveClass('field-group');
      expect(container).toHaveClass('border');
      expect(container).toHaveClass('rounded-lg');
      expect(container).toHaveClass('overflow-hidden');
    });
  });

  it('renders group header with padding and hover styles', () => {
    render(FieldGroups, { props: { groups: sampleGroups } });
    const headers = screen.getAllByRole('button', { name: /Personal Information|Address/ });
    headers.forEach(header => {
      expect(header).toHaveClass('w-full');
      expect(header).toHaveClass('flex');
      expect(header).toHaveClass('items-center');
      expect(header).toHaveClass('justify-between');
      expect(header).toHaveClass('p-4');
    });
  });

  it('shows icon for each group', () => {
    render(FieldGroups, { props: { groups: sampleGroups } });
    const group1Button = screen.getByText('Personal Information').closest('button');
    const icon = group1Button?.querySelector('svg[role="img"]');
    expect(icon).toBeInTheDocument();
  });
});