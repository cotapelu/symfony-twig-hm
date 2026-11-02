import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen, within } from '@testing-library/svelte';
import { tick } from 'svelte';
import GroupCard from './GroupCard.svelte';

describe('GroupCard', () => {
  const mockGroup = {
    name: 'Dev Team',
    members: 42,
    description: 'A team for developers.'
  };

  const renderGroupCard = (props = {}) => render(GroupCard, { props });

  it('renders nothing when group is null', () => {
    const { container } = renderGroupCard({ group: null });
    expect(container.querySelector('.group-card')).toBeNull();
  });

  it('renders group name, members, and description', () => {
    renderGroupCard({ group: mockGroup });
    expect(screen.getByText('Dev Team')).toBeInTheDocument();
    expect(screen.getByText('42 members')).toBeInTheDocument();
    expect(screen.getByText('A team for developers.')).toBeInTheDocument();
  });

  it('displays 0 members when members undefined', () => {
    const group = { name: 'Solo', description: 'Solo group' };
    renderGroupCard({ group });
    expect(screen.getByText('0 members')).toBeInTheDocument();
  });

  it('does not show description when missing', () => {
    const group = { name: 'NoDesc', members: 5 };
    renderGroupCard({ group });
    // Description element not rendered because description is undefined
    expect(screen.queryByText('NoDesc')).toBeInTheDocument();
    // No <p> with description
    expect(screen.queryByRole('paragraph')).not.toBeInTheDocument();
  });

  it('join button is present and is a button element', () => {
    renderGroupCard({ group: mockGroup });
    const joinBtn = screen.getByText('Join Group');
    expect(joinBtn).toBeInTheDocument();
    expect(joinBtn.tagName).toBe('BUTTON');
  });

  it('clicking join button calls onJoin with group', async () => {
    const onJoin = vi.fn();
    renderGroupCard({ group: mockGroup, onJoin });
    const joinBtn = screen.getByText('Join Group');
    await fireEvent.click(joinBtn);
    await tick();
    expect(onJoin).toHaveBeenCalledTimes(1);
    expect(onJoin).toHaveBeenCalledWith(mockGroup);
  });

  it('clicking the card (outside join button) calls onView with group', async () => {
    const onView = vi.fn();
    const { container } = renderGroupCard({ group: mockGroup, onView });
    const cardDiv = container.querySelector('.group-card') as HTMLElement;
    await fireEvent.click(cardDiv);
    await tick();
    expect(onView).toHaveBeenCalledTimes(1);
    expect(onView).toHaveBeenCalledWith(mockGroup);
  });

  it('join button click does not propagate to card', async () => {
    const onJoin = vi.fn();
    const onView = vi.fn();
    const { container } = renderGroupCard({ group: mockGroup, onJoin, onView });
    const joinBtn = screen.getByText('Join Group');
    await fireEvent.click(joinBtn);
    await tick();
    expect(onJoin).toHaveBeenCalledTimes(1);
    expect(onView).not.toHaveBeenCalled();
  });

  it('card has clickable styling; role and tabindex', () => {
    const { container } = renderGroupCard({ group: mockGroup });
    const cardDiv = container.querySelector('.group-card') as HTMLElement;
    expect(cardDiv).toHaveAttribute('role', 'button');
    expect(cardDiv).toHaveAttribute('tabindex', '0');
  });
});
