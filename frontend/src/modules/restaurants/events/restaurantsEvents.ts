export interface RestaurantSearchedEvent { type: 'restaurants.searched'; payload: { location: string; cuisine?: string }; }
export interface ReservationCreatedEvent { type: 'restaurants.reservation.created'; payload: { reservationId: string; restaurantId: string; userId: string }; }
export interface ReservationCancelledEvent { type: 'restaurants.reservation.cancelled'; payload: { reservationId: string; restaurantId: string }; }
export type RestaurantsEvent = RestaurantSearchedEvent | ReservationCreatedEvent | ReservationCancelledEvent;
export const restaurantsEvents = {
  restaurantSearched: (location: string, cuisine?: string): RestaurantSearchedEvent => ({ type: 'restaurants.searched', payload: { location, cuisine } }),
  reservationCreated: (reservationId: string, restaurantId: string, userId: string): ReservationCreatedEvent => ({ type: 'restaurants.reservation.created', payload: { reservationId, restaurantId, userId } }),
  reservationCancelled: (reservationId: string, restaurantId: string): ReservationCancelledEvent => ({ type: 'restaurants.reservation.cancelled', payload: { reservationId, restaurantId } })
};
