// Events Service - Connects to Go events module
import { getApiUrl } from '../config/api';

export interface Event {
  id: string;
  title: string;
  description: string;
  image: string;
  // routes read a plural images array — added additively
  images?: string[];
  location: string;
  startDate: string;
  endDate: string;
  organizer: string;
  category: string;
  isAttending: boolean;
  attendeesCount: number;
}

export interface EventAttendance {
  eventId: string;
  status: 'attending' | 'interested' | 'not_attending';
}

class EventsService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getEvents(): Promise<Event[]> {
    const url = getApiUrl('events', '/api/events');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch events');
    const data = await response.json();
    return data.events || [];
  }

  async getEvent(id: string): Promise<Event> {
    const url = getApiUrl('events', `/api/events/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch event');
    return response.json();
  }

  async attendEvent(eventId: string): Promise<EventAttendance> {
    const url = getApiUrl('events', `/api/events/${eventId}/attend`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to attend event');
    return response.json();
  }

  async getMyEvents(): Promise<Event[]> {
    const url = getApiUrl('events', '/api/events/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch my events');
    const data = await response.json();
    return data.events || [];
  }
}

export const eventsService = new EventsService();
