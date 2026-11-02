import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  pollsStore,
  polls,
  loading as pollsLoading,
  error as pollsError,
  loadPolls,
  createPoll,
  vote,
  closePoll,
  getResults,
  clearError,
  hasVoted,
  getUserVote,
  reset
} from './pollsStore';
import { pollsService } from '../services/pollsService';
import type { Poll, PollOption, CreatePollRequest } from '../contracts/pollsTypes';

function getCurrentState() {
  let current: any;
  const unsub = pollsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockPollOption = (overrides: Partial<PollOption> = {}): PollOption => ({
  id: `opt-${Math.random().toString(36).substr(2, 5)}`,
  text: 'Option',
  votes: 0,
  ...overrides
});

const mockPoll = (overrides: Partial<Poll> = {}): Poll => ({
  id: `poll-${Math.random().toString(36).substr(2, 5)}`,
  question: 'Test Question?',
  options: [mockPollOption(), mockPollOption()],
  totalVotes: 0,
  status: 'active',
  createdAt: new Date(),
  allowMultiple: false,
  showResults: true,
  ...overrides
});

const mockCreateRequest = (overrides: Partial<CreatePollRequest> = {}): CreatePollRequest => ({
  question: 'New Poll?',
  options: ['Yes', 'No'],
  allowMultiple: false,
  showResults: true,
  ...overrides
});

describe('PollsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    reset();
  });

  describe('initial state', () => {
    it('has empty polls and userVotes, default loading/error', () => {
      const state = getCurrentState();
      expect(state.polls).toEqual([]);
      expect(state.userVotes).toBeInstanceOf(Map);
      expect(state.userVotes.size).toBe(0);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('polls derived returns polls array', async () => {
      let pollsList: Poll[] = [];
      const unsub = polls.subscribe(p => { pollsList = p; });
      expect(pollsList).toEqual([]);
      unsub();
    });

    it('hasVoted returns false initially', () => {
      expect(hasVoted('poll-1')).toBe(false);
    });

    it('getUserVote returns undefined initially', () => {
      expect(getUserVote('poll-1')).toBeUndefined();
    });
  });

  describe('loadPolls', () => {
    it('loads polls and updates state', async () => {
      const pollsList = [mockPoll(), mockPoll()];
      vi.spyOn(pollsService, 'getPolls').mockResolvedValue(pollsList);
      await loadPolls();
      const state = getCurrentState();
      expect(state.polls).toEqual(pollsList);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(pollsService, 'getPolls').mockRejectedValue(new Error('Fetch failed'));
      await loadPolls();
      const state = getCurrentState();
      expect(state.error).toBe('Fetch failed');
      expect(state.polls).toEqual([]);
    });
  });

  describe('createPoll', () => {
    it('creates poll and adds to list', async () => {
      const newPoll = mockPoll({ id: 'new-poll' });
      vi.spyOn(pollsService, 'createPoll').mockResolvedValue(newPoll);
      const request = mockCreateRequest();
      const result = await createPoll(request);
      expect(result).toEqual(newPoll);
      let state = getCurrentState();
      expect(state.polls[0]).toEqual(newPoll);
    });

    it('propagates errors', async () => {
      vi.spyOn(pollsService, 'createPoll').mockRejectedValue(new Error('Validation'));
      try {
        await createPoll(mockCreateRequest());
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Validation');
    });
  });

  describe('vote', () => {
    it('records vote and updates poll options/total', async () => {
      const poll = mockPoll({ id: 'poll-1', options: [
        { id: 'opt1', text: 'A', votes: 0 },
        { id: 'opt2', text: 'B', votes: 0 }
      ]});
      vi.spyOn(pollsService, 'getPolls').mockResolvedValue([poll]);
      await loadPolls();
      vi.spyOn(pollsService, 'vote').mockResolvedValue(undefined);
      await vote({ pollId: 'poll-1', optionIds: ['opt1'] });
      let state = getCurrentState();
      const updatedPoll = state.polls.find(p => p.id === 'poll-1');
      expect(updatedPoll?.options.find(o => o.id === 'opt1')?.votes).toBe(1);
      expect(updatedPoll?.options.find(o => o.id === 'opt2')?.votes).toBe(0);
      expect(updatedPoll?.totalVotes).toBe(1);
      expect(state.userVotes.get('poll-1')).toEqual(['opt1']);
      expect(hasVoted('poll-1')).toBe(true);
      expect(getUserVote('poll-1')).toEqual(['opt1']);
    });

    it('allows multiple options if allowMultiple is true', async () => {
      const poll = mockPoll({ id: 'poll-1', allowMultiple: true, options: [
        { id: 'opt1', votes: 0 }, { id: 'opt2', votes: 0 }
      ]});
      vi.spyOn(pollsService, 'getPolls').mockResolvedValue([poll]);
      await loadPolls();
      vi.spyOn(pollsService, 'vote').mockResolvedValue(undefined);
      await vote({ pollId: 'poll-1', optionIds: ['opt1', 'opt2'] });
      const state = getCurrentState();
      const updatedPoll = state.polls.find(p => p.id === 'poll-1');
      // totalVotes increments by 1 per ballot, not per option
      expect(updatedPoll?.totalVotes).toBe(1);
      expect(updatedPoll?.options.find(o => o.id === 'opt1')?.votes).toBe(1);
      expect(updatedPoll?.options.find(o => o.id === 'opt2')?.votes).toBe(1);
      expect(state.userVotes.get('poll-1')).toEqual(['opt1', 'opt2']);
    });

    it('throws error if already voted', async () => {
      const poll = mockPoll({ id: 'poll-1', options: [{ id: 'opt1', votes: 0 }]});
      vi.spyOn(pollsService, 'getPolls').mockResolvedValue([poll]);
      await loadPolls();
      // First vote
      vi.spyOn(pollsService, 'vote').mockResolvedValue(undefined);
      await vote({ pollId: 'poll-1', optionIds: ['opt1'] });
      // Second vote should throw
      try {
        await vote({ pollId: 'poll-1', optionIds: ['opt1'] });
      } catch (e: any) {
        expect(e.message).toBe('You have already voted on this poll');
      }
      // Ensure state unchanged
      let state = getCurrentState();
      expect(state.userVotes.get('poll-1')).toEqual(['opt1']);
      expect(state.polls.find(p => p.id === 'poll-1')?.totalVotes).toBe(1);
    });

    it('handles service error', async () => {
      const poll = mockPoll({ id: 'poll-1', options: [{ id: 'opt1', votes: 0 }]});
      vi.spyOn(pollsService, 'getPolls').mockResolvedValue([poll]);
      await loadPolls();
      vi.spyOn(pollsService, 'vote').mockRejectedValue(new Error('Vote failed'));
      try {
        await vote({ pollId: 'poll-1', optionIds: ['opt1'] });
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Vote failed');
      // userVotes should not be updated
      expect(state.userVotes.has('poll-1')).toBe(false);
    });
  });

  describe('closePoll', () => {
    it('closes poll and updates status', async () => {
      const poll = mockPoll({ id: 'poll-1', status: 'active' });
      vi.spyOn(pollsService, 'getPolls').mockResolvedValue([poll]);
      await loadPolls();
      const closedPoll = { ...poll, status: 'closed' as const };
      vi.spyOn(pollsService, 'closePoll').mockResolvedValue(closedPoll);
      await closePoll('poll-1');
      let state = getCurrentState();
      expect(state.polls.find(p => p.id === 'poll-1')?.status).toBe('closed');
    });

    it('handles error', async () => {
      vi.spyOn(pollsService, 'getPolls').mockResolvedValue([mockPoll({ id: 'poll-1' })]);
      await loadPolls();
      vi.spyOn(pollsService, 'closePoll').mockRejectedValue(new Error('Close failed'));
      try {
        await closePoll('poll-1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Close failed');
    });
  });

  describe('getResults', () => {
    it('returns poll results via service', async () => {
      const poll = mockPoll();
      vi.spyOn(pollsService, 'getResults').mockResolvedValue(poll);
      const result = await getResults('poll-1');
      expect(result).toEqual(poll);
      expect(pollsService.getResults).toHaveBeenCalledWith('poll-1');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(pollsService, 'getPolls').mockRejectedValue(new Error('test'));
      try { await loadPolls(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const pollsList = [mockPoll()];
      vi.spyOn(pollsService, 'getPolls').mockResolvedValue(pollsList);
      await loadPolls();
      // Simulate vote to add userVotes
      vi.spyOn(pollsService, 'vote').mockResolvedValue(undefined);
      await vote({ pollId: 'poll-1', optionIds: ['opt1'] });
      let state = getCurrentState();
      expect(state.polls.length).toBeGreaterThan(0);
      expect(state.userVotes.size).toBeGreaterThan(0);
      // Reset
      reset();
      state = getCurrentState();
      expect(state.polls).toEqual([]);
      expect(state.userVotes.size).toBe(0);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
