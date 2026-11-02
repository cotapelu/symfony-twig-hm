export interface EventCreatedEvent {
  type: 'events.event.created';
  payload: { eventId: string; organizerId: string; title: string };
}

export interface EventPublishedEvent {
  type: 'events.event.published';
  payload: { eventId: string; title: string };
}

export interface TicketPurchasedEvent {
  type: 'events.ticket.purchased';
  payload: { ticketId: string; eventId: string; userId: string; quantity: number };
}

export interface TicketCancelledEvent {
  type: 'events.ticket.cancelled';
  payload: { ticketId: string; eventId: string };
}

export type EventsEvent = EventCreatedEvent | EventPublishedEvent | TicketPurchasedEvent | TicketCancelledEvent;

export const eventsModuleEvents = {
  eventCreated: (eventId: string, organizerId: string, title: string): EventCreatedEvent => ({
    type: 'events.event.created',
    payload: { eventId, organizerId, title }
  }),
  eventPublished: (eventId: string, title: string): EventPublishedEvent => ({
    type: 'events.event.published',
    payload: { eventId, title }
  }),
  ticketPurchased: (ticketId: string, eventId: string, userId: string, quantity: number): TicketPurchasedEvent => ({
    type: 'events.ticket.purchased',
    payload: { ticketId, eventId, userId, quantity }
  }),
  ticketCancelled: (ticketId: string, eventId: string): TicketCancelledEvent => ({
    type: 'events.ticket.cancelled',
    payload: { ticketId, eventId }
  })
};
