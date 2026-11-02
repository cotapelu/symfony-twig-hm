import { describe, it, expect, vi, beforeEach } from 'vitest';
import { pollsStore } from './polls';
import { PHP } from '../lib/api';

vi.mock('../lib/api', () => ({
  PHP: {
    getPolls: vi.fn(),
    createPoll: vi.fn(),
    votePoll: vi.fn(),
    deletePoll: vi.fn(),
  }
}));

describe('PollsStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    pollsStore.reset();
    pollsStore.subscribe(s => { state = s; });
  });

  const mockPoll = (id: string, question: string, conversationId: string = 'c1'): any => ({
    id,
    question,
    options: [{ id: 'o1', text: 'A', votes: 0 }],
    conversationId,
    createdAt: new Date().toISOString()
  });

  describe('loadPolls', () => {
    it('should load polls and group by conversation', async () => {
      const polls = [mockPoll('p1', 'Q1', 'c1'), mockPoll('p2', 'Q2', 'c2')];
      (PHP.getPolls as any).mockResolvedValue(polls);

      await pollsStore.loadPolls();

      expect(state.polls).toHaveProperty('global');
      expect(state.polls.global).toHaveLength(2);
      expect(pollsStore.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('should handle empty polls', async () => {
      (PHP.getPolls as any).mockResolvedValue([]);
      await pollsStore.loadPolls();
      expect(state.polls).toEqual({});
    });

    it('should set error on failure', async () => {
      (PHP.getPolls as any).mockRejectedValue(new Error('API fail'));
      await expect(pollsStore.loadPolls()).rejects.toThrow('API fail');
      expect(pollsStore.loading).toBe(false);
      expect(pollsStore.error).toBe('API fail');
    });
  });

  describe('createPoll', () => {
    it('should create poll via API and add to store', async () => {
      const poll = mockPoll('p1', 'New?', 'c1');
      (PHP.createPoll as any).mockResolvedValue(poll);

      const result = await pollsStore.createPoll('c1', 'New?', ['A', 'B']);

      expect(result).toEqual(poll);
      expect(state.polls.c1).toContain(poll);
      expect(PHP.createPoll).toHaveBeenCalledWith({ conversationId: 'c1', question: 'New?', options: ['A', 'B'] });
    });

    it('should handle create error', async () => {
      (PHP.createPoll as any).mockRejectedValue(new Error('Create failed'));
      await expect(pollsStore.createPoll('c1', 'Q', ['A'])).rejects.toThrow('Create failed');
      expect(pollsStore.error).toBe('Create failed');
    });
  });

  describe('addPoll', () => {
    it('should add poll locally', () => {
      const poll = mockPoll('p1', 'Local', 'c1');
      pollsStore.addPoll('c1', poll);
      expect(state.polls.c1).toContain(poll);
    });
  });

  describe('votePoll', () => {
    it('should vote and update poll in store', async () => {
      const existing = mockPoll('p1', 'Q', 'c1');
      pollsStore.addPoll('c1', existing);
      const updated = { ...existing, options: [{ id: 'o1', text: 'A', votes: 1 }] };
      (PHP.votePoll as any).mockResolvedValue(updated);

      const result = await pollsStore.votePoll('p1', 'o1');

      expect(result).toEqual(updated);
      expect(state.polls.c1[0]).toEqual(updated);
    });

    it('should handle vote error', async () => {
      (PHP.votePoll as any).mockRejectedValue(new Error('Vote failed'));
      await expect(pollsStore.votePoll('p1', 'o1')).rejects.toThrow('Vote failed');
      expect(pollsStore.error).toBe('Vote failed');
    });
  });

  describe('deletePoll', () => {
    it('should delete poll from store', async () => {
      const poll = mockPoll('p1', 'Q', 'c1');
      pollsStore.addPoll('c1', poll);
      expect(state.polls.c1).toHaveLength(1);
      (PHP.deletePoll as any).mockResolvedValue(undefined);
      await pollsStore.deletePoll('p1');
      expect(state.polls.c1).toHaveLength(0);
    });

    it('should handle delete error', async () => {
      const poll = mockPoll('p1', 'Q', 'c1');
      pollsStore.addPoll('c1', poll);
      (PHP.deletePoll as any).mockRejectedValue(new Error('Delete failed'));
      await expect(pollsStore.deletePoll('p1')).rejects.toThrow('Delete failed');
      expect(pollsStore.error).toBe('Delete failed');
      expect(state.polls.c1).toHaveLength(1); // still there
    });
  });

  describe('vote (alias)', () => {
    it('should call votePoll', async () => {
      const existing = mockPoll('p1', 'Q', 'c1');
      pollsStore.addPoll('c1', existing);
      const updated = { ...existing, options: [{ id: 'o1', text: 'A', votes: 1 }] };
      (PHP.votePoll as any).mockResolvedValue(updated);
      const result = await pollsStore.vote('p1', 'o1');
      expect(result).toEqual(updated);
    });
  });
});
