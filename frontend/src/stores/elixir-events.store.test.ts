import { describe, it, expect, vi, beforeEach } from 'vitest';
import { elixirEventsStore } from './elixir-stores';
import { Elixir } from '../lib/api';

vi.mock('../lib/api', () => ({
  Elixir: {
    createEvent: vi.fn(),
    getEvents: vi.fn(),
    getEvent: vi.fn(),
    rsvpEvent: vi.fn(),
    getCalendarIntegration: vi.fn(),
  }
}));

describe('ElixirEventsStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    elixirEventsStore.reset();
    elixirEventsStore.subscribe(s => { state = s; });
  });

  describe('createEvent', () => {
    it('should create event and prepend to events', async () => {
      const event = { id: 'e1', name: 'Event1' };
      (Elixir.createEvent as any).mockResolvedValue(event);

      const result = await elixirEventsStore.createEvent({ name: 'Event1' });

      expect(result).toEqual(event);
      expect(state.events).toContain(event);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Elixir.createEvent as any).mockRejectedValue(new Error('Create failed'));
      await expect(elixirEventsStore.createEvent({})).rejects.toThrow('Create failed');
      expect(state.error).toBe('Create failed');
    });
  });

  describe('getEvents', () => {
    it('should fetch events', async () => {
      const events = [{ id: 'e1' }, { id: 'e2' }];
      (Elixir.getEvents as any).mockResolvedValue(events);

      const result = await elixirEventsStore.getEvents('user1');

      expect(result).toEqual(events);
      expect(state.events).toEqual(events);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Elixir.getEvents as any).mockRejectedValue(new Error('Get failed'));
      await expect(elixirEventsStore.getEvents('u1')).rejects.toThrow('Get failed');
      expect(state.error).toBe('Get failed');
    });
  });

  describe('getEvent', () => {
    it('should set currentEvent', async () => {
      const event = { id: 'e1', name: 'Event' };
      (Elixir.getEvent as any).mockResolvedValue(event);

      const result = await elixirEventsStore.getEvent('e1');

      expect(result).toEqual(event);
      expect(state.currentEvent).toEqual(event);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Elixir.getEvent as any).mockRejectedValue(new Error('GetEvent failed'));
      await expect(elixirEventsStore.getEvent('e1')).rejects.toThrow('GetEvent failed');
      expect(state.error).toBe('GetEvent failed');
    });
  });

  describe('rsvpEvent', () => {
    it('should update rsvpStatus in events array', async () => {
      state.events = [{ id: 'e1', rsvpStatus: null }, { id: 'e2', rsvpStatus: null }];
      (Elixir.rsvpEvent as any).mockResolvedValue({ success: true });

      await elixirEventsStore.rsvpEvent('e1', 'going');

      expect(state.events[0].rsvpStatus).toBe('going');
      expect(state.events[1].rsvpStatus).toBeNull();
    });

    it('should handle error', async () => {
      state.events = [{ id: 'e1' }];
      (Elixir.rsvpEvent as any).mockRejectedValue(new Error('RSVP failed'));
      await expect(elixirEventsStore.rsvpEvent('e1', 'maybe')).rejects.toThrow('RSVP failed');
      expect(state.error).toBe('RSVP failed');
    });
  });

  describe('getCalendarIntegration', () => {
    it('should store calendar data by provider', async () => {
      const calendar = { provider: 'google', token: 'tok' };
      (Elixir.getCalendarIntegration as any).mockResolvedValue(calendar);
      state.calendarEvents = {};

      const result = await elixirEventsStore.getCalendarIntegration('google');

      expect(result).toEqual(calendar);
      expect(state.calendarEvents['google']).toEqual(calendar);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Elixir.getCalendarIntegration as any).mockRejectedValue(new Error('Cal failed'));
      await expect(elixirEventsStore.getCalendarIntegration('google')).rejects.toThrow('Cal failed');
      expect(state.error).toBe('Cal failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.events = [{ id: 'e1' }];
      state.currentEvent = { id: 'e2' };
      state.calendarEvents = { google: {} };
      state.error = 'err';
      elixirEventsStore.reset();
      expect(state.events).toEqual([]);
      expect(state.currentEvent).toBeNull();
      expect(state.calendarEvents).toEqual({});
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Elixir.createEvent as any).mockRejectedValue(new Error('test'));
      try { await elixirEventsStore.createEvent({}); } catch (e) {}
      expect(state.error).toBe('test');
      elixirEventsStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
