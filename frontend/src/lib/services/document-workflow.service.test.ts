import { describe, it, expect, vi, beforeEach } from 'vitest';
import { documentWorkflowService } from './documentWorkflowService';

// Mock browser check
vi.mock('$app/environment', () => ({
  browser: true,
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

// Helper to capture store subscriptions
function getStoreValue(store: any): any {
  let value: any;
  const unsub = store.subscribe((v: any) => { value = v; });
  unsub();
  return value;
}

describe('DocumentWorkflowService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    // Reset stores
    documentWorkflowService.workflows.set([]);
    documentWorkflowService.loading.set(false);
    documentWorkflowService.error.set(null);
    documentWorkflowService.total.set(0);
  });

  describe('fetchWorkflows', () => {
    it('fetches workflows and updates stores', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          data: [{ id: 'w1', documentId: 'd1', status: 'pending', createdAt: '', updatedAt: '' }],
          pagination: { total: '1' },
        }),
      });

      await documentWorkflowService.fetchWorkflows({ status: 'pending', page: 1, perPage: 10 });

      expect(mockFetch).toHaveBeenCalledWith('/api/government/document-workflows?status=pending&page=1&perPage=10');
      expect(getStoreValue(documentWorkflowService.workflows)).toHaveLength(1);
      expect(getStoreValue(documentWorkflowService.total)).toBe(1);
      expect(getStoreValue(documentWorkflowService.loading)).toBe(false);
      expect(getStoreValue(documentWorkflowService.error)).toBeNull();
    });

    it('sets error on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      await documentWorkflowService.fetchWorkflows();
      expect(getStoreValue(documentWorkflowService.error)).toContain('HTTP error! status: 500');
    });
  });

  describe('submitForReview', () => {
    it('submits and returns workflow data', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: { id: 'w1', documentId: 'd1', status: 'pending', createdAt: '', updatedAt: '' } }),
      });
      // fetchWorkflows call inside
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [], pagination: { total: '0' } }),
      });

      const result = await documentWorkflowService.submitForReview('d1');

      expect(mockFetch).toHaveBeenCalledWith('/api/government/document-workflows/d1/submit', { method: 'POST' });
      expect(result).toMatchObject({ id: 'w1', status: 'pending' });
    });

    it('returns null on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 400 });
      const result = await documentWorkflowService.submitForReview('d1');
      expect(result).toBeNull();
    });
  });

  describe('approve', () => {
    it('approves document and returns data', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: { id: 'w1', status: 'approved', processedBy: 'u1' } }),
      });
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [], pagination: { total: '0' } }),
      });

      const result = await documentWorkflowService.approve('d1', 'u1', 'OK');

      expect(mockFetch).toHaveBeenCalledWith('/api/government/document-workflows/d1/approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ approvedBy: 'u1', comments: 'OK' }),
      });
      expect(result).toMatchObject({ status: 'approved', processedBy: 'u1' });
    });

    it('returns null on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      const result = await documentWorkflowService.approve('d1', 'u1');
      expect(result).toBeNull();
    });
  });

  describe('reject', () => {
    it('rejects document', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: { id: 'w1', status: 'rejected' } }),
      });
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [], pagination: { total: '0' } }),
      });

      const result = await documentWorkflowService.reject('d1', 'u1', 'Bad');

      expect(mockFetch).toHaveBeenCalledWith('/api/government/document-workflows/d1/reject', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rejectedBy: 'u1', reason: 'Bad' }),
      });
      expect(result).toMatchObject({ status: 'rejected' });
    });

    it('handles null rejectedBy', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: {} }) });
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: [], pagination: { total: '0' } }) });
      await documentWorkflowService.reject('d1', null, 'Spam');
      const body = mockFetch.mock.calls[0][1].body;
      expect(body).toBe(JSON.stringify({ rejectedBy: null, reason: 'Spam' }));
    });
  });

  describe('publish', () => {
    it('publishes document', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: { id: 'w1', status: 'published' } }),
      });
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [], pagination: { total: '0' } }),
      });

      const result = await documentWorkflowService.publish('d1');

      expect(mockFetch).toHaveBeenCalledWith('/api/government/document-workflows/d1/publish', { method: 'POST' });
      expect(result).toMatchObject({ status: 'published' });
    });

    it('returns null on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      const result = await documentWorkflowService.publish('d1');
      expect(result).toBeNull();
    });
  });

  describe('getWorkflowHistory', () => {
    it('fetches history', async () => {
      const history = [{ id: 'w1', status: 'pending', createdAt: '', updatedAt: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: history }) });

      const result = await documentWorkflowService.getWorkflowHistory('d1');

      expect(mockFetch).toHaveBeenCalledWith('/api/government/document-workflows/d1/history');
      expect(result).toEqual(history);
    });

    it('returns empty array on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      const result = await documentWorkflowService.getWorkflowHistory('d1');
      expect(result).toEqual([]);
    });
  });

  describe('getPendingDocuments', () => {
    it('fetches pending workflows', async () => {
      const pending = [{ id: 'w1', status: 'pending', createdAt: '', updatedAt: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: pending }) });

      const result = await documentWorkflowService.getPendingDocuments();

      expect(mockFetch).toHaveBeenCalledWith('/api/government/document-workflows/pending');
      expect(result).toEqual(pending);
    });

    it('returns empty array on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      const result = await documentWorkflowService.getPendingDocuments();
      expect(result).toEqual([]);
    });
  });

  describe('getDocumentsByStatus', () => {
    it('fetches documents by status', async () => {
      const docs = [{ id: 'w1', status: 'approved', createdAt: '', updatedAt: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ data: docs }) });

      const result = await documentWorkflowService.getDocumentsByStatus('approved');

      expect(mockFetch).toHaveBeenCalledWith('/api/government/document-workflows/by-status/approved');
      expect(result).toEqual(docs);
    });

    it('returns empty array on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      const result = await documentWorkflowService.getDocumentsByStatus('rejected');
      expect(result).toEqual([]);
    });
  });
});
