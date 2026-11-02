import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => { store[key] = value; },
    removeItem: (key: string) => { delete store[key]; },
    clear: () => { store = {}; }
  };
})();
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// Mock global fetch
global.fetch = vi.fn();

// Import after mocks defined
import {
  citizenApi,
  documentApi,
  procedureApi,
  applicationApi
} from './government.service';

describe('Government Service', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.clear();
  });

  // ==================== Citizen API
  describe('citizenApi', () => {
    const mockCitizen = { id: 'c1', name: 'John Doe' };
    const mockPaginated = { data: [mockCitizen], total: 1, page: 1, perPage: 20 };

    it('should create citizen successfully', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockCitizen })
      });

      const result = await citizenApi.create({ name: 'John Doe', dob: '1990-01-01' });

      expect(result).toEqual(mockCitizen);
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/government/citizens'),
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('should get citizen by id', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockCitizen })
      });

      const result = await citizenApi.getById('c1');

      expect(result).toEqual(mockCitizen);
    });

    it('should list citizens with filters', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockPaginated
      });

      const result = await citizenApi.list({ search: 'john' }, 2, 50);

      expect(result).toEqual(mockPaginated);
      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('page=2');
      expect(url).toContain('perPage=50');
      expect(url).toContain('search=john');
    });

    it('should search citizens', async () => {
      const results = [{ id: 'c1' }];
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: results })
      });

      const result = await citizenApi.search('john');

      expect(result).toEqual(results);
      expect((global.fetch as any).mock.calls[0][0]).toContain('/search?q=john');
    });

    it('should update citizen', async () => {
      const updated = { id: 'c1', name: 'Jane' };
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: updated })
      });

      const result = await citizenApi.update('c1', { name: 'Jane' });

      expect(result).toEqual(updated);
    });

    it('should deactivate citizen', async () => {
      global.fetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });

      await citizenApi.deactivate('c1');

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/government/citizens/c1/deactivate'),
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('should get statistics', async () => {
      const stats = { total: 100, byProvince: { 'HN': 50 } };
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: stats })
      });

      const result = await citizenApi.getStatistics({ province: 'HN' });

      expect(result).toEqual(stats);
    });
  });

  // ==================== Document API
  describe('documentApi', () => {
    const mockDoc = { id: 'd1', title: 'Form' };

    it('should create document', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockDoc })
      });

      const result = await documentApi.create({ title: 'Form', type: 'application' });

      expect(result).toEqual(mockDoc);
    });

    it('should list documents', async () => {
      const paginated = { data: [mockDoc], total: 1, page: 1, perPage: 20 };
      global.fetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: paginated }) });

      const result = await documentApi.list({ type: 'form' }, 1, 10);

      expect(result).toEqual(paginated);
    });

    it('should search documents', async () => {
      const docs = [{ id: 'd1' }];
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: docs })
      });

      const result = await documentApi.search('tax', 5);

      expect(result).toEqual(docs);
      expect((global.fetch as any).mock.calls[0][0]).toContain('limit=5');
    });

    it('should get document by id', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockDoc })
      });

      const result = await documentApi.getById('d1');

      expect(result).toEqual(mockDoc);
    });

    it('should publish document', async () => {
      global.fetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });

      await documentApi.publish('d1');

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/government/documents/d1/publish'),
        expect.objectContaining({ method: 'POST' })
      );
    });
  });

  // ==================== Procedure API
  describe('procedureApi', () => {
    const mockProc = { id: 'p1', name: 'Passport' };

    it('should create procedure', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockProc })
      });

      const result = await procedureApi.create({ name: 'Passport', category: 'civil' });

      expect(result).toEqual(mockProc);
    });

    it('should list procedures', async () => {
      const paginated = { data: [mockProc], total: 1, page: 1, perPage: 20 };
      global.fetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: paginated }) });

      const result = await procedureApi.list({ category: 'civil' }, 1, 10);

      expect(result).toEqual(paginated);
    });

    it('should get active procedures', async () => {
      const procs = [{ id: 'p1' }];
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: procs })
      });

      const result = await procedureApi.getActive();

      expect(result).toEqual(procs);
    });

    it('should delete procedure', async () => {
      global.fetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });

      await procedureApi.delete('p1');

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/government/procedures/p1'),
        expect.objectContaining({ method: 'DELETE' })
      );
    });
  });

  // ==================== Application API
  describe('applicationApi', () => {
    const mockApp = { id: 'a1', status: 'pending' };

    it('should create application', async () => {
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: mockApp })
      });

      const result = await applicationApi.create({ procedureId: 'p1', citizenId: 'c1' });

      expect(result).toEqual(mockApp);
    });

    it('should list applications', async () => {
      const paginated = { data: [mockApp], total: 1, page: 1, perPage: 20 };
      global.fetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: paginated }) });

      const result = await applicationApi.list({ status: 'pending' }, 1, 10);

      expect(result).toEqual(paginated);
    });

    it('should get pending applications', async () => {
      const apps = [{ id: 'a1' }];
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: apps })
      });

      const result = await applicationApi.getPending();

      expect(result).toEqual(apps);
    });

    it('should update status', async () => {
      const updated = { id: 'a1', status: 'approved' };
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: updated })
      });

      const result = await applicationApi.updateStatus('a1', 'approved', 'All good');

      expect(result).toEqual(updated);
    });

    it('should get statistics', async () => {
      const stats = { total: 10, byStatus: { pending: 5 }, pending: 5, completedThisMonth: 2 };
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: stats })
      });

      const result = await applicationApi.getStatistics();

      expect(result).toEqual(stats);
    });
  });
});
