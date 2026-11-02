import { describe, it, expect, vi, beforeEach } from 'vitest';
import { governmentService } from './government';
import { getApiUrl } from '../config/api';

vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('GovernmentService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('Citizens', () => {
    it('getCitizens returns list', async () => {
      const citizens = [{ id: 'c1', name: 'John', cccd: '123', dob: '1990-01-01', gender: 'M', address: 'Hanoi', ward: 'W1', district: 'D1', phone: '0123456787' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ citizens }) });
      const result = await governmentService.getCitizens();
      expect(getApiUrl).toHaveBeenCalledWith('government', '/api/government/citizens');
      expect(result).toEqual(citizens);
    });

    it('createCitizen posts data', async () => {
      const newCitizen = { name: 'Jane', cccd: '456', dob: '1995-05-05', gender: 'F', address: 'HCM', ward: 'W2', district: 'D2', phone: '0987654321' };
      const created = { id: 'c2', ...newCitizen };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => created });
      const result = await governmentService.createCitizen(newCitizen);
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify(newCitizen),
      });
      expect(result).toEqual(created);
    });

    it('updateCitizen sends PUT', async () => {
      const updates = { address: 'New Address' };
      const updated = { id: 'c1', name: 'John', address: 'New Address' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => updated });
      const result = await governmentService.updateCitizen('c1', updates);
      expect(getApiUrl).toHaveBeenCalledWith('government', '/api/government/citizens/c1');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'PUT',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify(updates),
      });
      expect(result).toEqual(updated);
    });

    it('deleteCitizen sends DELETE', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });
      await governmentService.deleteCitizen('c1');
      expect(getApiUrl).toHaveBeenCalledWith('government', '/api/government/citizens/c1');
      expect(mockFetch).toHaveBeenCalledWith('/', { method: 'DELETE', headers: expect.objectContaining({ Authorization: 'Bearer test-token' }) });
    });
  });

  describe('Officers', () => {
    it('getOfficers returns list', async () => {
      const officers = [{ id: 'o1', name: 'Officer A', position: 'Manager', department: 'Dept1', phone: '0123456789', email: 'a@b.com', wards: ['W1'] }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ officers }) });
      const result = await governmentService.getOfficers();
      expect(result).toEqual(officers);
    });

    it('createOfficer posts data', async () => {
      const newOfficer = { name: 'New Officer', position: 'Staff', department: 'Dept2', phone: '0987654321', email: 'new@b.com', wards: ['W2'] };
      const created = { id: 'o2', ...newOfficer };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => created });
      const result = await governmentService.createOfficer(newOfficer);
      expect(result).toEqual(created);
    });
  });

  describe('Procedures', () => {
    it('getProcedures returns list', async () => {
      const procedures = [{ id: 'p1', name: 'ISS', code: '001', category: 'General', description: '...', requirements: [], processingTime: '3 days', fee: 0, status: 'active' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ procedures }) });
      const result = await governmentService.getProcedures();
      expect(result).toEqual(procedures);
    });

    it('createProcedure posts data', async () => {
      const newProc = { name: 'New Proc', category: 'Other', description: '...', requirements: [], processingTime: '5 days', fee: 100, status: 'draft' };
      const created = { id: 'p2', ...newProc };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => created });
      const result = await governmentService.createProcedure(newProc);
      expect(result).toEqual(created);
    });
  });

  describe('Documents', () => {
    it('getDocuments returns list', async () => {
      const docs = [{ id: 'd1', title: 'Doc1', type: 'application/pdf', content: '...', status: 'draft', createdBy: 'u1', createdAt: '2024-01-01' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ documents: docs }) });
      const result = await governmentService.getDocuments();
      expect(result).toEqual(docs);
    });

    it('createDocument posts data', async () => {
      const newDoc = { title: 'New Doc', type: 'text/plain', content: '...', status: 'draft', createdBy: 'u1' };
      const created = { id: 'd2', ...newDoc };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => created });
      const result = await governmentService.createDocument(newDoc);
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify(newDoc),
      });
      expect(result).toEqual(created);
    });

    it('publishDocument sends POST to publish endpoint', async () => {
      const published = { id: 'd1', status: 'published' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => published });
      const result = await governmentService.publishDocument('d1');
      expect(getApiUrl).toHaveBeenCalledWith('government', '/api/government/documents/d1/publish');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
      expect(result).toEqual(published);
    });
  });

  describe('Statistics', () => {
    it('getStatistics returns stats', async () => {
      const stats = { totalCitizens: 1000, totalOfficers: 50, totalProcedures: 20, pendingDocuments: 5, approvedDocuments: 95 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => stats });
      const result = await governmentService.getStatistics();
      expect(getApiUrl).toHaveBeenCalledWith('government', '/api/government/statistics');
      expect(result).toEqual(stats);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(governmentService.getStatistics()).rejects.toThrow('Failed to fetch statistics');
    });
  });
});
