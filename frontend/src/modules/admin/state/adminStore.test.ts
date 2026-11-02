import { describe, it, expect, vi, beforeEach } from 'vitest';
import { adminService } from '../services/adminService';
import { adminStore, users, documents, population, logs, stats } from './adminStore';
import type { AdminStats, AdminUser, AdminDocument, PopulationRecord, SystemLog } from '../contracts/adminTypes';

// Helper to get current store state
function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockStats = (overrides: Partial<AdminStats> = {}): AdminStats => ({
  totalUsers: 100,
  activeUsers: 80,
  pendingDocuments: 5,
  systemUptime: 99.9,
  ...overrides
});

const mockUser = (overrides: Partial<AdminUser> = {}): AdminUser => ({
  id: `user-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test User',
  email: 'test@example.com',
  status: 'active',
  role: 'user',
  createdAt: new Date().toISOString(),
  ...overrides
});

const mockDocument = (overrides: Partial<AdminDocument> = {}): AdminDocument => ({
  id: `doc-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Test Document',
  status: 'pending',
  uploadedBy: 'user1',
  uploadedAt: new Date().toISOString(),
  ...overrides
});

const mockPopulationRecord = (overrides: Partial<PopulationRecord> = {}): PopulationRecord => ({
  id: `pop-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Citizen',
  age: 30,
  address: '123 St',
  ...overrides
});

const mockSystemLog = (overrides: Partial<SystemLog> = {}): SystemLog => ({
  id: `log-${Math.random().toString(36).substr(2, 5)}`,
  level: 'info',
  message: 'System start',
  timestamp: new Date().toISOString(),
  ...overrides
});

describe('AdminStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    adminStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getCurrentState(adminStore);
      expect(state.stats).toBeNull();
      expect(state.users).toEqual([]);
      expect(state.documents).toEqual([]);
      expect(state.population).toEqual([]);
      expect(state.logs).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('loadStats', () => {
    it('loads stats successfully', async () => {
      const stats = mockStats();
      vi.spyOn(adminService, 'getStats').mockResolvedValue(stats);

      await adminStore.loadStats();

      expect(adminService.getStats).toHaveBeenCalledTimes(1);
      const state = getCurrentState(adminStore);
      expect(state.stats).toEqual(stats);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(adminService, 'getStats').mockReturnValue(p as any);
      const loadPromise = adminStore.loadStats();
      await new Promise(process.nextTick);
      expect(getCurrentState(adminStore).loading).toBe(true);
      resolvePromise(mockStats());
      await loadPromise;
      expect(getCurrentState(adminStore).loading).toBe(false);
    });

    it('updates stats derived on success', async () => {
      const statsData = mockStats();
      vi.spyOn(adminService, 'getStats').mockResolvedValue(statsData);
      await adminStore.loadStats();
      let s: AdminStats | null = null;
      const unsub = stats.subscribe(val => { s = val; });
      expect(s).toEqual(statsData);
      unsub();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(adminService, 'getStats').mockRejectedValue(new Error('Stats API down'));

      await adminStore.loadStats();

      const state = getCurrentState(adminStore);
      expect(state.loading).toBe(false);
      expect(state.error).toBe('Stats API down');
    });
  });

  describe('loadUsers', () => {
    it('loads users successfully', async () => {
      const users = [mockUser(), mockUser()];
      vi.spyOn(adminService, 'getUsers').mockResolvedValue(users);

      await adminStore.loadUsers();

      const state = getCurrentState(adminStore);
      expect(state.users).toEqual(users);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(adminService, 'getUsers').mockReturnValue(p as any);
      const loadPromise = adminStore.loadUsers();
      await new Promise(process.nextTick);
      expect(getCurrentState(adminStore).loading).toBe(true);
      resolvePromise([mockUser()]);
      await loadPromise;
      expect(getCurrentState(adminStore).loading).toBe(false);
    });

    it('updates users derived on success', async () => {
      const userList = [mockUser()];
      vi.spyOn(adminService, 'getUsers').mockResolvedValue(userList);
      await adminStore.loadUsers();
      let u: AdminUser[] = [];
      const unsub = users.subscribe(s => { u = s; });
      expect(u).toEqual(userList);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(adminService, 'getUsers').mockResolvedValue([]);
      await adminStore.loadUsers();
      expect(getCurrentState(adminStore).users).toEqual([]);
      expect(getCurrentState(adminStore).loading).toBe(false);
      expect(getCurrentState(adminStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(adminService, 'getUsers').mockRejectedValue(new Error('Network'));
      await adminStore.loadUsers();
      const state = getCurrentState(adminStore);
      expect(state.error).toBe('Network');
      expect(state.loading).toBe(false);
      expect(state.users).toEqual([]);
    });
  });

  describe('loadDocuments', () => {
    it('loads documents successfully', async () => {
      const docs = [mockDocument(), mockDocument()];
      vi.spyOn(adminService, 'getDocuments').mockResolvedValue(docs);

      await adminStore.loadDocuments();

      const state = getCurrentState(adminStore);
      expect(state.documents).toEqual(docs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(adminService, 'getDocuments').mockReturnValue(p as any);
      const loadPromise = adminStore.loadDocuments();
      await new Promise(process.nextTick);
      expect(getCurrentState(adminStore).loading).toBe(true);
      resolvePromise([mockDocument()]);
      await loadPromise;
      expect(getCurrentState(adminStore).loading).toBe(false);
    });

    it('updates documents derived on success', async () => {
      const docs = [mockDocument()];
      vi.spyOn(adminService, 'getDocuments').mockResolvedValue(docs);
      await adminStore.loadDocuments();
      let d: AdminDocument[] = [];
      const unsub = documents.subscribe(s => { d = s; });
      expect(d).toEqual(docs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(adminService, 'getDocuments').mockResolvedValue([]);
      await adminStore.loadDocuments();
      expect(getCurrentState(adminStore).documents).toEqual([]);
      expect(getCurrentState(adminStore).loading).toBe(false);
      expect(getCurrentState(adminStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(adminService, 'getDocuments').mockRejectedValue(new Error('Fail'));

      await adminStore.loadDocuments();

      const state = getCurrentState(adminStore);
      expect(state.error).toBe('Fail');
      expect(state.loading).toBe(false);
      expect(state.documents).toEqual([]);
    });
  });

  describe('updateDocument', () => {
    it('updates document and modifies list', async () => {
      // Preload documents
      const doc1 = mockDocument({ id: '1', status: 'pending' });
      const doc2 = mockDocument({ id: '2', status: 'approved' });
      vi.spyOn(adminService, 'getDocuments').mockResolvedValue([doc1, doc2]);
      await adminStore.loadDocuments();

      // Now update doc1
      const updatedDoc = mockDocument({ id: '1', status: 'approved' });
      vi.spyOn(adminService, 'updateDocument').mockResolvedValue(updatedDoc);

      const result = await adminStore.updateDocument('1', 'approved', 'Approved now');

      expect(adminService.updateDocument).toHaveBeenCalledWith({ documentId: '1', status: 'approved', notes: 'Approved now' });
      expect(result).toEqual(updatedDoc);
      const state = getCurrentState(adminStore);
      expect(state.documents[0]).toEqual(updatedDoc);
      expect(state.documents[0].status).toBe('approved');
    });

    it('handles error', async () => {
      vi.spyOn(adminService, 'updateDocument').mockRejectedValue(new Error('Update fail'));

      await expect(adminStore.updateDocument('1', 'rejected')).rejects.toThrow('Update fail');

      const state = getCurrentState(adminStore);
      expect(state.error).toBe('Update fail');
    });
  });

  describe('loadPopulation', () => {
    it('loads population with default pagination', async () => {
      const records = [mockPopulationRecord(), mockPopulationRecord()];
      vi.spyOn(adminService, 'getPopulation').mockResolvedValue({ data: records, total: 2 });

      await adminStore.loadPopulation();

      expect(adminService.getPopulation).toHaveBeenCalledWith(1, 50);
      const state = getCurrentState(adminStore);
      expect(state.population).toEqual(records);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads specific page and limit', async () => {
      const records = [mockPopulationRecord()];
      vi.spyOn(adminService, 'getPopulation').mockResolvedValue({ data: records, total: 1 });

      await adminStore.loadPopulation(2, 25);

      expect(adminService.getPopulation).toHaveBeenCalledWith(2, 25);
      const state = getCurrentState(adminStore);
      expect(state.population).toEqual(records);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(adminService, 'getPopulation').mockReturnValue(p as any);
      const loadPromise = adminStore.loadPopulation();
      await new Promise(process.nextTick);
      expect(getCurrentState(adminStore).loading).toBe(true);
      resolvePromise({ data: [mockPopulationRecord()], total: 1 });
      await loadPromise;
      expect(getCurrentState(adminStore).loading).toBe(false);
    });

    it('updates population derived on success', async () => {
      const records = [mockPopulationRecord()];
      vi.spyOn(adminService, 'getPopulation').mockResolvedValue({ data: records, total: 1 });
      await adminStore.loadPopulation();
      let p: PopulationRecord[] = [];
      const unsub = population.subscribe(s => { p = s; });
      expect(p).toEqual(records);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(adminService, 'getPopulation').mockResolvedValue({ data: [], total: 0 });
      await adminStore.loadPopulation();
      expect(getCurrentState(adminStore).population).toEqual([]);
      expect(getCurrentState(adminStore).loading).toBe(false);
      expect(getCurrentState(adminStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(adminService, 'getPopulation').mockRejectedValue(new Error('Bad request'));

      await adminStore.loadPopulation();

      const state = getCurrentState(adminStore);
      expect(state.error).toBe('Bad request');
      expect(state.loading).toBe(false);
      expect(state.population).toEqual([]);
    });
  });

  describe('searchPopulation', () => {
    it('searches population successfully', async () => {
      const results = [mockPopulationRecord({ name: 'John' })];
      vi.spyOn(adminService, 'searchPopulation').mockResolvedValue(results);

      await adminStore.searchPopulation('John');

      expect(adminService.searchPopulation).toHaveBeenCalledWith('John');
      const state = getCurrentState(adminStore);
      expect(state.population).toEqual(results);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(adminService, 'searchPopulation').mockReturnValue(p as any);
      const searchPromise = adminStore.searchPopulation('John');
      await new Promise(process.nextTick);
      expect(getCurrentState(adminStore).loading).toBe(true);
      resolvePromise([mockPopulationRecord()]);
      await searchPromise;
      expect(getCurrentState(adminStore).loading).toBe(false);
    });

    it('updates population derived on success', async () => {
      const results = [mockPopulationRecord()];
      vi.spyOn(adminService, 'searchPopulation').mockResolvedValue(results);
      await adminStore.searchPopulation('q');
      let p: PopulationRecord[] = [];
      const unsub = population.subscribe(s => { p = s; });
      expect(p).toEqual(results);
      unsub();
    });

    it('handles empty results', async () => {
      vi.spyOn(adminService, 'searchPopulation').mockResolvedValue([]);
      await adminStore.searchPopulation('Nonexistent');
      const state = getCurrentState(adminStore);
      expect(state.population).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(adminService, 'searchPopulation').mockRejectedValue(new Error('Search fail'));

      await adminStore.searchPopulation('q');

      const state = getCurrentState(adminStore);
      expect(state.error).toBe('Search fail');
      expect(state.loading).toBe(false);
      expect(state.population).toEqual([]);
    });
  });

  describe('loadLogs', () => {
    it('loads logs with default pagination', async () => {
      const logs = [mockSystemLog(), mockSystemLog()];
      vi.spyOn(adminService, 'getLogs').mockResolvedValue({ data: logs, total: 2 });

      await adminStore.loadLogs();

      expect(adminService.getLogs).toHaveBeenCalledWith(1, 100);
      const state = getCurrentState(adminStore);
      expect(state.logs).toEqual(logs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads logs with custom pagination', async () => {
      const logs = [mockSystemLog()];
      vi.spyOn(adminService, 'getLogs').mockResolvedValue({ data: logs, total: 10 });

      await adminStore.loadLogs(2, 50);

      expect(adminService.getLogs).toHaveBeenCalledWith(2, 50);
      const state = getCurrentState(adminStore);
      expect(state.logs).toEqual(logs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(adminService, 'getLogs').mockReturnValue(p as any);
      const loadPromise = adminStore.loadLogs();
      await new Promise(process.nextTick);
      expect(getCurrentState(adminStore).loading).toBe(true);
      resolvePromise({ data: [mockSystemLog()], total: 1 });
      await loadPromise;
      expect(getCurrentState(adminStore).loading).toBe(false);
    });

    it('updates logs derived on success', async () => {
      const logData = [mockSystemLog()];
      vi.spyOn(adminService, 'getLogs').mockResolvedValue({ data: logData, total: 1 });
      await adminStore.loadLogs();
      let l: SystemLog[] = [];
      const unsub = logs.subscribe(s => { l = s; });
      expect(l).toEqual(logData);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(adminService, 'getLogs').mockResolvedValue({ data: [], total: 0 });
      await adminStore.loadLogs();
      expect(getCurrentState(adminStore).logs).toEqual([]);
      expect(getCurrentState(adminStore).loading).toBe(false);
      expect(getCurrentState(adminStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(adminService, 'getLogs').mockRejectedValue(new Error('Logs API down'));

      await adminStore.loadLogs();

      const state = getCurrentState(adminStore);
      expect(state.error).toBe('Logs API down');
      expect(state.loading).toBe(false);
      expect(state.logs).toEqual([]);
    });
  });

  describe('updateUserStatus', () => {
    it('updates user status and updates users list', async () => {
      const user1 = mockUser({ id: 'u1', status: 'active' });
      const user2 = mockUser({ id: 'u2', status: 'inactive' });
      // Preload users
      vi.spyOn(adminService, 'getUsers').mockResolvedValue([user1, user2]);
      await adminStore.loadUsers();

      const updatedUser = mockUser({ id: 'u1', status: 'suspended' });
      vi.spyOn(adminService, 'updateUserStatus').mockResolvedValue(updatedUser);

      const result = await adminStore.updateUserStatus('u1', 'suspended');

      expect(adminService.updateUserStatus).toHaveBeenCalledWith('u1', 'suspended');
      expect(result).toEqual(updatedUser);
      const state = getCurrentState(adminStore);
      expect(state.users[0].status).toBe('suspended');
    });

    it('handles error', async () => {
      vi.spyOn(adminService, 'updateUserStatus').mockRejectedValue(new Error('Status update failed'));

      await expect(adminStore.updateUserStatus('u1', 'active')).rejects.toThrow('Status update failed');

      const state = getCurrentState(adminStore);
      expect(state.error).toBe('Status update failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(adminService, 'getStats').mockRejectedValue(new Error('Test error'));
      try {
        await adminStore.loadStats();
      } catch (e) {}
      let state = getCurrentState(adminStore);
      expect(state.error).toBe('Test error');

      adminStore.clearError();
      state = getCurrentState(adminStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      // Modify state
      const stats = mockStats();
      vi.spyOn(adminService, 'getStats').mockResolvedValue(stats);
      await adminStore.loadStats();
      let state = getCurrentState(adminStore);
      expect(state.stats).not.toBeNull();

      adminStore.reset();
      state = getCurrentState(adminStore);
      expect(state.stats).toBeNull();
      expect(state.users).toEqual([]);
      expect(state.documents).toEqual([]);
      expect(state.population).toEqual([]);
      expect(state.logs).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
