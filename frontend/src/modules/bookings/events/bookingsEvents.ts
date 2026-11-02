export interface BookingCreatedEvent {
  type: 'booking.created';
  payload: { bookingId: string; userId: string; serviceId: string };
}

export interface BookingConfirmedEvent {
  type: 'booking.confirmed';
  payload: { bookingId: string };
}

export interface BookingCancelledEvent {
  type: 'booking.cancelled';
  payload: { bookingId: string; reason?: string };
}

export interface BookingCompletedEvent {
  type: 'booking.completed';
  payload: { bookingId: string };
}

export type BookingsEvent = BookingCreatedEvent | BookingConfirmedEvent | BookingCancelledEvent | BookingCompletedEvent;

export const bookingsEvents = {
  bookingCreated: (bookingId: string, userId: string, serviceId: string): BookingCreatedEvent => ({
    type: 'booking.created',
    payload: { bookingId, userId, serviceId }
  }),
  bookingConfirmed: (bookingId: string): BookingConfirmedEvent => ({
    type: 'booking.confirmed',
    payload: { bookingId }
  }),
  bookingCancelled: (bookingId: string, reason?: string): BookingCancelledEvent => ({
    type: 'booking.cancelled',
    payload: { bookingId, reason }
  }),
  bookingCompleted: (bookingId: string): BookingCompletedEvent => ({
    type: 'booking.completed',
    payload: { bookingId }
  })
};
