import { describe, it, expect, vi, beforeEach } from 'vitest';
import { citizenService } from './citizenService';

// Mock browser check
vi.mock('$app/environment', () => ({
  browser: true,
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

// Helper to get store values
function getStoreValue(store: any): any {
  let value: any;
  const unsub = store.subscribe((v: any) => { value = v; });
  unsub();
  return value;
}

describe('CitizenService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    // Reset stores
    citizenService.citizens.set([]);
    citizenService.loading.set(false);
    citizenService.error.set(null);
    citizenService.total.set(0);
  });

  describe('fetchCitizens', () => {
    it('fetches citizens with filters', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          data: [{ id: 'c1', fullName: 'John', gender: 'M', isActive: true }],
          pagination: { total: '1' },
        }),
      });

      await citizenService.fetchCitizens({ province: 'Hanoi', isActive: true, page: 1, perPage: 10 });

      expect(mockFetch).toHaveBeenCalledWith('/api/government/statistics/citizens?province=Hanoi&isActive=true&page=1&perPage=10');
      expect(getStoreValue(citizenService.citizens)).toHaveLength(1);
      expect(getStoreValue(citizenService.total)).toBe(1);
      expect(getStoreValue(citizenService.loading)).toBe(false);
      expect(getStoreValue(citizenService.error)).toBeNull();
    });

    it('sets error on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      await citizenService.fetchCitizens();
      expect(getStoreValue(citizenService.error)).toContain('HTTP error! status: 500');
    });
  });

  describe('createCitizen', () => {
    it('creates a new citizen and returns it', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [{ id: 'c2', fullName: 'Jane', gender: 'F', isActive: true }] }),
      });
      // Also mock fetchCitizens call inside createCitizen
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [], pagination: { total: '0' } }),
      });

      const result = await citizenService.createCitizen({ fullName: 'Jane', gender: 'F' });

      expect(mockFetch).toHaveBeenCalledWith('/api/government/import/citizens', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ citizens: [{ fullName: 'Jane', gender: 'F' }] }),
      });
      expect(result).toEqual({ id: 'c2', fullName: 'Jane', gender: 'F', isActive: true });
    });

    it('returns null on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 400, json: async () => ({}) });
      const result = await citizenService.createCitizen({ fullName: 'Test' });
      expect(result).toBeNull();
      expect(getStoreValue(citizenService.error)).toContain('HTTP error! status: 400');
    });
  });

  describe('updateCitizen', () => {
    it('updates citizen and returns true', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: [], pagination: { total: '0' } }) });

      const result = await citizenService.updateCitizen('c1', { phone: '123456' });

      expect(mockFetch).toHaveBeenCalledWith('/api/government/citizens/c1', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone: '123456' }),
      });
      expect(result).toBe(true);
    });

    it('returns false on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      const result = await citizenService.updateCitizen('c1', {});
      expect(result).toBe(false);
    });
  });

  describe('deleteCitizen', () => {
    it('deactivates citizen and returns true', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: [], pagination: { total: '0' } }) });

      const result = await citizenService.deleteCitizen('c1');

      expect(mockFetch).toHaveBeenCalledWith('/api/government/citizens/c1/deactivate', { method: 'POST' });
      expect(result).toBe(true);
    });

    it('returns false on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      const result = await citizenService.deleteCitizen('c1');
      expect(result).toBe(false);
    });
  });

  describe('exportCitizens', () => {
    it('exports citizens as CSV blob', async () => {
      const mockBlob = new Blob(['csv content'], { type: 'text/csv' });
      mockFetch.mockResolvedValueOnce({ ok: true, blob: async () => mockBlob });

      const result = await citizenService.exportCitizens('csv', { province: 'Hanoi' });

      expect(mockFetch).toHaveBeenCalledWith('/api/government/export/csv?province=Hanoi');
      expect(result).toEqual(mockBlob);
    });

    it('returns null on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      const result = await citizenService.exportCitizens('json');
      expect(result).toBeNull();
    });
  });

  describe('getCitizenById', () => {
    it('fetches citizen by ID', async () => {
      const citizen = { id: 'c1', fullName: 'John', gender: 'M', isActive: true };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => citizen });

      const result = await citizenService.getCitizenById('c1');

      expect(mockFetch).toHaveBeenCalledWith('/api/government/citizens/c1');
      expect(result).toEqual(citizen);
    });

    it('returns null if 404', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 404 });
      const result = await citizenService.getCitizenById('c1');
      expect(result).toBeNull();
    });

    it('returns null on other error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      const result = await citizenService.getCitizenById('c1');
      expect(result).toBeNull();
      expect(getStoreValue(citizenService.error)).toContain('HTTP error! status: 500');
    });
  });
});
