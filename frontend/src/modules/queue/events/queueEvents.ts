export interface TicketCalledEvent {
  type: 'queue.ticket.called';
  payload: {
    ticketId: string;
    queueId: string;
    counterNumber: number;
  };
}

export interface TicketServedEvent {
  type: 'queue.ticket.served';
  payload: {
    ticketId: string;
    queueId: string;
    waitTime: number;
  };
}

export interface TicketCancelledEvent {
  type: 'queue.ticket.cancelled';
  payload: {
    ticketId: string;
    queueId: string;
    reason?: string;
  };
}

export interface TicketJoinedEvent {
  type: 'queue.ticket.joined';
  payload: {
    ticketId: string;
    queueId: string;
    position: number;
  };
}

export interface QueueUpdatedEvent {
  type: 'queue.updated';
  payload: {
    queueId: string;
    waitingCount: number;
  };
}

export type QueueEvent =
  | TicketCalledEvent
  | TicketServedEvent
  | TicketCancelledEvent
  | TicketJoinedEvent
  | QueueUpdatedEvent;

export const queueEvents = {
  ticketCalled: (ticketId: string, queueId: string, counterNumber: number): TicketCalledEvent => ({
    type: 'queue.ticket.called',
    payload: { ticketId, queueId, counterNumber }
  }),

  ticketServed: (ticketId: string, queueId: string, waitTime: number): TicketServedEvent => ({
    type: 'queue.ticket.served',
    payload: { ticketId, queueId, waitTime }
  }),

  ticketCancelled: (ticketId: string, queueId: string, reason?: string): TicketCancelledEvent => ({
    type: 'queue.ticket.cancelled',
    payload: { ticketId, queueId, reason }
  }),

  ticketJoined: (ticketId: string, queueId: string, position: number): TicketJoinedEvent => ({
    type: 'queue.ticket.joined',
    payload: { ticketId, queueId, position }
  }),

  queueUpdated: (queueId: string, waitingCount: number): QueueUpdatedEvent => ({
    type: 'queue.updated',
    payload: { queueId, waitingCount }
  })
};
