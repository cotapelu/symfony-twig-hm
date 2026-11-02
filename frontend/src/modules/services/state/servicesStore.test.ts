import { describe, it, expect, vi, beforeEach } from 'vitest';
import { servicesStore, loadServices, bookService, cancelBooking, rateService, clearError, reset, getBookings, hasBooked } from './servicesStore';
import { servicesService } from '../services/servicesService';
import type { Service, Booking, CreateBookingRequest, RateServiceRequest } from '../contracts/servicesTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = servicesStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockService = (overrides: Partial<Service> = {}): Service => ({
  id: `svc-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test Service',
  description: 'A test service',
  category: 'general',
  price: 100,
  duration: 60,
  ...overrides
});

const mockBooking = (overrides: Partial<Booking> = {}): Booking => ({
  id: `book-${Math.random().toString(36).substr(2, 5)}`,
  service: mockService(),
  userId: 'user-1',
  status: 'confirmed',
  bookedAt: new Date().toISOString(),
  ...overrides
});

const mockCreateBookingRequest = (overrides: Partial<CreateBookingRequest> = {}): CreateBookingRequest => ({
  serviceId: 'svc-1',
  scheduledAt: new Date().toISOString(),
  location: 'Online',
  notes: '',
  ...overrides
});

const mockRateServiceRequest = (overrides: Partial<RateServiceRequest> = {}): RateServiceRequest => ({
  bookingId: 'book-1',
  rating: 5,
  comment: 'Great!',
  ...overrides
});

describe('ServicesStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    reset();
  });

  describe('loadServices', () => {
    it('loads services successfully', async () => {
      const services = [mockService(), mockService()];
      vi.spyOn(servicesService, 'getServices').mockResolvedValue(services);

      await loadServices();

      expect(servicesService.getServices).toHaveBeenCalled();
      const state = getState();
      expect(state.services).toEqual(services);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(servicesService, 'getServices').mockReturnValue(p as any);

      const loadPromise = loadServices();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockService()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch services');
      vi.spyOn(servicesService, 'getServices').mockRejectedValue(error);

      await loadServices();

      const state = getState();
      expect(state.error).toBe('Failed to fetch services');
      expect(state.loading).toBe(false);
      expect(state.services).toEqual([]);
    });
  });

  describe('bookService', () => {
    it('creates booking and adds to list', async () => {
      const newBooking = mockBooking({ id: 'new-book' });
      vi.spyOn(servicesService, 'createBooking').mockResolvedValue(newBooking);

      const request = mockCreateBookingRequest();
      const result = await bookService(request);

      expect(result).toEqual(newBooking);
      expect(servicesService.createBooking).toHaveBeenCalledWith(request);
      const state = getState();
      expect(state.bookings).toContainEqual(newBooking);
      expect(state.loading).toBe(false);
    });

    it('prevents double booking for same active service', async () => {
      const existing = mockBooking({ service: mockService({ id: 'svc-1' }) });
      servicesStore.update(s => ({ ...s, bookings: [existing] }));

      const request = mockCreateBookingRequest({ serviceId: 'svc-1' });
      await expect(bookService(request)).rejects.toThrow('Service already booked');

      const state = getState();
      // Error is not set because validation throws before any state update
      expect(state.error).toBeNull();
      expect(state.bookings).toHaveLength(1);
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(servicesService, 'createBooking').mockReturnValue(p as any);

      const request = mockCreateBookingRequest();
      const loadPromise = bookService(request);

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockBooking());
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Booking failed');
      vi.spyOn(servicesService, 'createBooking').mockRejectedValue(error);

      const request = mockCreateBookingRequest();
      await expect(bookService(request)).rejects.toThrow('Booking failed');

      const state = getState();
      expect(state.error).toBe('Booking failed');
      expect(state.bookings).toEqual([]);
    });
  });

  describe('cancelBooking', () => {
    it('cancels booking and updates list', async () => {
      const booking = mockBooking({ id: 'book-1', status: 'confirmed' });
      servicesStore.update(s => ({ ...s, bookings: [booking] }));

      vi.spyOn(servicesService, 'cancelBooking').mockResolvedValue(undefined);

      await cancelBooking('book-1');

      expect(servicesService.cancelBooking).toHaveBeenCalledWith('book-1');
      const state = getState();
      expect(state.bookings[0].status).toBe('cancelled');
    });

    it('handles error', async () => {
      const booking = mockBooking({ id: 'book-1' });
      servicesStore.update(s => ({ ...s, bookings: [booking] }));

      const error = new Error('Cancel failed');
      vi.spyOn(servicesService, 'cancelBooking').mockRejectedValue(error);

      await expect(cancelBooking('book-1')).rejects.toThrow('Cancel failed');

      const state = getState();
      expect(state.error).toBe('Cancel failed');
      expect(state.bookings[0].status).toBe('confirmed'); // unchanged
    });
  });

  describe('rateService', () => {
    it('rates service and updates booking', async () => {
      const booking = mockBooking({ id: 'book-1', status: 'completed' });
      const updatedBooking = { ...booking, rating: 5, comment: 'Great!' };
      vi.spyOn(servicesService, 'rateService').mockResolvedValue(updatedBooking);

      servicesStore.update(s => ({ ...s, bookings: [booking] }));

      const request = mockRateServiceRequest({ bookingId: 'book-1', rating: 5, comment: 'Great!' });
      await rateService(request);

      expect(servicesService.rateService).toHaveBeenCalledWith(request);
      const state = getState();
      expect(state.bookings[0]).toEqual(updatedBooking);
    });

    it('handles error', async () => {
      const booking = mockBooking({ id: 'book-1' });
      servicesStore.update(s => ({ ...s, bookings: [booking] }));

      const error = new Error('Rating failed');
      vi.spyOn(servicesService, 'rateService').mockRejectedValue(error);

      const request = mockRateServiceRequest({ bookingId: 'book-1' });
      await expect(rateService(request)).rejects.toThrow('Rating failed');

      const state = getState();
      expect(state.error).toBe('Rating failed');
    });
  });

  describe('clearError', () => {
    it('clears error', () => {
      // Manually set error
      servicesStore.update(s => ({ ...s, error: 'some error' }));
      expect(getState().error).toBe('some error');

      clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Populate state with services
      const svc = mockService();
      vi.spyOn(servicesService, 'getServices').mockResolvedValue([svc]);
      await loadServices();
      // Manually add a booking and error
      servicesStore.update(s => ({ ...s, bookings: [mockBooking()], error: 'some error' }));
      expect(getState().services).toHaveLength(1);
      expect(getState().bookings).toHaveLength(1);
      expect(getState().error).toBe('some error');

      reset();
      const state = getState();
      expect(state.services).toEqual([]);
      expect(state.bookings).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('getBookings', () => {
    it('returns current bookings', () => {
      const bookings = [mockBooking(), mockBooking()];
      servicesStore.set({ services: [], bookings, loading: false, error: null });
      expect(getBookings()).toEqual(bookings);
    });
  });

  describe('hasBooked', () => {
    it('returns true for active booking of service', () => {
      const booking = mockBooking({ service: mockService({ id: 'svc-1' }), status: 'confirmed' });
      servicesStore.set({ services: [], bookings: [booking], loading: false, error: null });
      expect(hasBooked('svc-1')).toBe(true);
    });

    it('returns false if no active booking (cancelled)', () => {
      const booking = mockBooking({ service: mockService({ id: 'svc-1' }), status: 'cancelled' });
      servicesStore.set({ services: [], bookings: [booking], loading: false, error: null });
      expect(hasBooked('svc-1')).toBe(false);
    });

    it('returns false for service not booked', () => {
      servicesStore.set({ services: [], bookings: [], loading: false, error: null });
      expect(hasBooked('svc-1')).toBe(false);
    });
  });
});
