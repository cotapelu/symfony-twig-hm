import { describe, it, expect, vi, beforeEach } from 'vitest';
import { get } from 'svelte/store';
import {
  queueStore,
  openQueues,
  myWaitingTickets,
  myCompletedTickets,
  totalWaitingCount,
  hasPendingTicket
} from './queueStore';

describe('queueStore', () => {
  beforeEach(() => {
    queueStore.reset();
  });

  describe('initial state', () => {
    it('should have correct initial state', () => {
      const state = get(queueStore);
      expect(state.queues).toEqual([]);
      expect(state.currentQueue).toBeNull();
      expect(state.myTickets).toEqual([]);
      expect(state.waitingTickets).toEqual([]);
      expect(state.calledTickets).toEqual([]);
      expect(state.statistics).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('setters', () => {
    it('should set loading', () => {
      queueStore.setLoading(true);
      expect(get(queueStore).loading).toBe(true);
    });

    it('should set error', () => {
      queueStore.setError('Test error');
      expect(get(queueStore).error).toBe('Test error');
    });

    it('should set queues', () => {
      const queues = [{ id: '1', name: 'Queue 1', isOpen: true, waitingTicketCount: 5 }];
      queueStore.setQueues(queues);
      expect(get(queueStore).queues).toEqual(queues);
    });

    it('should set current queue', () => {
      const queue = { id: '1', name: 'Queue 1', isOpen: true, waitingTicketCount: 5 };
      queueStore.setCurrentQueue(queue);
      expect(get(queueStore).currentQueue).toEqual(queue);
    });

    it('should set statistics', () => {
      const stats = { totalCalled: 10, averageWaitTime: 300 };
      queueStore.setStatistics(stats);
      expect(get(queueStore).statistics).toEqual(stats);
    });
  });

  describe('addTicket', () => {
    it('should add ticket to myTickets and waitingTickets', () => {
      const ticket = { id: '1', queueId: 'q1', status: 'waiting', ticketNumber: 'A001' };
      
      queueStore.addTicket(ticket);
      
      const state = get(queueStore);
      expect(state.myTickets).toContainEqual(ticket);
      expect(state.waitingTickets).toContainEqual(ticket);
    });
  });

  describe('updateTicket', () => {
    it('should update ticket in myTickets', () => {
      const ticket = { id: '1', queueId: 'q1', status: 'waiting', ticketNumber: 'A001' };
      queueStore.addTicket(ticket);
      
      queueStore.updateTicket('1', { status: 'called' });
      
      const state = get(queueStore);
      expect(state.myTickets[0].status).toBe('called');
    });

    it('should update ticket in waitingTickets', () => {
      const ticket = { id: '1', queueId: 'q1', status: 'waiting', ticketNumber: 'A001' };
      queueStore.addTicket(ticket);
      
      queueStore.updateTicket('1', { status: 'completed' });
      
      const state = get(queueStore);
      expect(state.waitingTickets[0].status).toBe('completed');
    });
  });

  describe('removeTicket', () => {
    it('should remove ticket from all lists', () => {
      const ticket = { id: '1', queueId: 'q1', status: 'waiting', ticketNumber: 'A001' };
      queueStore.addTicket(ticket);
      
      queueStore.removeTicket('1');
      
      const state = get(queueStore);
      expect(state.myTickets).toEqual([]);
      expect(state.waitingTickets).toEqual([]);
    });
  });

  describe('reset', () => {
    it('should reset to initial state', () => {
      queueStore.setQueues([{ id: '1', name: 'Q1', isOpen: true, waitingTicketCount: 5 }]);
      queueStore.setLoading(true);
      queueStore.setError('error');
      
      queueStore.reset();
      
      const state = get(queueStore);
      expect(state.queues).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('derived stores', () => {
    it('should derive openQueues', () => {
      const queues = [
        { id: '1', name: 'Open', isOpen: true, waitingTicketCount: 5 },
        { id: '2', name: 'Closed', isOpen: false, waitingTicketCount: 0 }
      ];
      queueStore.setQueues(queues);
      
      expect(get(openQueues).length).toBe(1);
      expect(get(openQueues)[0].name).toBe('Open');
    });

    it('should derive myWaitingTickets', () => {
      queueStore.addTicket({ id: '1', queueId: 'q1', status: 'waiting', ticketNumber: 'A001' });
      queueStore.addTicket({ id: '2', queueId: 'q1', status: 'completed', ticketNumber: 'A002' });
      
      expect(get(myWaitingTickets).length).toBe(1);
      expect(get(myWaitingTickets)[0].status).toBe('waiting');
    });

    it('should derive myCompletedTickets', () => {
      queueStore.addTicket({ id: '1', queueId: 'q1', status: 'waiting', ticketNumber: 'A001' });
      queueStore.addTicket({ id: '2', queueId: 'q1', status: 'completed', ticketNumber: 'A002' });
      
      expect(get(myCompletedTickets).length).toBe(1);
      expect(get(myCompletedTickets)[0].status).toBe('completed');
    });

    it('should derive totalWaitingCount', () => {
      const queues = [
        { id: '1', name: 'Q1', isOpen: true, waitingTicketCount: 5 },
        { id: '2', name: 'Q2', isOpen: true, waitingTicketCount: 3 }
      ];
      queueStore.setQueues(queues);
      
      expect(get(totalWaitingCount)).toBe(8);
    });

    it('should derive hasPendingTicket', () => {
      expect(get(hasPendingTicket)).toBe(false);
      
      queueStore.addTicket({ id: '1', queueId: 'q1', status: 'waiting', ticketNumber: 'A001' });
      expect(get(hasPendingTicket)).toBe(true);
    });
  });
});
