import { describe, it, expect, vi, beforeEach } from 'vitest';
import * as api from './sample-module.service';

const mockFetch = vi.fn();
global.fetch = mockFetch;

const API_BASE = 'http://localhost:8080';

function okResponse(data: any) {
  return new Response(JSON.stringify(data), { status: 200, headers: { 'Content-Type': 'application/json' } });
}

function errorResponse(message: string, status = 400) {
  return new Response(JSON.stringify({ error: message }), { status, headers: { 'Content-Type': 'application/json' } });
}

describe('SampleModuleApi', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.localStorage.clear();
  });

  describe('create', () => {
    it('should create item and return data', async () => {
      const input = { name: 'Item1', description: 'Desc' };
      const responseData = { data: { id: '1', ...input, isActive: true, createdAt: 'now', updatedAt: 'now' } };
      mockFetch.mockResolvedValue(okResponse(responseData));

      const result = await api.sampleModuleApi.create(input as any);

      expect(mockFetch).toHaveBeenCalledWith(`${API_BASE}/api/sample-module/items`, {
        method: 'POST',
        headers: expect.objectContaining({ 'Content-Type': 'application/json', 'Accept': 'application/json' }),
        body: JSON.stringify(input),
      });
      expect(result).toEqual(responseData.data);
    });

    it('should throw on API error', async () => {
      mockFetch.mockResolvedValue(errorResponse('Create failed', 500));
      await expect(api.sampleModuleApi.create({ name: 'x' } as any)).rejects.toThrow('Create failed');
    });
  });

  describe('getById', () => {
    it('should fetch item by id', async () => {
      const data = { data: { id: '123', name: 'Item', isActive: true, createdAt: 'now', updatedAt: 'now' } };
      mockFetch.mockResolvedValue(okResponse(data));

      const result = await api.sampleModuleApi.getById('123');

      expect(mockFetch).toHaveBeenCalledWith(`${API_BASE}/api/sample-module/items/123`, expect.anything());
      expect(result).toEqual(data.data);
    });

    it('should throw on not found', async () => {
      mockFetch.mockResolvedValue(errorResponse('Not found', 404));
      await expect(api.sampleModuleApi.getById('999')).rejects.toThrow('Not found');
    });
  });

  describe('list', () => {
    it('should fetch paginated list with default page/perPage', async () => {
      const paginated = {
        data: [{ id: '1' }],
        page: 1,
        perPage: 20,
        total: 1,
      };
      mockFetch.mockResolvedValue(okResponse(paginated));

      const result = await api.sampleModuleApi.list();

      expect(mockFetch).toHaveBeenCalledWith(
        `${API_BASE}/api/sample-module/items?page=1&perPage=20`,
        expect.anything()
      );
      expect(result).toEqual(paginated);
    });

    it('should include filters and custom pagination', async () => {
      const paginated = { data: [], page: 2, perPage: 10, total: 0 };
      mockFetch.mockResolvedValue(okResponse(paginated));

      await api.sampleModuleApi.list({ search: 'test', active: true }, 2, 10);

      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('page=2&perPage=10&search=test&active=true'),
        expect.anything()
      );
    });

    it('should throw on error', async () => {
      mockFetch.mockResolvedValue(errorResponse('List error', 500));
      await expect(api.sampleModuleApi.list()).rejects.toThrow('List error');
    });
  });

  describe('search', () => {
    it('should search items', async () => {
      const results = [{ id: '1' }];
      mockFetch.mockResolvedValue(okResponse({ data: results }));

      const result = await api.sampleModuleApi.search('query');

      expect(mockFetch).toHaveBeenCalledWith(`${API_BASE}/api/sample-module/items/search?q=query`, expect.anything());
      expect(result).toEqual(results);
    });
  });

  describe('update', () => {
    it('should update item', async () => {
      const id = '123';
      const updates = { name: 'Updated' };
      const responseData = { data: { id, ...updates, isActive: true, createdAt: 'now', updatedAt: 'now' } };
      mockFetch.mockResolvedValue(okResponse(responseData));

      const result = await api.sampleModuleApi.update(id, updates as any);

      expect(mockFetch).toHaveBeenCalledWith(`${API_BASE}/api/sample-module/items/123`, {
        method: 'PUT',
        headers: expect.objectContaining({ 'Content-Type': 'application/json', 'Accept': 'application/json' }),
        body: JSON.stringify(updates),
      });
      expect(result).toEqual(responseData.data);
    });

    it('should throw on error', async () => {
      mockFetch.mockResolvedValue(errorResponse('Update failed', 500));
      await expect(api.sampleModuleApi.update('123', { name: 'x' } as any)).rejects.toThrow('Update failed');
    });
  });

  describe('deactivate', () => {
    it('should deactivate item', async () => {
      mockFetch.mockResolvedValue(okResponse({}));

      await api.sampleModuleApi.deactivate('123');

      expect(mockFetch).toHaveBeenCalledWith(`${API_BASE}/api/sample-module/items/123/deactivate`, {
        method: 'POST',
        headers: expect.objectContaining({ 'Accept': 'application/json' }),
      });
    });

    it('should throw on error', async () => {
      mockFetch.mockResolvedValue(errorResponse('Deactivate failed', 500));
      await expect(api.sampleModuleApi.deactivate('123')).rejects.toThrow('Deactivate failed');
    });
  });

  describe('activate', () => {
    it('should activate item', async () => {
      mockFetch.mockResolvedValue(okResponse({}));

      await api.sampleModuleApi.activate('123');

      expect(mockFetch).toHaveBeenCalledWith(`${API_BASE}/api/sample-module/items/123/activate`, {
        method: 'POST',
        headers: expect.objectContaining({ 'Accept': 'application/json' }),
      });
    });

    it('should throw on error', async () => {
      mockFetch.mockResolvedValue(errorResponse('Activate failed', 500));
      await expect(api.sampleModuleApi.activate('123')).rejects.toThrow('Activate failed');
    });
  });

  describe('getStatistics', () => {
    it('should fetch statistics', async () => {
      const stats = { total: 10, active: 5, inactive: 5 };
      mockFetch.mockResolvedValue(okResponse({ data: stats }));

      const result = await api.sampleModuleApi.getStatistics();

      expect(mockFetch).toHaveBeenCalledWith(`${API_BASE}/api/sample-module/items/statistics`, expect.anything());
      expect(result).toEqual(stats);
    });

    it('should throw on error', async () => {
      mockFetch.mockResolvedValue(errorResponse('Stats failed', 500));
      await expect(api.sampleModuleApi.getStatistics()).rejects.toThrow('Stats failed');
    });
  });
});
