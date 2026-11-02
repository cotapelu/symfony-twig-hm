import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import GroupCard from './GroupCard.svelte';

describe('GroupCard Extra Interactions', () => {
  const mockGroup = {
    name: 'Dev Team',
    members: 42,
    description: 'A team for developers.'
  };

  it('triggers onView when card is clicked', async () => {
    const onView = vi.fn();
    render(GroupCard, { props: { group: mockGroup, onView } });
    const card = screen.getByText('Dev Team').closest('.group-card')!;
    await fireEvent.click(card);
    expect(onView).toHaveBeenCalledWith(mockGroup);
  });

  it('join button click does not trigger onView due to stopPropagation', async () => {
    const onView = vi.fn();
    const onJoin = vi.fn();
    render(GroupCard, { props: { group: mockGroup, onView, onJoin } });
    const joinBtn = screen.getByText('Join Group');
    await fireEvent.click(joinBtn);
    expect(onJoin).toHaveBeenCalledWith(mockGroup);
    expect(onView).not.toHaveBeenCalled();
  });

  it('card responds to Enter key to trigger onView', async () => {
    const onView = vi.fn();
    render(GroupCard, { props: { group: mockGroup, onView } });
    const card = screen.getByText('Dev Team').closest('.group-card')!;
    await fireEvent.keyDown(card, { key: 'Enter' });
    expect(onView).toHaveBeenCalledWith(mockGroup);
  });

  it('card responds to Space key to trigger onView', async () => {
    const onView = vi.fn();
    render(GroupCard, { props: { group: mockGroup, onView } });
    const card = screen.getByText('Dev Team').closest('.group-card')!;
    await fireEvent.keyDown(card, { key: ' ' });
    expect(onView).toHaveBeenCalledWith(mockGroup);
  });

  it('does not respond to other keys', async () => {
    const onView = vi.fn();
    render(GroupCard, { props: { group: mockGroup, onView } });
    const card = screen.getByText('Dev Team').closest('.group-card')!;
    await fireEvent.keyDown(card, { key: 'a' });
    expect(onView).not.toHaveBeenCalled();
  });
});
