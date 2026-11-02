import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { CompareService } from './compareService';
import { __testing__ } from './compareService';

describe('CompareService', () => {
  let service: CompareService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new CompareService();
  });

  describe('getHistory', () => {
    it('should fetch compare history', async () => {
      const history = [{ id: 'c1', items: [] }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(history) });

      const result = await service.getHistory();

      expect(result).toEqual(history);
      expect(global.fetch).toHaveBeenCalledWith('/api/compare/history');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getHistory()).rejects.toThrow('Failed to fetch history');
    });
  });

  describe('addItem', () => {
    const item = { id: 'i1', name: 'Item1' };

    it('should add item successfully', async () => {
      const result = [{ id: 'i2', name: 'Item2' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.addItem(item);

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/compare/items', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"id":"i1"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.addItem(item)).rejects.toThrow('Failed to add item');
    });
  });

  describe('removeItem', () => {
    it('should remove item successfully', async () => {
      const result = [{ id: 'i2' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.removeItem('i1');

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/compare/items/i1', expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.removeItem('i1')).rejects.toThrow('Failed to remove item');
    });
  });

  describe('clearCompare', () => {
    it('should clear compare successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.clearCompare()).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/compare/items', expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.clearCompare()).rejects.toThrow('Failed to clear compare');
    });
  });
});
