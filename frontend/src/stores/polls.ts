// Polls Store - TypeScript Version
// PHP Poll Module with type safety

import { writable, type Subscriber, type Unsubscriber } from "svelte/store";
import { PHP } from "../lib/api";
import type { Poll } from "../lib/types";

interface PollsState {
  polls: Record<string, Poll[]>;
  loading: boolean;
  error: string | null;
}

type PollsStore = {
  subscribe: (
    run: Subscriber<PollsState>,
    invalidate?: () => void,
  ) => Unsubscriber;
  createPoll: (
    conversationId: string,
    question: string,
    options: string[],
  ) => Promise<Poll>;
  votePoll: (pollId: string, optionId: string) => Promise<Poll>;
  vote: (pollId: string, optionId: string) => Promise<Poll>;
  addPoll: (conversationId: string, poll: Poll) => void;
  loadPolls: () => Promise<void>;
  deletePoll: (pollId: string) => Promise<void>;
  reset: () => void;
} & Pick<PollsState, "loading" | "error">;

function createPollsStore(): PollsStore {
  const initialState: PollsState = {
    polls: {},
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<PollsState>(initialState);

  let loading = false;
  let error: string | null = null;

  function setLoading(val: boolean) {
    loading = val;
  }

  function setError(err: string | null) {
    error = err;
  }

  return {
    subscribe,
    get loading() {
      return loading;
    },
    get error() {
      return error;
    },

    createPoll: async (
      conversationId: string,
      question: string,
      options: string[],
    ): Promise<Poll> => {
      setLoading(true);
      setError(null);
      try {
        const poll = await PHP.createPoll({
          conversationId,
          question,
          options,
        });
        update((prev) => ({
          ...prev,
          polls: {
            ...prev.polls,
            [conversationId]: [...(prev.polls[conversationId] || []), poll],
          },
        }));
        setLoading(false);
        return poll;
      } catch (err) {
        const errorMessage =
          err instanceof Error ? err.message : "Failed to create poll";
        setLoading(false);
        setError(errorMessage);
        throw err;
      }
    },

    votePoll: async (pollId: string, optionId: string): Promise<Poll> => {
      try {
        const result = await PHP.votePoll(pollId, [optionId]);
        update((prev) => {
          const updatedPolls: Record<string, Poll[]> = {};
          Object.keys(prev.polls).forEach((convId) => {
            updatedPolls[convId] = prev.polls[convId].map((p) =>
              p.id === pollId ? Object.assign({}, p, result) : p,
            );
          });
          return { ...prev, polls: updatedPolls };
        });
        return result as Poll;
      } catch (err) {
        const errorMessage =
          err instanceof Error ? err.message : "Failed to vote poll";
        setError(errorMessage);
        throw err;
      }
    },

    addPoll: (conversationId: string, poll: Poll): void => {
      update((prev) => ({
        ...prev,
        polls: {
          ...prev.polls,
          [conversationId]: [...(prev.polls[conversationId] || []), poll],
        },
      }));
    },

    loadPolls: async (): Promise<void> => {
      setLoading(true);
      setError(null);
      try {
        const polls = await PHP.getPolls();
        const pollsByConversation: Record<string, Poll[]> = {};
        polls.forEach((poll) => {
          const convId = "global";
          if (!pollsByConversation[convId]) {
            pollsByConversation[convId] = [];
          }
          pollsByConversation[convId].push(poll);
        });
        update((prev) => ({
          ...prev,
          polls: pollsByConversation,
        }));
        setLoading(false);
      } catch (err) {
        const errorMessage =
          err instanceof Error ? err.message : "Failed to load polls";
        setLoading(false);
        setError(errorMessage);
        throw err;
      }
    },

    deletePoll: async (pollId: string): Promise<void> => {
      setLoading(true);
      setError(null);
      try {
        await PHP.deletePoll(pollId);
        update((prev) => {
          const updatedPolls: Record<string, Poll[]> = {};
          Object.keys(prev.polls).forEach((convId) => {
            updatedPolls[convId] = prev.polls[convId].filter(
              (p) => p.id !== pollId,
            );
          });
          return { ...prev, polls: updatedPolls };
        });
        setLoading(false);
      } catch (err) {
        const errorMessage =
          err instanceof Error ? err.message : "Failed to delete poll";
        setLoading(false);
        setError(errorMessage);
        throw err;
      }
    },

    vote: async (pollId: string, optionId: string): Promise<Poll> => {
      return votePoll(pollId, optionId);
    },

    // Test-only reset
    reset: (): void => {
      set({ polls: {}, loading: false, error: null });
    },
  };
}

export const pollsStore = createPollsStore();

export const { createPoll, votePoll, addPoll, loadPolls, deletePoll, vote } =
  pollsStore;
