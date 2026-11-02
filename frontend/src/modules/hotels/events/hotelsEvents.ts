export interface HotelSearchedEvent { type: 'hotels.searched'; payload: { location: string; checkIn: string; checkOut: string }; }
export interface HotelBookedEvent { type: 'hotels.booked'; payload: { bookingId: string; hotelId: string; userId: string }; }
export interface HotelCancelledEvent { type: 'hotels.cancelled'; payload: { bookingId: string; hotelId: string }; }
export type HotelsEvent = HotelSearchedEvent | HotelBookedEvent | HotelCancelledEvent;
export const hotelsEvents = {
  hotelSearched: (location: string, checkIn: string, checkOut: string): HotelSearchedEvent => ({ type: 'hotels.searched', payload: { location, checkIn, checkOut } }),
  hotelBooked: (bookingId: string, hotelId: string, userId: string): HotelBookedEvent => ({ type: 'hotels.booked', payload: { bookingId, hotelId, userId } }),
  hotelCancelled: (bookingId: string, hotelId: string): HotelCancelledEvent => ({ type: 'hotels.cancelled', payload: { bookingId, hotelId } })
};
