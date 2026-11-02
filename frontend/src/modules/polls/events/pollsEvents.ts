import type { Poll } from '../contracts/pollsTypes';

export const PollsEvents = {
  POLLS_LOADED: 'polls:loaded',
  POLL_CREATED: 'poll:created',
  VOTE_SUBMITTED: 'vote:submitted',
  POLL_CLOSED: 'poll:closed',
  POLL_ERROR: 'poll:error'
};

export interface PollsEventDetail {
  polls?: Poll[];
  poll?: Poll;
  pollId?: string;
  error?: string;
}

export function dispatchPollsEvent(event: string, detail?: PollsEventDetail) {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent(event, { detail }));
  }
}

export function onPollsLoaded(callback: (polls: Poll[]) => void) {
  window.addEventListener(PollsEvents.POLLS_LOADED, (e: Event) => {
    const customEvent = e as CustomEvent;
    callback(customEvent.detail.polls);
  });
}

export function onVoteSubmitted(callback: (pollId: string) => void) {
  window.addEventListener(PollsEvents.VOTE_SUBMITTED, (e: Event) => {
    const customEvent = e as CustomEvent;
    callback(customEvent.detail.pollId);
  });
}