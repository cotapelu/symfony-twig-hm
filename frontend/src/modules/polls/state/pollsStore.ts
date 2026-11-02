import { writable, get } from 'svelte/store';
import type { Poll, PollVote, PollsState, CreatePollRequest, VoteRequest } from '../contracts/pollsTypes';
import { pollsService } from '../services/pollsService';

const initialState: PollsState = {
  polls: [],
  userVotes: new Map(),
  loading: false,
  error: null
};

export const pollsStore = writable<PollsState>(initialState);

export const polls = {
  subscribe: (fn: (polls: Poll[]) => void) => {
    return pollsStore.subscribe(state => fn(state.polls));
  }
};

export const loading = {
  subscribe: (fn: (loading: boolean) => void) => {
    return pollsStore.subscribe(state => fn(state.loading));
  }
};

export const error = {
  subscribe: (fn: (error: string | null) => void) => {
    return pollsStore.subscribe(state => fn(state.error));
  }
};

export async function loadPolls() {
  pollsStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    const data = await pollsService.getPolls();
    pollsStore.update(s => ({ ...s, polls: data, loading: false }));
  } catch (e) {
    pollsStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
  }
}

export async function createPoll(request: CreatePollRequest) {
  pollsStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    const poll = await pollsService.createPoll(request);
    pollsStore.update(s => ({ ...s, polls: [...s.polls, poll], loading: false }));
    return poll;
  } catch (e) {
    pollsStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
    throw e;
  }
}

export async function vote(request: VoteRequest) {
  const state = get(pollsStore);
  
  if (state.userVotes.has(request.pollId)) {
    throw new Error('You have already voted on this poll');
  }
  
  pollsStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    await pollsService.vote(request);
    
    pollsStore.update(s => {
      const newVotes = new Map(s.userVotes);
      newVotes.set(request.pollId, request.optionIds);
      
      const updatedPolls = s.polls.map(p => {
        if (p.id !== request.pollId) return p;
        
        const updatedOptions = p.options.map(opt => {
          if (request.optionIds.includes(opt.id)) {
            return { ...opt, votes: opt.votes + 1 };
          }
          return opt;
        });
        
        return { ...p, options: updatedOptions, totalVotes: p.totalVotes + 1 };
      });
      
      return { ...s, polls: updatedPolls, userVotes: newVotes, loading: false };
    });
  } catch (e) {
    pollsStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
    throw e;
  }
}

export async function closePoll(pollId: string) {
  pollsStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    await pollsService.closePoll(pollId);
    pollsStore.update(s => ({
      ...s,
      polls: s.polls.map(p => p.id === pollId ? { ...p, status: 'closed' as const } : p),
      loading: false
    }));
  } catch (e) {
    pollsStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
    throw e;
  }
}

export async function getResults(pollId: string) {
  return pollsService.getResults(pollId);
}

export function clearError() {
  pollsStore.update(s => ({ ...s, error: null }));
}

export function hasVoted(pollId: string): boolean {
  const state = get(pollsStore);
  return state.userVotes.has(pollId);
}

export function getUserVote(pollId: string): string[] | undefined {
  const state = get(pollsStore);
  return state.userVotes.get(pollId);
}

export function reset() {
  pollsStore.set(initialState);
}