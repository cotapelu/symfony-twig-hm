import { queueStore } from '../state/queueStore';
import type { Queue, QueueTicket, QueueStatistics, CreateQueueRequest, JoinQueueRequest, CallNextTicketRequest } from '../contracts/queueTypes';

const API_BASE = '/api/v1/queues';

export class QueueService {
  private async request<T>(endpoint: string, options?: RequestInit): Promise<T> {
    const url = `${API_BASE}${endpoint}`;
    const config = {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options?.headers
      }
    };

    try {
      queueStore.setLoading(true);
      queueStore.setError(null);

      const response = await fetch(url, config);
      const data = await response.json();

      if (!data.success) {
        throw new Error(data.error || 'Request failed');
      }

      return data.data as T;
    } catch (error) {
      queueStore.setError(error instanceof Error ? error.message : 'Unknown error');
      throw error;
    } finally {
      queueStore.setLoading(false);
    }
  }

  async loadActiveQueues(): Promise<Queue[]> {
    const queues = await this.request<Queue[]>('/active');
    queueStore.setQueues(queues);
    return queues;
  }

  async loadOpenQueues(): Promise<Queue[]> {
    const queues = await this.request<Queue[]>('/open');
    queueStore.setQueues(queues);
    return queues;
  }

  async loadQueuesByWard(wardCode: string): Promise<Queue[]> {
    const queues = await this.request<Queue[]>(`/ward/${wardCode}`);
    queueStore.setQueues(queues);
    return queues;
  }

  async loadQueue(queueId: string): Promise<Queue> {
    const queue = await this.request<Queue>(`/${queueId}`);
    queueStore.setCurrentQueue(queue);
    return queue;
  }

  async createQueue(request: CreateQueueRequest): Promise<Queue> {
    return this.request<Queue>('', {
      method: 'POST',
      body: JSON.stringify(request)
    });
  }

  async updateQueue(queueId: string, updates: Partial<Queue>): Promise<Queue> {
    return this.request<Queue>(`/${queueId}`, {
      method: 'PUT',
      body: JSON.stringify(updates)
    });
  }

  async activateQueue(queueId: string): Promise<Queue> {
    return this.request<Queue>(`/${queueId}/activate`, {
      method: 'POST'
    });
  }

  async deactivateQueue(queueId: string): Promise<Queue> {
    return this.request<Queue>(`/${queueId}/deactivate`, {
      method: 'POST'
    });
  }

  async joinQueue(request: JoinQueueRequest): Promise<QueueTicket> {
    const ticket = await this.request<QueueTicket>(`/${request.queueId}/join`, {
      method: 'POST',
      body: JSON.stringify(request)
    });
    queueStore.addTicket(ticket);
    return ticket;
  }

  async callNextTicket(request: CallNextTicketRequest): Promise<QueueTicket | null> {
    const ticket = await this.request<QueueTicket | null>(`/${request.queueId}/next`, {
      method: 'POST',
      body: JSON.stringify(request)
    });

    if (ticket) {
      queueStore.updateTicket(ticket.id, ticket);
      await this.loadWaitingTickets(request.queueId);
      await this.loadCalledTickets(request.queueId);
    }

    return ticket;
  }

  async startTicket(ticketId: string): Promise<QueueTicket> {
    const ticket = await this.request<QueueTicket>(`/tickets/${ticketId}/start`, {
      method: 'POST'
    });
    queueStore.updateTicket(ticketId, ticket);
    return ticket;
  }

  async completeTicket(ticketId: string, notes?: string): Promise<QueueTicket> {
    const ticket = await this.request<QueueTicket>(`/tickets/${ticketId}/complete`, {
      method: 'POST',
      body: JSON.stringify({ notes })
    });
    queueStore.updateTicket(ticketId, ticket);
    return ticket;
  }

  async cancelTicket(ticketId: string, reason?: string): Promise<QueueTicket> {
    const ticket = await this.request<QueueTicket>(`/tickets/${ticketId}/cancel`, {
      method: 'POST',
      body: JSON.stringify({ reason })
    });
    queueStore.updateTicket(ticketId, ticket);
    return ticket;
  }

  async skipTicket(ticketId: string): Promise<QueueTicket> {
    const ticket = await this.request<QueueTicket>(`/tickets/${ticketId}/skip`, {
      method: 'POST'
    });
    queueStore.updateTicket(ticketId, ticket);
    return ticket;
  }

  async loadTicket(ticketId: string): Promise<QueueTicket> {
    return this.request<QueueTicket>(`/tickets/${ticketId}`);
  }

  async loadTicketsByQueue(queueId: string, status?: string, limit = 50, offset = 0): Promise<QueueTicket[]> {
    let endpoint = `/${queueId}/tickets?limit=${limit}&offset=${offset}`;
    if (status) {
      endpoint += `&status=${status}`;
    }
    return this.request<QueueTicket[]>(endpoint);
  }

  async loadWaitingTickets(queueId: string): Promise<QueueTicket[]> {
    const tickets = await this.request<QueueTicket[]>(`/${queueId}/waiting`);
    queueStore.setWaitingTickets(tickets);
    return tickets;
  }

  async loadCalledTickets(queueId: string): Promise<QueueTicket[]> {
    const tickets = await this.request<QueueTicket[]>(`/${queueId}/called`);
    queueStore.setCalledTickets(tickets);
    return tickets;
  }

  async loadQueueStatistics(queueId: string): Promise<QueueStatistics> {
    const stats = await this.request<QueueStatistics>(`/${queueId}/statistics`);
    queueStore.setStatistics(stats);
    return stats;
  }

  async loadTicketsByPhone(phone: string): Promise<QueueTicket[]> {
    const tickets = await this.request<QueueTicket[]>(`/tickets/by-phone/${phone}`);
    queueStore.setMyTickets(tickets);
    return tickets;
  }
}

export const queueService = new QueueService();
