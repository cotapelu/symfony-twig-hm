import { writable, derived } from 'svelte/store';
import type { AdminState, AdminStats, AdminUser, AdminDocument, PopulationRecord, SystemLog } from '../contracts/adminTypes';
import { adminService } from '../services/adminService';

const initialState: AdminState = {
  stats: null,
  users: [],
  documents: [],
  population: [],
  logs: [],
  loading: false,
  error: null
};

function createAdminStore() {
  const { subscribe, set, update } = writable<AdminState>(initialState);

  return {
    subscribe,

    async loadStats() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const stats = await adminService.getStats();
        update(s => ({ ...s, stats, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadUsers() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const users = await adminService.getUsers();
        update(s => ({ ...s, users, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadDocuments() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const documents = await adminService.getDocuments();
        update(s => ({ ...s, documents, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async updateDocument(documentId: string, status: 'approved' | 'rejected', notes?: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const document = await adminService.updateDocument({ documentId, status, notes });
        update(s => ({
          ...s,
          documents: s.documents.map(d => d.id === documentId ? document : d),
          loading: false
        }));
        return document;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadPopulation(page = 1, limit = 50) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const result = await adminService.getPopulation(page, limit);
        update(s => ({ ...s, population: result.data, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async searchPopulation(query: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const population = await adminService.searchPopulation(query);
        update(s => ({ ...s, population, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadLogs(page = 1, limit = 100) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const result = await adminService.getLogs(page, limit);
        update(s => ({ ...s, logs: result.data, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async updateUserStatus(userId: string, status: 'active' | 'inactive' | 'suspended') {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const user = await adminService.updateUserStatus(userId, status);
        update(s => ({
          ...s,
          users: s.users.map(u => u.id === userId ? user : u),
          loading: false
        }));
        return user;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const adminStore = createAdminStore();

export const stats = derived(adminStore, $s => $s.stats);
export const users = derived(adminStore, $s => $s.users);
export const documents = derived(adminStore, $s => $s.documents);
export const pendingDocuments = derived(adminStore, $s => $s.documents.filter(d => d.status === 'pending'));
export const population = derived(adminStore, $s => $s.population);
export const logs = derived(adminStore, $s => $s.logs);
export const adminLoading = derived(adminStore, $s => $s.loading);
export const adminError = derived(adminStore, $s => $s.error);
