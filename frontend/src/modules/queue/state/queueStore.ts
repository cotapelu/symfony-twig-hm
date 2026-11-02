import { writable, derived, get } from 'svelte/store';
import type { Queue, QueueTicket, QueueStatistics } from '../contracts/queueTypes';

interface QueueState {
  queues: Queue[];
  currentQueue: Queue | null;
  myTickets: QueueTicket[];
  waitingTickets: QueueTicket[];
  calledTickets: QueueTicket[];
  statistics: QueueStatistics | null;
  loading: boolean;
  error: string | null;
}

function createQueueStore() {
  const initialState: QueueState = {
    queues: [],
    currentQueue: null,
    myTickets: [],
    waitingTickets: [],
    calledTickets: [],
    statistics: null,
    loading: false,
    error: null
  };

  const { subscribe, update, set } = writable<QueueState>(initialState);

  return {
    subscribe,
    
    setLoading: (loading: boolean) => update(s => ({ ...s, loading })),
    
    setError: (error: string | null) => update(s => ({ ...s, error })),
    
    setQueues: (queues: Queue[]) => update(s => ({ ...s, queues })),
    
    setCurrentQueue: (queue: Queue | null) => update(s => ({ ...s, currentQueue: queue })),
    
    setMyTickets: (tickets: QueueTicket[]) => update(s => ({ ...s, myTickets: tickets })),
    
    setWaitingTickets: (tickets: QueueTicket[]) => update(s => ({ ...s, waitingTickets: tickets })),
    
    setCalledTickets: (tickets: QueueTicket[]) => update(s => ({ ...s, calledTickets: tickets })),
    
    setStatistics: (stats: QueueStatistics | null) => update(s => ({ ...s, statistics: stats })),
    
    addTicket: (ticket: QueueTicket) => update(s => ({
      ...s,
      myTickets: [ticket, ...s.myTickets],
      waitingTickets: [...s.waitingTickets, ticket]
    })),
    
    updateTicket: (ticketId: string, updates: Partial<QueueTicket>) => update(s => ({
      ...s,
      myTickets: s.myTickets.map(t => t.id === ticketId ? { ...t, ...updates } : t),
      waitingTickets: s.waitingTickets.map(t => t.id === ticketId ? { ...t, ...updates } : t),
      calledTickets: s.calledTickets.map(t => t.id === ticketId ? { ...t, ...updates } : t)
    })),
    
    removeTicket: (ticketId: string) => update(s => ({
      ...s,
      myTickets: s.myTickets.filter(t => t.id !== ticketId),
      waitingTickets: s.waitingTickets.filter(t => t.id !== ticketId),
      calledTickets: s.calledTickets.filter(t => t.id !== ticketId)
    })),
    
    reset: () => set(initialState)
  };
}

export const queueStore = createQueueStore();

export const openQueues = derived(queueStore, $queueStore => 
  $queueStore.queues.filter(q => q.isOpen)
);

export const myWaitingTickets = derived(queueStore, $queueStore =>
  $queueStore.myTickets.filter(t => t.status === 'waiting' || t.status === 'called')
);

export const myCompletedTickets = derived(queueStore, $queueStore =>
  $queueStore.myTickets.filter(t => t.status === 'completed')
);

export const totalWaitingCount = derived(queueStore, $queueStore =>
  $queueStore.queues.reduce((sum, q) => sum + q.waitingTicketCount, 0)
);

export const hasPendingTicket = derived(myWaitingTickets, $tickets => 
  $tickets.length > 0
);
