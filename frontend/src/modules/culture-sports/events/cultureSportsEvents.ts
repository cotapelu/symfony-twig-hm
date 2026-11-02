export type CultureSportsEventType = CultureSportsEvent;

export interface EventCreatedEvent { type: 'culture-sports.event.created'; payload: { eventId: string }; }
export type CultureSportsEvent = EventCreatedEvent;

export const cultureSportsEvents = {
  eventCreated: (eventId: string): EventCreatedEvent => ({ type: 'culture-sports.event.created', payload: { eventId } })
};
