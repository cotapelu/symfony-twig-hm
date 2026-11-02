import { describe, it, expect, vi, beforeEach } from 'vitest';
import { complaintsStore, complaints, complaintsLoading } from './complaintsStore';
import { ComplaintsService } from '../services/complaintsService';
import type { Complaint } from '../contracts/complaintsTypes';

function getCurrentState() {
  let current: any;
  const unsub = complaintsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockComplaint = (overrides: Partial<Complaint> = {}): Complaint => ({
  id: `comp-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Complaint Title',
  description: 'Description',
  category: 'service',
  status: 'pending',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

describe('ComplaintsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    complaintsStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.complaints).toEqual([]);
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let c: Complaint[] = [], l = false;
    const unsubC = complaints.subscribe(s => { c = s; });
    const unsubL = complaintsLoading.subscribe(s => { l = s; });
    expect(c).toEqual([]);
    expect(l).toBe(false);
    unsubC(); unsubL();
  });

  describe('loadComplaints', () => {
    it('loads complaints', async () => {
      const comps = [mockComplaint(), mockComplaint()];
      vi.spyOn(ComplaintsService.prototype, 'getComplaints').mockResolvedValue(comps);
      await complaintsStore.loadComplaints();
      const state = getCurrentState();
      expect(state.complaints).toEqual(comps);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(ComplaintsService.prototype, 'getComplaints').mockRejectedValue(new Error('Failed to load'));
      await complaintsStore.loadComplaints();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load');
      expect(state.complaints).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(ComplaintsService.prototype, 'getComplaints').mockRejectedValue(new Error('err'));
      await complaintsStore.loadComplaints();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      complaintsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(ComplaintsService.prototype, 'getComplaints').mockResolvedValue([mockComplaint()]);
      await complaintsStore.loadComplaints();
      let state = getCurrentState();
      expect(state.complaints.length).toBeGreaterThan(0);
      complaintsStore.reset();
      state = getCurrentState();
      expect(state.complaints).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
