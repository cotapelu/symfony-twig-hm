import { writable, get } from 'svelte/store';
import type { Service, Booking, ServicesState, CreateBookingRequest, RateServiceRequest } from '../contracts/servicesTypes';
import { servicesService } from '../services/servicesService';

export const initialState: ServicesState = {
  services: [],
  bookings: [],
  loading: false,
  error: null
};

export const servicesStore = writable<ServicesState>(initialState);

export async function loadServices() {
  servicesStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    const data = await servicesService.getServices();
    servicesStore.update(s => ({ ...s, services: data, loading: false }));
  } catch (e) {
    servicesStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
  }
}

export async function bookService(request: CreateBookingRequest) {
  const state = get(servicesStore);
  
  const alreadyBooked = state.bookings.some(
    b => b.service.id === request.serviceId && b.status !== 'cancelled'
  );
  
  if (alreadyBooked) {
    throw new Error('Service already booked');
  }
  
  servicesStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    const booking = await servicesService.createBooking(request);
    servicesStore.update(s => ({ 
      ...s, 
      bookings: [...s.bookings, booking], 
      loading: false 
    }));
    return booking;
  } catch (e) {
    servicesStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
    throw e;
  }
}

export async function cancelBooking(bookingId: string) {
  servicesStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    await servicesService.cancelBooking(bookingId);
    servicesStore.update(s => ({
      ...s,
      bookings: s.bookings.map(b => 
        b.id === bookingId ? { ...b, status: 'cancelled' as const } : b
      ),
      loading: false
    }));
  } catch (e) {
    servicesStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
    throw e;
  }
}

export async function rateService(request: RateServiceRequest) {
  servicesStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    const updated = await servicesService.rateService(request);
    servicesStore.update(s => ({
      ...s,
      bookings: s.bookings.map(b => b.id === request.bookingId ? updated : b),
      loading: false
    }));
  } catch (e) {
    servicesStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
    throw e;
  }
}

export function clearError() {
  servicesStore.update(s => ({ ...s, error: null }));
}

export function getBookings() {
  return get(servicesStore).bookings;
}

export function hasBooked(serviceId: string): boolean {
  const state = get(servicesStore);
  return state.bookings.some(b => b.service.id === serviceId && b.status !== 'cancelled');
}

// Reset store to initial state for testing
import { initialState as initial } from './servicesStore';
// Correct: we need to access initial state. But initial state is defined in same module as const initialState. We can just use it directly.
// We'll define a reset function:
export function reset() {
  servicesStore.set(initialState);
}