import type { Service, Booking } from '../contracts/servicesTypes';

export const ServicesEvents = {
  SERVICES_LOADED: 'services:loaded',
  BOOKING_CREATED: 'booking:created',
  BOOKING_CANCELLED: 'booking:cancelled',
  SERVICE_RATED: 'service:rated',
  SERVICE_ERROR: 'service:error'
};

export interface ServicesEventDetail {
  services?: Service[];
  booking?: Booking;
  bookingId?: string;
  error?: string;
}

export function dispatchServicesEvent(event: string, detail?: ServicesEventDetail) {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent(event, { detail }));
  }
}

export function onServicesLoaded(callback: (services: Service[]) => void) {
  window.addEventListener(ServicesEvents.SERVICES_LOADED, (e: Event) => {
    const customEvent = e as CustomEvent;
    callback(customEvent.detail.services);
  });
}

export function onBookingCreated(callback: (booking: Booking) => void) {
  window.addEventListener(ServicesEvents.BOOKING_CREATED, (e: Event) => {
    const customEvent = e as CustomEvent;
    callback(customEvent.detail.booking);
  });
}