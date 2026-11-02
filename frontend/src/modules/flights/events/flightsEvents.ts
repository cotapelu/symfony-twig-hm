export interface FlightSearchedEvent {
  type: 'flights.searched';
  payload: { origin: string; destination: string; date: string };
}

export interface FlightBookedEvent {
  type: 'flights.booked';
  payload: { bookingId: string; flightId: string; userId: string };
}

export interface FlightCancelledEvent {
  type: 'flights.cancelled';
  payload: { bookingId: string; flightId: string };
}

export type FlightsEvent = FlightSearchedEvent | FlightBookedEvent | FlightCancelledEvent;

export const flightsEvents = {
  flightSearched: (origin: string, destination: string, date: string): FlightSearchedEvent => ({
    type: 'flights.searched',
    payload: { origin, destination, date }
  }),
  flightBooked: (bookingId: string, flightId: string, userId: string): FlightBookedEvent => ({
    type: 'flights.booked',
    payload: { bookingId, flightId, userId }
  }),
  flightCancelled: (bookingId: string, flightId: string): FlightCancelledEvent => ({
    type: 'flights.cancelled',
    payload: { bookingId, flightId }
  })
};
