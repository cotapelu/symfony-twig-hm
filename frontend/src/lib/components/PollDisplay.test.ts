import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import PollDisplay from './PollDisplay.svelte';

const basePoll = {
  id: 'p1',
  question: 'Best framework?',
  options: [
    { id: 'o1', text: 'Svelte', votes: 10 },
    { id: 'o2', text: 'React', votes: 5 },
    { id: 'o3', text: 'Vue', votes: 3 },
  ],
  allowMultiple: false,
  isAnonymous: false,
};

describe('PollDisplay Component', () => {
  it('renders nothing when poll is null', async () => {
    const { container } = render(PollDisplay, { props: { poll: null } });
    await tick();
    expect(container.querySelector('.poll-display')).toBeNull();
  });

  it('renders wrapper with poll-display class', async () => {
    const { container } = render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    expect(container.querySelector('.poll-display')).not.toBeNull();
  });

  it('renders poll question as h4', async () => {
    render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    expect(screen.getByText('Best framework?')).not.toBeNull();
  });

  it('renders all poll options', async () => {
    const { container } = render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    const options = container.querySelectorAll('.poll-option');
    expect(options.length).toBe(3);
  });

  it('renders option text', async () => {
    render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    expect(screen.getByText('Svelte')).not.toBeNull();
    expect(screen.getByText('React')).not.toBeNull();
    expect(screen.getByText('Vue')).not.toBeNull();
  });

  it('poll-options container has role=radiogroup', async () => {
    const { container } = render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    const options = container.querySelector('.poll-options');
    expect(options?.getAttribute('role')).toBe('radiogroup');
    expect(options?.getAttribute('aria-label')).toBe('Poll options');
  });

  it('each option has role=radio and tabindex=0', async () => {
    const { container } = render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    const options = container.querySelectorAll('.poll-option');
    options.forEach((opt) => {
      expect(opt.getAttribute('role')).toBe('radio');
      expect(opt.getAttribute('tabindex')).toBe('0');
    });
  });

  it('options have aria-checked=false by default', async () => {
    const { container } = render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    const options = container.querySelectorAll('.poll-option');
    options.forEach((opt) => {
      expect(opt.getAttribute('aria-checked')).toBe('false');
    });
  });

  it('renders single-choice radio indicator by default', async () => {
    const { container } = render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    expect(container.querySelector('.vote-radio')).not.toBeNull();
  });

  it('renders checkbox indicator when allowMultiple=true', async () => {
    const { container } = render(PollDisplay, {
      props: { poll: { ...basePoll, allowMultiple: true } },
    });
    await tick();
    expect(container.querySelector('.vote-checkbox')).not.toBeNull();
  });

  it('does not render results toggle when total votes is 0', async () => {
    const emptyPoll = { ...basePoll, options: basePoll.options.map((o) => ({ ...o, votes: 0 })) };
    const { container } = render(PollDisplay, { props: { poll: emptyPoll } });
    await tick();
    expect(container.querySelector('.results-toggle')).toBeNull();
  });

  it('renders Show Results toggle when total votes > 0', async () => {
    render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    expect(screen.getByText('Show Results')).not.toBeNull();
  });

  it('clicking Show Results toggles to Hide Results', async () => {
    render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    const toggle = screen.getByText('Show Results');
    await fireEvent.click(toggle);
    await tick();
    expect(screen.getByText('Hide Results')).not.toBeNull();
  });

  it('renders total votes count', async () => {
    render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    expect(screen.getByText('18 total votes')).not.toBeNull();
  });

  it('renders singular total vote for 1 vote', async () => {
    const oneVotePoll = { ...basePoll, options: [{ id: 'o1', text: 'A', votes: 1 }] };
    render(PollDisplay, { props: { poll: oneVotePoll } });
    await tick();
    expect(screen.getByText('1 total vote')).not.toBeNull();
  });

  it('does not render total votes when total is 0', async () => {
    const emptyPoll = { ...basePoll, options: basePoll.options.map((o) => ({ ...o, votes: 0 })) };
    const { container } = render(PollDisplay, { props: { poll: emptyPoll } });
    await tick();
    expect(container.querySelector('.total-votes')).toBeNull();
  });

  it('renders Multiple choice label when allowMultiple=true', async () => {
    render(PollDisplay, { props: { poll: { ...basePoll, allowMultiple: true } } });
    await tick();
    expect(screen.getByText('Multiple choice')).not.toBeNull();
  });

  it('renders Anonymous label when isAnonymous=true', async () => {
    render(PollDisplay, { props: { poll: { ...basePoll, isAnonymous: true } } });
    await tick();
    expect(screen.getByText('Anonymous')).not.toBeNull();
  });

  it('does not render Multiple choice label when allowMultiple=false', async () => {
    render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    expect(screen.queryByText('Multiple choice')).toBeNull();
  });

  it('does not render Anonymous label when isAnonymous=false', async () => {
    render(PollDisplay, { props: { poll: basePoll } });
    await tick();
    expect(screen.queryByText('Anonymous')).toBeNull();
  });

  it('renders vote counts and percentages when showResults=true', async () => {
    const { container } = render(PollDisplay, {
      props: { poll: basePoll, showResults: true },
    });
    await tick();
    expect(container.querySelectorAll('.vote-count').length).toBe(3);
    expect(container.querySelectorAll('.percentage').length).toBe(3);
  });

  it('vote counts show plural for non-1', async () => {
    const { container } = render(PollDisplay, {
      props: { poll: basePoll, showResults: true },
    });
    await tick();
    expect(container.querySelector('.vote-count')?.textContent).toContain('10 votes');
  });

  it('vote counts show singular for 1', async () => {
    const oneVotePoll = { ...basePoll, options: [{ id: 'o1', text: 'A', votes: 1 }] };
    const { container } = render(PollDisplay, {
      props: { poll: oneVotePoll, showResults: true },
    });
    await tick();
    expect(container.querySelector('.vote-count')?.textContent).toContain('1 vote');
  });

  it('renders progress bars when showResults=true', async () => {
    const { container } = render(PollDisplay, {
      props: { poll: basePoll, showResults: true },
    });
    await tick();
    expect(container.querySelectorAll('.progress-bar').length).toBe(3);
  });

  it('does not render progress bars when showResults=false', async () => {
    const { container } = render(PollDisplay, {
      props: { poll: basePoll, showResults: false },
    });
    await tick();
    expect(container.querySelector('.progress-bar')).toBeNull();
  });

  it('progress fill width reflects percentage', async () => {
    const { container } = render(PollDisplay, {
      props: { poll: basePoll, showResults: true },
    });
    await tick();
    const fills = container.querySelectorAll('.progress-fill');
    // Svelte 10/18 = 56%
    expect(fills[0]?.getAttribute('style')).toContain('width: 56%');
  });

  it('renders expiry countdown when expiresAt is in future', async () => {
    const future = new Date(Date.now() + 3 * 60 * 60 * 1000).toISOString();
    render(PollDisplay, { props: { poll: { ...basePoll, expiresAt: future } } });
    await tick();
    const expiry = screen.getByText(/remaining/);
    expect(expiry).not.toBeNull();
  });

  it('renders Expired text when expiresAt is in past', async () => {
    const past = new Date(Date.now() - 60 * 1000).toISOString();
    render(PollDisplay, { props: { poll: { ...basePoll, expiresAt: past } } });
    await tick();
    expect(screen.getByText('Expired')).not.toBeNull();
  });

  it('applies expired class when expiresAt is in past', async () => {
    const past = new Date(Date.now() - 60 * 1000).toISOString();
    const { container } = render(PollDisplay, { props: { poll: { ...basePoll, expiresAt: past } } });
    await tick();
    expect(container.querySelector('.poll-display')?.classList.contains('expired')).toBe(true);
  });

  it('does not apply expired class when expiresAt is in future', async () => {
    const future = new Date(Date.now() + 60 * 60 * 1000).toISOString();
    const { container } = render(PollDisplay, { props: { poll: { ...basePoll, expiresAt: future } } });
    await tick();
    expect(container.querySelector('.poll-display')?.classList.contains('expired')).toBe(false);
  });

  it('applies disabled class on options when expired', async () => {
    const past = new Date(Date.now() - 60 * 1000).toISOString();
    const { container } = render(PollDisplay, { props: { poll: { ...basePoll, expiresAt: past } } });
    await tick();
    const options = container.querySelectorAll('.poll-option');
    options.forEach((opt) => {
      expect(opt.classList.contains('disabled')).toBe(true);
    });
  });

  it('renders selected class when user has voted', async () => {
    const pollWithUserVote = {
      ...basePoll,
      userVotes: { user1: [0] },
    };
    const { container } = render(PollDisplay, {
      props: { poll: pollWithUserVote, currentUserId: 'user1' },
    });
    await tick();
    const selected = container.querySelectorAll('.poll-option.selected');
    expect(selected.length).toBe(1);
  });

  it('aria-checked=true on user-voted option', async () => {
    const pollWithUserVote = {
      ...basePoll,
      userVotes: { user1: [0] },
    };
    const { container } = render(PollDisplay, {
      props: { poll: pollWithUserVote, currentUserId: 'user1' },
    });
    await tick();
    const firstOption = container.querySelector('.poll-option');
    expect(firstOption?.getAttribute('aria-checked')).toBe('true');
  });

  it('renders vote-status message when hasVoted=true', async () => {
    render(PollDisplay, { props: { poll: basePoll, hasVoted: true } });
    await tick();
    expect(screen.getByText(/Your vote submitted/)).not.toBeNull();
  });

  it('renders plural vote-status when user has multiple votes', async () => {
    const multiVotePoll = {
      ...basePoll,
      allowMultiple: true,
      userVotes: { user1: [0, 1] },
    };
    render(PollDisplay, {
      props: { poll: multiVotePoll, currentUserId: 'user1', hasVoted: true },
    });
    await tick();
    expect(screen.getByText(/Your votes submitted/)).not.toBeNull();
  });

  it('does not render vote-status when hasVoted=false', async () => {
    render(PollDisplay, { props: { poll: basePoll, hasVoted: false } });
    await tick();
    expect(screen.queryByText(/Your vote/)).toBeNull();
  });

  it('does not render vote-status when expired', async () => {
    const past = new Date(Date.now() - 60 * 1000).toISOString();
    render(PollDisplay, {
      props: { poll: { ...basePoll, expiresAt: past }, hasVoted: true },
    });
    await tick();
    expect(screen.queryByText(/Your vote/)).toBeNull();
  });

  it('renders vote-checkmark when user has voted for that option', async () => {
    const pollWithUserVote = {
      ...basePoll,
      userVotes: { user1: [0] },
    };
    const { container } = render(PollDisplay, {
      props: { poll: pollWithUserVote, currentUserId: 'user1' },
    });
    await tick();
    expect(container.querySelector('.vote-checkmark')).not.toBeNull();
  });
});
