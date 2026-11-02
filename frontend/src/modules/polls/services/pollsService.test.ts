import { describe, it, expect, vi, beforeEach } from 'vitest';
import { PollsService } from './pollsService';

global.fetch = vi.fn();

describe('PollsService', () => {
  let service: PollsService;

  beforeEach(() => {
    service = new PollsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getPolls', () => {
    it('should fetch all polls', async () => {
      const polls = [{ id: 'p1', question: 'Favorite color?' }];
      (global.fetch as any).mockResolvedValue(mockResponse(polls));
      const result = await service.getPolls();
      expect(result).toEqual(polls);
      expect(fetch).toHaveBeenCalledWith('/api/polls');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getPolls()).rejects.toThrow('Failed to fetch polls');
    });
  });

  describe('getPoll', () => {
    it('should fetch poll by id', async () => {
      const poll = { id: 'p1' };
      (global.fetch as any).mockResolvedValue(mockResponse(poll));
      const result = await service.getPoll('p1');
      expect(result).toEqual(poll);
      expect(fetch).toHaveBeenCalledWith('/api/polls/p1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getPoll('p1')).rejects.toThrow('Failed to fetch poll');
    });
  });

  describe('createPoll', () => {
    it('should create a poll', async () => {
      const created = { id: 'new', question: 'New poll' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const request = { question: 'New poll', options: ['Yes', 'No'] };
      const result = await service.createPoll(request);
      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/polls', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createPoll({ question: 'X', options: [] })).rejects.toThrow('Failed to create poll');
    });
  });

  describe('vote', () => {
    it('should submit a vote', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      const request = { pollId: 'p1', optionIds: ['opt1'] };
      await service.vote(request);
      expect(fetch).toHaveBeenCalledWith('/api/polls/p1/vote', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ optionIds: ['opt1'] })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.vote({ pollId: 'p1', optionIds: ['opt1'] })).rejects.toThrow('Failed to submit vote');
    });
  });

  describe('closePoll', () => {
    it('should close a poll', async () => {
      const poll = { id: 'p1', closed: true };
      (global.fetch as any).mockResolvedValue(mockResponse(poll));
      const result = await service.closePoll('p1');
      expect(result).toEqual(poll);
      expect(fetch).toHaveBeenCalledWith('/api/polls/p1/close', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.closePoll('p1')).rejects.toThrow('Failed to close poll');
    });
  });
});
