export interface BookingCreatedEvent {
  type: 'booking.created';
  payload: {
    bookingId: string;
    userId: string;
    type: 'hotel' | 'flight' | 'restaurant' | 'car' | 'experience' | 'event' | 'package';
    referenceId: string;
  };
}

export interface BookingConfirmedEvent {
  type: 'booking.confirmed';
  payload: {
    bookingId: string;
    userId: string;
    confirmationCode?: string;
  };
}

export interface BookingCancelledEvent {
  type: 'booking.cancelled';
  payload: {
    bookingId: string;
    userId: string;
    reason?: string;
  };
}

export interface BookingCompletedEvent {
  type: 'booking.completed';
  payload: {
    bookingId: string;
    userId: string;
  };
}

export interface BookingFailedEvent {
  type: 'booking.failed';
  payload: {
    bookingId: string;
    userId: string;
    reason: string;
  };
}

export type BookingEvent = 
  | BookingCreatedEvent 
  | BookingConfirmedEvent 
  | BookingCancelledEvent 
  | BookingCompletedEvent 
  | BookingFailedEvent;

export const bookingEvents = {
  bookingCreated: (bookingId: string, userId: string, type: BookingCreatedEvent['payload']['type'], referenceId: string): BookingCreatedEvent => ({
    type: 'booking.created',
    payload: { bookingId, userId, type, referenceId }
  }),

  bookingConfirmed: (bookingId: string, userId: string, confirmationCode?: string): BookingConfirmedEvent => ({
    type: 'booking.confirmed',
    payload: { bookingId, userId, confirmationCode }
  }),

  bookingCancelled: (bookingId: string, userId: string, reason?: string): BookingCancelledEvent => ({
    type: 'booking.cancelled',
    payload: { bookingId, userId, reason }
  }),

  bookingCompleted: (bookingId: string, userId: string): BookingCompletedEvent => ({
    type: 'booking.completed',
    payload: { bookingId, userId }
  }),

  bookingFailed: (bookingId: string, userId: string, reason: string): BookingFailedEvent => ({
    type: 'booking.failed',
    payload: { bookingId, userId, reason }
  })
};
