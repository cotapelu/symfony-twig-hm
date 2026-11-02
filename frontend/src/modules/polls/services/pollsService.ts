import type { Poll, CreatePollRequest, VoteRequest } from '../contracts/pollsTypes';

const API_BASE = '/api/polls';

export class PollsService {
  async getPolls(): Promise<Poll[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) {
      throw new Error('Failed to fetch polls');
    }
    return response.json();
  }

  async getPoll(pollId: string): Promise<Poll> {
    const response = await fetch(`${API_BASE}/${pollId}`);
    if (!response.ok) {
      throw new Error('Failed to fetch poll');
    }
    return response.json();
  }

  async createPoll(request: CreatePollRequest): Promise<Poll> {
    const response = await fetch(`${API_BASE}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) {
      throw new Error('Failed to create poll');
    }
    return response.json();
  }

  async vote(request: VoteRequest): Promise<void> {
    const response = await fetch(`${API_BASE}/${request.pollId}/vote`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ optionIds: request.optionIds })
    });
    if (!response.ok) {
      throw new Error('Failed to submit vote');
    }
  }

  async closePoll(pollId: string): Promise<Poll> {
    const response = await fetch(`${API_BASE}/${pollId}/close`, {
      method: 'POST'
    });
    if (!response.ok) {
      throw new Error('Failed to close poll');
    }
    return response.json();
  }

  async getResults(pollId: string): Promise<Poll> {
    return this.getPoll(pollId);
  }
}

export const pollsService = new PollsService();
export default pollsService;