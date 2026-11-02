import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { AdminService } from './adminService';
import { __testing__ } from './adminService';

// Suppress console logs
vi.spyOn(console, 'info').mockImplementation(() => {});
vi.spyOn(console, 'warn').mockImplementation(() => {});
vi.spyOn(console, 'error').mockImplementation(() => {});

describe('AdminService', () => {
  let service: AdminService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new AdminService();
  });

  describe('getStats', () => {
    it('should fetch admin stats', async () => {
      const stats = { usersCount: 100, activeSessions: 50 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(stats) });

      const result = await service.getStats();

      expect(result).toEqual(stats);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/stats');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getStats()).rejects.toThrow('Failed to fetch stats');
    });
  });

  describe('getUsers', () => {
    it('should fetch all users', async () => {
      const users = [{ id: 'u1', email: 'user@example.com' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(users) });

      const result = await service.getUsers();

      expect(result).toEqual(users);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/users');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getUsers()).rejects.toThrow('Failed to fetch users');
    });
  });

  describe('getUser', () => {
    it('should fetch user by id', async () => {
      const user = { id: 'u1', email: 'user@example.com' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(user) });

      const result = await service.getUser('u1');

      expect(result).toEqual(user);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/users/u1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getUser('u1')).rejects.toThrow('Failed to fetch user');
    });
  });

  describe('createUser', () => {
    const request = { email: 'new@example.com', password: 'pass', role: 'user' as const };

    it('should create user successfully', async () => {
      const user = { id: 'u2', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(user) });

      const result = await service.createUser(request);

      expect(result).toEqual(user);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/users', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"email":"new@example.com"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createUser(request)).rejects.toThrow('Failed to create user');
    });
  });

  describe('updateUserStatus', () => {
    it('should update user status', async () => {
      const user = { id: 'u1', status: 'active' as const };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(user) });

      const result = await service.updateUserStatus('u1', 'active');

      expect(result).toEqual(user);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/users/u1/status', expect.objectContaining({
        method: 'PUT',
        body: expect.stringContaining('"status":"active"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.updateUserStatus('u1', 'inactive')).rejects.toThrow('Failed to update user status');
    });
  });

  describe('getDocuments', () => {
    it('should fetch documents', async () => {
      const docs = [{ id: 'd1', name: 'Doc1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(docs) });

      const result = await service.getDocuments();

      expect(result).toEqual(docs);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/documents');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getDocuments()).rejects.toThrow('Failed to fetch documents');
    });
  });

  describe('updateDocument', () => {
    const request = { documentId: 'd1', status: 'approved' as const, notes: 'OK' };

    it('should update document', async () => {
      const doc = { id: 'd1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(doc) });

      const result = await service.updateDocument(request);

      expect(result).toEqual(doc);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/documents/d1', expect.objectContaining({
        method: 'PUT',
        body: expect.stringContaining('"status":"approved"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.updateDocument(request)).rejects.toThrow('Failed to update document');
    });
  });

  describe('getPopulation', () => {
    it('should fetch population with default pagination', async () => {
      const result = { data: [{ id: 'p1' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getPopulation();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/population?page=1&limit=50');
    });

    it('should fetch with custom pagination', async () => {
      const result = { data: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getPopulation(2, 100);

      expect(global.fetch).toHaveBeenCalledWith('/api/admin/population?page=2&limit=100');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getPopulation()).rejects.toThrow('Failed to fetch population');
    });
  });

  describe('searchPopulation', () => {
    it('should search population', async () => {
      const results = [{ id: 'p1', name: 'John' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(results) });

      const result = await service.searchPopulation('john');

      expect(result).toEqual(results);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/population/search?q=john');
    });

    it('should encode query', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue([]) });

      await service.searchPopulation('a b');

      expect(global.fetch).toHaveBeenCalledWith('/api/admin/population/search?q=a%20b');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.searchPopulation('q')).rejects.toThrow('Failed to search population');
    });
  });

  describe('getLogs', () => {
    it('should fetch system logs with default pagination', async () => {
      const result = { data: [{ id: 'l1', message: 'Log' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getLogs();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/admin/logs?page=1&limit=100');
    });

    it('should fetch with custom pagination', async () => {
      const result = { data: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getLogs(2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/admin/logs?page=2&limit=50');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getLogs()).rejects.toThrow('Failed to fetch logs');
    });
  });
});
