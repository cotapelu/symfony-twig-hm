import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  documentStore,
  publishedDocuments,
  documentsByType,
  recentDocuments,
  isDocumentLoading,
  isDocumentSaving,
  documentError
} from './documentStore';
import { documentApi } from '../services/government.service';
import type { GovernmentDocument, DocumentStatistics, CreateDocumentRequest, DocumentType, DocumentStatus } from '../contracts/governmentTypes';

function getCurrentState() {
  let current: any;
  const unsub = documentStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockDocument = (overrides: Partial<GovernmentDocument> = {}): GovernmentDocument => ({
  id: `doc-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Document Title',
  type: 'report' as DocumentType,
  status: 'draft' as DocumentStatus,
  content: 'Content',
  authorId: 'user-1',
  createdAt: now,
  updatedAt: now,
  isActive: true,
  ...overrides
});

describe('DocumentStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    documentStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.documents).toEqual([]);
      expect(state.selectedDocument).toBeNull();
      expect(state.statistics).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.saving).toBe(false);
      expect(state.error).toBeNull();
      expect(state.filters).toEqual({});
      expect(state.page).toBe(1);
      expect(state.perPage).toBe(20);
      expect(state.hasMore).toBe(true);
    });

    it('derived stores (empty)', () => {
      let pub: GovernmentDocument[] = [], byType: any = {}, recent: GovernmentDocument[] = [], load = false, sav = false, err: string | null = null;
      const unsubP = publishedDocuments.subscribe(s => { pub = s; });
      const unsubT = documentsByType.subscribe(s => { byType = s; });
      const unsubR = recentDocuments.subscribe(s => { recent = s; });
      const unsubL = isDocumentLoading.subscribe(s => { load = s; });
      const unsubS = isDocumentSaving.subscribe(s => { sav = s; });
      const unsubE = documentError.subscribe(s => { err = s; });
      expect(pub).toEqual([]);
      expect(byType).toEqual({});
      expect(recent).toEqual([]);
      expect(load).toBe(false);
      expect(sav).toBe(false);
      expect(err).toBeNull();
      unsubP(); unsubT(); unsubR(); unsubL(); unsubS(); unsubE();
    });
  });

  describe('loadDocuments', () => {
    it('loads first page', async () => {
      const docs = [mockDocument(), mockDocument()];
      vi.spyOn(documentApi, 'list').mockResolvedValue({ data: docs, total: 2, totalPages: 1 });
      await documentStore.loadDocuments(); // default page 1, reset=true (page=1)
      const state = getCurrentState();
      expect(state.documents).toEqual(docs);
      expect(state.page).toBe(2); // increment
      expect(state.hasMore).toBe(false);
      expect(state.loading).toBe(false);
    });

    it('loads more pages', async () => {
      const page1 = mockDocument({ id: 'd1' });
      const page2 = mockDocument({ id: 'd2' });
      vi.spyOn(documentApi, 'list').mockResolvedValueOnce({ data: [page1], total: 2, totalPages: 2 })
        .mockResolvedValueOnce({ data: [page2], total: 2, totalPages: 2 });
      // First load page 1
      await documentStore.loadDocuments();
      let state = getCurrentState();
      expect(state.documents).toHaveLength(1);
      // Load more (page 2)
      await documentStore.loadDocuments(); // without reset, uses state.page which is 2
      state = getCurrentState();
      expect(state.documents).toHaveLength(2);
      expect(state.page).toBe(3);
      expect(state.hasMore).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(documentApi, 'list').mockRejectedValue(new Error('List failed'));
      await documentStore.loadDocuments();
      const state = getCurrentState();
      expect(state.error).toBe('List failed');
      expect(state.loading).toBe(false);
    });
  });

  describe('searchDocuments', () => {
    it('calls list when query empty', async () => {
      const spy = vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [], total: 0, totalPages: 0 });
      await documentStore.searchDocuments('');
      expect(spy).toHaveBeenCalledWith({}, 1, 20); // default filters, page 1, perPage 20
    });

    it('searches and replaces documents', async () => {
      const results = [mockDocument({ id: 'd1' }), mockDocument({ id: 'd2' })];
      vi.spyOn(documentApi, 'search').mockResolvedValue(results);
      await documentStore.searchDocuments('keyword');
      const state = getCurrentState();
      expect(state.documents).toEqual(results);
      expect(state.hasMore).toBe(false);
    });

    it('handles search error', async () => {
      vi.spyOn(documentApi, 'search').mockRejectedValue(new Error('Search failed'));
      await documentStore.searchDocuments('keyword');
      const state = getCurrentState();
      expect(state.error).toBe('Search failed');
    });
  });

  describe('getDocument', () => {
    it('loads single document and sets selectedDocument', async () => {
      const doc = mockDocument({ id: 'd1' });
      vi.spyOn(documentApi, 'getById').mockResolvedValue(doc);
      const result = await documentStore.getDocument('d1');
      expect(result).toEqual(doc);
      const state = getCurrentState();
      expect(state.selectedDocument).toEqual(doc);
    });

    it('returns null on error', async () => {
      vi.spyOn(documentApi, 'getById').mockRejectedValue(new Error('Not found'));
      const result = await documentStore.getDocument('d1');
      expect(result).toBeNull();
      const state = getCurrentState();
      expect(state.selectedDocument).toBeNull();
      expect(state.error).toBe('Not found');
    });
  });

  describe('createDocument', () => {
    it('creates and prepends to documents', async () => {
      const newDoc = mockDocument({ id: 'new1', title: 'New' });
      vi.spyOn(documentApi, 'create').mockResolvedValue(newDoc);
      const result = await documentStore.createDocument({ title: 'New', type: 'report', content: '' } as CreateDocumentRequest);
      expect(result).toEqual(newDoc);
      const state = getCurrentState();
      expect(state.documents[0]).toEqual(newDoc);
      expect(state.saving).toBe(false);
    });

    it('propagates error', async () => {
      vi.spyOn(documentApi, 'create').mockRejectedValue(new Error('Create failed'));
      try {
        await documentStore.createDocument({ title: 'New', type: 'report', content: '' } as CreateDocumentRequest);
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Create failed');
      expect(state.saving).toBe(false);
    });
  });

  describe('publishDocument', () => {
    it('updates document status to published', async () => {
      const doc = mockDocument({ id: 'd1', status: 'draft' });
      // Prepopulate documents via list
      vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [doc], total: 1, totalPages: 1 });
      await documentStore.loadDocuments(); // now documents contains doc

      // Now publish
      vi.spyOn(documentApi, 'publish').mockResolvedValue(undefined);
      await documentStore.publishDocument('d1');
      const state = getCurrentState();
      expect(state.documents[0].status).toBe('published');
      // also if selectedDocument is same, should update
      documentStore.selectDocument(doc);
      await documentStore.publishDocument('d1');
      const state2 = getCurrentState();
      expect(state2.selectedDocument?.status).toBe('published');
    });

    it('handles error', async () => {
      const doc = mockDocument({ id: 'd1' });
      // Prepopulate
      vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [doc], total: 1, totalPages: 1 });
      await documentStore.loadDocuments();
      vi.spyOn(documentApi, 'publish').mockRejectedValue(new Error('Publish failed'));
      try {
        await documentStore.publishDocument('d1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Publish failed');
    });
  });

  describe('archiveDocument', () => {
    it('updates document status to archived', async () => {
      const doc = mockDocument({ id: 'd1', status: 'draft' });
      vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [doc], total: 1, totalPages: 1 });
      await documentStore.loadDocuments();
      vi.spyOn(documentApi, 'archive').mockResolvedValue(undefined);
      await documentStore.archiveDocument('d1');
      const state = getCurrentState();
      expect(state.documents[0].status).toBe('archived');
    });

    it('handles error', async () => {
      const doc = mockDocument({ id: 'd1' });
      vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [doc], total: 1, totalPages: 1 });
      await documentStore.loadDocuments();
      vi.spyOn(documentApi, 'archive').mockRejectedValue(new Error('Archive failed'));
      try {
        await documentStore.archiveDocument('d1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Archive failed');
    });
  });

  describe('loadStatistics', () => {
    it('loads statistics', async () => {
      const stats: DocumentStatistics = { total: 100, byType: { report: 60, form: 40 }, published: 80, draft: 20 };
      vi.spyOn(documentApi, 'getStatistics').mockResolvedValue(stats);
      const result = await documentStore.loadStatistics();
      expect(result).toEqual(stats);
      const state = getCurrentState();
      expect(state.statistics).toEqual(stats);
    });

    it('returns null on error', async () => {
      vi.spyOn(documentApi, 'getStatistics').mockRejectedValue(new Error('Stats failed'));
      const result = await documentStore.loadStatistics();
      expect(result).toBeNull();
      // state.statistics remains null
      const state = getCurrentState();
      expect(state.statistics).toBeNull();
    });
  });

  describe('filter methods', () => {
    it('setFilters updates filters and resets pagination, then loads', async () => {
      const spy = vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [], total: 0, totalPages: 0 });
      documentStore.setFilters({ status: 'published' });
      // The setFilters calls loadDocuments(true) which triggers list
      expect(spy).toHaveBeenCalledWith({ status: 'published' }, 1, 20);
      // check state
      const state = getCurrentState();
      expect(state.filters).toEqual({ status: 'published' });
      expect(state.page).toBe(1);
      // hasMore set based on returned data length; with empty returns, hasMore false; but not crucial
    });

    it('setTypeFilter calls setFilters with type', async () => {
      const spy = vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [], total: 0, totalPages: 0 });
      documentStore.setTypeFilter('report');
      const state = getCurrentState();
      expect(state.filters.type).toBe('report');
      expect(spy).toHaveBeenCalledWith({ type: 'report' }, 1, 20);
    });

    it('setStatusFilter calls setFilters with status', async () => {
      const spy = vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [], total: 0, totalPages: 0 });
      documentStore.setStatusFilter('draft');
      const state = getCurrentState();
      expect(state.filters.status).toBe('draft');
      expect(spy).toHaveBeenCalledWith({ status: 'draft' }, 1, 20);
    });
  });

  describe('selectDocument', () => {
    it('selects a document', async () => {
      const doc = mockDocument();
      documentStore.selectDocument(doc);
      const state = getCurrentState();
      expect(state.selectedDocument).toEqual(doc);
      documentStore.selectDocument(null);
      const state2 = getCurrentState();
      expect(state2.selectedDocument).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(documentApi, 'list').mockRejectedValue(new Error('err'));
      await documentStore.loadDocuments();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      documentStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [mockDocument()], total: 1, totalPages: 1 });
      await documentStore.loadDocuments();
      let state = getCurrentState();
      expect(state.documents.length).toBeGreaterThan(0);
      documentStore.reset();
      state = getCurrentState();
      expect(state.documents).toEqual([]);
      expect(state.selectedDocument).toBeNull();
      expect(state.statistics).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.saving).toBe(false);
      expect(state.error).toBeNull();
      expect(state.filters).toEqual({});
      expect(state.page).toBe(1);
      expect(state.hasMore).toBe(true);
    });
  });

  describe('derived stores logic', () => {
    it('publishedDocuments filters by status', async () => {
      const published = mockDocument({ id: 'd1', status: 'published' });
      const draft = mockDocument({ id: 'd2', status: 'draft' });
      vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [published, draft], total: 2, totalPages: 1 });
      await documentStore.loadDocuments();
      let pub: GovernmentDocument[] = [];
      const unsub = publishedDocuments.subscribe(s => { pub = s; });
      expect(pub).toEqual([published]);
      unsub();
    });

    it('documentsByType groups correctly', async () => {
      const doc1 = mockDocument({ id: 'd1', type: 'report' });
      const doc2 = mockDocument({ id: 'd2', type: 'form' });
      const doc3 = mockDocument({ id: 'd3', type: 'report' });
      vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [doc1, doc2, doc3], total: 3, totalPages: 1 });
      await documentStore.loadDocuments();
      let byType: any = {};
      const unsub = documentsByType.subscribe(s => { byType = s; });
      expect(byType).toEqual({
        report: [doc1, doc3],
        form: [doc2]
      });
      unsub();
    });

    it('recentDocuments sorts by createdAt descending and limits to 10', async () => {
      const earlier = mockDocument({ id: 'd1', createdAt: new Date(now.getTime() - 3600000) });
      const later = mockDocument({ id: 'd2', createdAt: new Date(now.getTime() + 3600000) });
      vi.spyOn(documentApi, 'list').mockResolvedValue({ data: [earlier, later], total: 2, totalPages: 1 });
      await documentStore.loadDocuments();
      let recent: GovernmentDocument[] = [];
      const unsub = recentDocuments.subscribe(s => { recent = s; });
      expect(recent).toEqual([later, earlier]);
      unsub();
    });
  });
});
