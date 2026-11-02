import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import Poll from './Poll.svelte';

const basePoll = {
  id: 'poll-1',
  question: 'Best color?',
  options: [
    { id: 'opt-1', text: 'Red', voteCount: 10, isVoted: true },
    { id: 'opt-2', text: 'Blue', voteCount: 5, isVoted: false },
  ],
  allowMultiple: false,
};

describe('Poll Component', () => {
  it('renders empty container when poll is null and not creating', async () => {
    const { container } = render(Poll, { props: { poll: null, isCreating: false } });
    await tick();
    expect(container.querySelector('.poll-container')).not.toBeNull();
  });

  it('renders poll display when poll is provided', async () => {
    const { container } = render(Poll, { props: { poll: basePoll, isCreating: false } });
    await tick();
    expect(container.querySelector('.poll-display')).not.toBeNull();
  });

  it('renders single choice indicator by default', async () => {
    render(Poll, { props: { poll: basePoll, isCreating: false } });
    await tick();
    expect(screen.getByText('Single Choice')).not.toBeNull();
  });

  it('renders multiple choice indicator when allowMultiple=true', async () => {
    render(Poll, { props: { poll: { ...basePoll, allowMultiple: true }, isCreating: false } });
    await tick();
    expect(screen.getByText('Multiple Choice')).not.toBeNull();
  });

  it('renders poll question in header', async () => {
    const { container } = render(Poll, { props: { poll: basePoll, isCreating: false } });
    await tick();
    const header = container.querySelector('.poll-header h4');
    expect(header?.textContent).toContain('Best color?');
  });

  it('renders option buttons', async () => {
    render(Poll, { props: { poll: basePoll, isCreating: false } });
    await tick();
    expect(screen.getByText('Red')).not.toBeNull();
    expect(screen.getByText('Blue')).not.toBeNull();
  });

  it('renders vote count and percentage after voting', async () => {
    render(Poll, { props: { poll: basePoll, isCreating: false } });
    await tick();
    expect(screen.getByText(/10 votes/)).not.toBeNull();
    expect(screen.getByText(/67%/)).not.toBeNull();
  });

  it('shows total votes line after voting', async () => {
    render(Poll, { props: { poll: basePoll, isCreating: false } });
    await tick();
    expect(screen.getByText(/Total: 15 votes/)).not.toBeNull();
  });

  it('clicking option calls onVote and dispatch', async () => {
    const onVote = vi.fn();
    const { container } = render(Poll, { props: { poll: basePoll, isCreating: false, onVote } });
    await tick();
    const blueButton = screen.getByText('Blue').closest('button');
    if (blueButton) {
      await fireEvent.click(blueButton);
    }
    expect(onVote).toHaveBeenCalledWith('poll-1', 1);
  });



  it('renders empty when poll options is empty array', async () => {
    const emptyPoll = { ...basePoll, options: [] };
    const { container } = render(Poll, { props: { poll: emptyPoll, isCreating: false } });
    await tick();
    expect(container.querySelector('.poll-option')).toBeNull();
  });

  it('renders poll container with class', async () => {
    const { container } = render(Poll, { props: { poll: basePoll, isCreating: false } });
    await tick();
    expect(container.querySelector('.poll-container')).not.toBeNull();
  });
});
