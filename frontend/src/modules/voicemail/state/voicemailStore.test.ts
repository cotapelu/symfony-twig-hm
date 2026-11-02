import { describe, it, expect, vi, beforeEach } from 'vitest';
import { voicemailStore, voicemails, voicemailLoading, voicemailPagination } from './voicemailStore';
import { voicemailService } from '../services/voicemailService';
import type { Voicemail, PaginatedResponse } from '../contracts/voicemailTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = voicemailStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockVoicemail = (overrides: Partial<Voicemail> = {}): Voicemail => ({
  id: `vm-${Math.random().toString(36).substr(2, 5)}`,
  callerId: 'user1',
  callerName: 'Test Caller',
  duration: 120,
  listened: false,
  createdAt: new Date().toISOString(),
  ...overrides
});

const mockPaginatedResponse = <T>(items: T[], page: number, limit: number, total: number): PaginatedResponse<T> => ({
  items,
  page,
  limit,
  total,
  totalPages: Math.ceil(total / limit)
});

describe('VoicemailStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    voicemailStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.voicemails).toEqual([]);
      expect(state.currentVoicemail).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });

    it('derived stores', () => {
      let vms: Voicemail[] = [], loading = false, pagination: any = {};
      const unsubV = voicemails.subscribe(s => { vms = s; });
      const unsubL = voicemailLoading.subscribe(s => { loading = s; });
      const unsubP = voicemailPagination.subscribe(s => { pagination = s; });
      expect(vms).toEqual([]);
      expect(loading).toBe(false);
      expect(pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
      unsubV(); unsubL(); unsubP();
    });
  });

  describe('loadVoicemails', () => {
    it('loads voicemails successfully with default params', async () => {
      const vms = [mockVoicemail(), mockVoicemail()];
      const response = mockPaginatedResponse(vms, 1, 20, 2);
      vi.spyOn(voicemailService, 'getVoicemails').mockResolvedValue(response);

      await voicemailStore.loadVoicemails();

      expect(voicemailService.getVoicemails).toHaveBeenCalledWith(1, 20);
      const state = getState();
      expect(state.voicemails).toEqual(vms);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 2, totalPages: 1 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads with custom page and limit', async () => {
      const vms = [mockVoicemail()];
      const response = mockPaginatedResponse(vms, 2, 10, 1);
      vi.spyOn(voicemailService, 'getVoicemails').mockResolvedValue(response);

      await voicemailStore.loadVoicemails(2, 10);

      expect(voicemailService.getVoicemails).toHaveBeenCalledWith(2, 10);
      const state = getState();
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.limit).toBe(10);
      expect(state.pagination.total).toBe(1);
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(voicemailService, 'getVoicemails').mockReturnValue(p as any);

      const loadPromise = voicemailStore.loadVoicemails();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockPaginatedResponse([mockVoicemail()], 1, 20, 1));
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch voicemails');
      vi.spyOn(voicemailService, 'getVoicemails').mockRejectedValue(error);

      await voicemailStore.loadVoicemails();

      const state = getState();
      expect(state.error).toBe('Failed to fetch voicemails');
      expect(state.loading).toBe(false);
      expect(state.voicemails).toEqual([]);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });
  });

  describe('loadVoicemail', () => {
    it('loads specific voicemail and sets currentVoicemail', async () => {
      const vm = mockVoicemail({ id: 'vm-1' });
      vi.spyOn(voicemailService, 'getVoicemail').mockResolvedValue(vm);

      await voicemailStore.loadVoicemail('vm-1');

      expect(voicemailService.getVoicemail).toHaveBeenCalledWith('vm-1');
      const state = getState();
      expect(state.currentVoicemail).toEqual(vm);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading during fetch', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(voicemailService, 'getVoicemail').mockReturnValue(p as any);

      const loadPromise = voicemailStore.loadVoicemail('vm-1');

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockVoicemail());
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Voicemail not found');
      vi.spyOn(voicemailService, 'getVoicemail').mockRejectedValue(error);

      await voicemailStore.loadVoicemail('vm-1');

      const state = getState();
      expect(state.error).toBe('Voicemail not found');
      expect(state.loading).toBe(false);
      expect(state.currentVoicemail).toBeNull();
    });
  });

  describe('markAsListened', () => {
    it('marks voicemail as listened and updates list', async () => {
      const vm1 = mockVoicemail({ id: 'vm-1', listened: false });
      const vm2 = mockVoicemail({ id: 'vm-2', listened: true });
      // Pre-populate voicemails via loadVoicemails
      vi.spyOn(voicemailService, 'getVoicemails').mockResolvedValue(mockPaginatedResponse([vm1, vm2], 1, 20, 2));
      await voicemailStore.loadVoicemails();

      vi.spyOn(voicemailService, 'markAsListened').mockResolvedValue(undefined);

      await voicemailStore.markAsListened('vm-1');

      expect(voicemailService.markAsListened).toHaveBeenCalledWith('vm-1');
      const state = getState();
      expect(state.voicemails).toHaveLength(2);
      const updated = state.voicemails.find(v => v.id === 'vm-1');
      expect(updated?.listened).toBe(true);
      // Other voicemail should be unchanged
      const unchanged = state.voicemails.find(v => v.id === 'vm-2');
      expect(unchanged?.listened).toBe(true);
    });

    it('does not modify other voicemail fields', async () => {
      const vm = mockVoicemail({ id: 'vm-1', listened: false, callerName: 'Alice' });
      vi.spyOn(voicemailService, 'getVoicemails').mockResolvedValue(mockPaginatedResponse([vm], 1, 20, 1));
      await voicemailStore.loadVoicemails();

      vi.spyOn(voicemailService, 'markAsListened').mockResolvedValue(undefined);

      await voicemailStore.markAsListened('vm-1');

      const state = getState();
      const updated = state.voicemails[0];
      expect(updated.callerName).toBe('Alice');
      expect(updated.listened).toBe(true);
    });

    it('handles error without clearing loading', async () => {
      const vm = mockVoicemail({ id: 'vm-1' });
      vi.spyOn(voicemailService, 'getVoicemails').mockResolvedValue(mockPaginatedResponse([vm], 1, 20, 1));
      await voicemailStore.loadVoicemails();

      const error = new Error('Mark failed');
      vi.spyOn(voicemailService, 'markAsListened').mockRejectedValue(error);

      await voicemailStore.markAsListened('vm-1');

      const state = getState();
      expect(state.error).toBe('Mark failed');
      // loading should remain unchanged (not set to true/false by markAsListened on error)
      expect(state.loading).toBe(false);
    });
  });

  describe('deleteVoicemail', () => {
    it('deletes voicemail and updates list', async () => {
      const vm1 = mockVoicemail({ id: 'vm-1' });
      const vm2 = mockVoicemail({ id: 'vm-2' });
      vi.spyOn(voicemailService, 'getVoicemails').mockResolvedValue(mockPaginatedResponse([vm1, vm2], 1, 20, 2));
      await voicemailStore.loadVoicemails();

      vi.spyOn(voicemailService, 'deleteVoicemail').mockResolvedValue(undefined);

      await voicemailStore.deleteVoicemail('vm-1');

      expect(voicemailService.deleteVoicemail).toHaveBeenCalledWith('vm-1');
      const state = getState();
      expect(state.voicemails).toHaveLength(1);
      expect(state.voicemails[0].id).toBe('vm-2');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading during deletion', async () => {
      const vm = mockVoicemail();
      vi.spyOn(voicemailService, 'getVoicemails').mockResolvedValue(mockPaginatedResponse([vm], 1, 20, 1));
      await voicemailStore.loadVoicemails();

      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(voicemailService, 'deleteVoicemail').mockReturnValue(p as any);

      const deletePromise = voicemailStore.deleteVoicemail('vm-1');

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(undefined);
      await deletePromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const vm = mockVoicemail({ id: 'vm-1' });
      vi.spyOn(voicemailService, 'getVoicemails').mockResolvedValue(mockPaginatedResponse([vm], 1, 20, 1));
      await voicemailStore.loadVoicemails();

      const error = new Error('Delete failed');
      vi.spyOn(voicemailService, 'deleteVoicemail').mockRejectedValue(error);

      await voicemailStore.deleteVoicemail('vm-1');

      const state = getState();
      expect(state.error).toBe('Delete failed');
      expect(state.loading).toBe(false);
      expect(state.voicemails).toHaveLength(1); // unchanged
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(voicemailService, 'getVoicemails').mockRejectedValue(new Error('test error'));
      await voicemailStore.loadVoicemails().catch(() => {});

      expect(getState().error).toBe('test error');

      voicemailStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Load voicemails
      const vms = [mockVoicemail(), mockVoicemail()];
      vi.spyOn(voicemailService, 'getVoicemails').mockResolvedValue(mockPaginatedResponse(vms, 1, 20, 2));
      await voicemailStore.loadVoicemails();
      expect(getState().voicemails).toHaveLength(2);

      // Set currentVoicemail
      const vm = mockVoicemail({ id: 'vm-1' });
      vi.spyOn(voicemailService, 'getVoicemail').mockResolvedValue(vm);
      await voicemailStore.loadVoicemail('vm-1');
      expect(getState().currentVoicemail).not.toBeNull();

      // Reset
      voicemailStore.reset();

      const state = getState();
      expect(state.voicemails).toEqual([]);
      expect(state.currentVoicemail).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });
  });
});
