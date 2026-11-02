import type { Event, EventTicket, EventsState, CreateEventRequest, PurchaseTicketsRequest } from '../contracts/eventsTypes';

const API_BASE = '/api/events';

export class EventsService {
  async getEvents(): Promise<Event[]> {
    const response = await fetch(API_BASE);
    if (!response.ok) throw new Error('Failed to fetch events');
    return response.json();
  }

  async getEvent(id: string): Promise<Event> {
    const response = await fetch(`${API_BASE}/${id}`);
    if (!response.ok) throw new Error('Failed to fetch event');
    return response.json();
  }

  async createEvent(request: CreateEventRequest): Promise<Event> {
    const response = await fetch(API_BASE, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create event');
    return response.json();
  }

  async updateEvent(id: string, request: Partial<CreateEventRequest>): Promise<Event> {
    const response = await fetch(`${API_BASE}/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to update event');
    return response.json();
  }

  async deleteEvent(id: string): Promise<void> {
    const response = await fetch(`${API_BASE}/${id}`, { method: 'DELETE' });
    if (!response.ok) throw new Error('Failed to delete event');
  }

  async getTickets(): Promise<EventTicket[]> {
    const response = await fetch(`${API_BASE}/tickets`);
    if (!response.ok) throw new Error('Failed to fetch tickets');
    return response.json();
  }

  async purchaseTickets(request: PurchaseTicketsRequest): Promise<EventTicket> {
    const response = await fetch(`${API_BASE}/tickets`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to purchase tickets');
    return response.json();
  }

  async cancelTicket(ticketId: string): Promise<EventTicket> {
    const response = await fetch(`${API_BASE}/tickets/${ticketId}/cancel`, { method: 'POST' });
    if (!response.ok) throw new Error('Failed to cancel ticket');
    return response.json();
  }
}

export const eventsService = new EventsService();
export default eventsService;
