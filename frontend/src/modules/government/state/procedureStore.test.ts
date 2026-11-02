import { describe, it, expect, vi, beforeEach } from 'vitest';
import { procedureStore } from './procedureStore';
import { procedureApi, applicationApi } from '../services/government.service';
import { GovernmentEventBus } from '../events/government.events';
import type { 
  AdministrativeProcedure, 
  ProcedureApplication,
  CreateProcedureRequest,
  CreateApplicationRequest,
  ApplicationStatus,
  ProcedureCategory 
} from '../contracts/government.types';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = procedureStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockProcedure = (overrides: Partial<AdministrativeProcedure> = {}): AdministrativeProcedure => ({
  id: `proc-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test Procedure',
  description: 'A test procedure',
  category: 'permit' as ProcedureCategory,
  isActive: true,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  ...overrides
});

const mockApplication = (overrides: Partial<ProcedureApplication> = {}): ProcedureApplication => ({
  id: `app-${Math.random().toString(36).substr(2, 5)}`,
  procedureId: 'proc-1',
  citizenId: 'cit-1',
  status: 'submitted' as ApplicationStatus,
  submittedAt: new Date().toISOString(),
  ...overrides
});

const mockCreateProcedureRequest = (overrides: Partial<CreateProcedureRequest> = {}): CreateProcedureRequest => ({
  name: 'New Procedure',
  description: 'New description',
  category: 'permit' as ProcedureCategory,
  ...overrides
});

const mockCreateApplicationRequest = (overrides: Partial<CreateApplicationRequest> = {}): CreateApplicationRequest => ({
  procedureId: 'proc-1',
  citizenId: 'cit-1',
  data: {},
  ...overrides
});

describe('ProcedureStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    procedureStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.procedures).toEqual([]);
      expect(state.applications).toEqual([]);
      expect(state.selectedProcedure).toBeNull();
      expect(state.selectedApplication).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.saving).toBe(false);
      expect(state.error).toBeNull();
      expect(state.procedureFilters).toEqual({});
      expect(state.applicationFilters).toEqual({});
      expect(state.page).toBe(1);
      expect(state.perPage).toBe(20);
      expect(state.hasMore).toBe(true);
    });
  });

  describe('loadProcedures', () => {
    it('loads procedures successfully (reset)', async () => {
      const procedures = [mockProcedure(), mockProcedure()];
      const response = { data: procedures, page: 1, total: 2, totalPages: 1 };
      vi.spyOn(procedureApi, 'list').mockResolvedValue(response);

      await procedureStore.loadProcedures(true); // reset

      expect(procedureApi.list).toHaveBeenCalledWith({}, 1, 20);
      const state = getState();
      expect(state.procedures).toEqual(procedures);
      expect(state.page).toBe(2);
      // hasMore false because fewer items than perPage
      expect(state.hasMore).toBe(false);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('appends procedures when not reset', async () => {
      const firstBatch = [mockProcedure({ id: 'p1' })];
      const secondBatch = [mockProcedure({ id: 'p2' })];
      // First load
      vi.spyOn(procedureApi, 'list').mockResolvedValueOnce({ data: firstBatch, page: 1, total: 1, totalPages: 1 })
        .mockResolvedValueOnce({ data: secondBatch, page: 2, total: 1, totalPages: 1 });

      await procedureStore.loadProcedures(true);
      expect(getState().procedures).toHaveLength(1);
      expect(getState().page).toBe(2);

      await procedureStore.loadProcedures(false);
      expect(getState().procedures).toHaveLength(2);
      expect(getState().page).toBe(3);
    });

    it('sets hasMore true if full page returned', async () => {
      const perPage = 20;
      // Override initialState perPage? Store uses 20, so create 20 items.
      const fullBatch = Array.from({ length: perPage }, () => mockProcedure());
      vi.spyOn(procedureApi, 'list').mockResolvedValue({ data: fullBatch, page: 1, total: perPage, totalPages: 1 });

      await procedureStore.loadProcedures(true);

      const state = getState();
      expect(state.hasMore).toBe(true);
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(procedureApi, 'list').mockReturnValue(p as any);

      const loadPromise = procedureStore.loadProcedures();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise({ data: [mockProcedure()], page: 1, total: 1, totalPages: 1 });
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to load procedures');
      vi.spyOn(procedureApi, 'list').mockRejectedValue(error);

      await procedureStore.loadProcedures();

      const state = getState();
      expect(state.error).toBe('Failed to load procedures');
      expect(state.loading).toBe(false);
      expect(state.procedures).toEqual([]);
    });
  });

  describe('getProcedure', () => {
    it('loads procedure and sets selectedProcedure', async () => {
      const proc = mockProcedure();
      vi.spyOn(procedureApi, 'getById').mockResolvedValue(proc);

      const result = await procedureStore.getProcedure('proc-1');

      expect(result).toEqual(proc);
      expect(procedureApi.getById).toHaveBeenCalledWith('proc-1');
      const state = getState();
      expect(state.selectedProcedure).toEqual(proc);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error and returns null', async () => {
      const error = new Error('Not found');
      vi.spyOn(procedureApi, 'getById').mockRejectedValue(error);

      const result = await procedureStore.getProcedure('proc-1');

      expect(result).toBeNull();
      const state = getState();
      expect(state.error).toBe('Not found');
      expect(state.selectedProcedure).toBeNull();
    });
  });

  describe('createProcedure', () => {
    it('creates procedure and adds to list', async () => {
      const newProc = mockProcedure({ id: 'new-proc' });
      vi.spyOn(procedureApi, 'create').mockResolvedValue(newProc);
      vi.spyOn(GovernmentEventBus, 'publish').mockImplementation(() => {});

      const request = mockCreateProcedureRequest();
      const result = await procedureStore.createProcedure(request);

      expect(result).toEqual(newProc);
      expect(procedureApi.create).toHaveBeenCalledWith(request);
      expect(GovernmentEventBus.publish).toHaveBeenCalledWith({
        type: 'government.procedure.created',
        payload: {
          procedure: newProc,
          timestamp: expect.any(String),
        },
      });
      const state = getState();
      expect(state.procedures).toContainEqual(newProc);
      expect(state.saving).toBe(false);
    });

    it('throws and sets error on failure', async () => {
      const error = new Error('Create failed');
      vi.spyOn(procedureApi, 'create').mockRejectedValue(error);

      const request = mockCreateProcedureRequest();
      await expect(procedureStore.createProcedure(request)).rejects.toThrow('Create failed');

      const state = getState();
      expect(state.error).toBe('Create failed');
      expect(state.saving).toBe(false);
      expect(state.procedures).toEqual([]);
    });
  });

  describe('loadActiveProcedures', () => {
    it('loads active procedures and replaces list', async () => {
      const active = [mockProcedure({ isActive: true }), mockProcedure({ isActive: true })];
      vi.spyOn(procedureApi, 'getActive').mockResolvedValue(active);

      await procedureStore.loadActiveProcedures();

      expect(procedureApi.getActive).toHaveBeenCalled();
      const state = getState();
      expect(state.procedures).toEqual(active);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      const error = new Error('Failed to load active');
      vi.spyOn(procedureApi, 'getActive').mockRejectedValue(error);

      await procedureStore.loadActiveProcedures();

      const state = getState();
      expect(state.error).toBe('Failed to load active');
      expect(state.loading).toBe(false);
    });
  });

  describe('loadProceduresByCategory', () => {
    it('loads procedures by category and replaces list', async () => {
      const category = 'permit' as ProcedureCategory;
      const procs = [mockProcedure({ category })];
      vi.spyOn(procedureApi, 'getByCategory').mockResolvedValue(procs);

      await procedureStore.loadProceduresByCategory(category);

      expect(procedureApi.getByCategory).toHaveBeenCalledWith(category);
      const state = getState();
      expect(state.procedures).toEqual(procs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      const error = new Error('Failed to load by category');
      vi.spyOn(procedureApi, 'getByCategory').mockRejectedValue(error);

      await procedureStore.loadProceduresByCategory('permit');

      const state = getState();
      expect(state.error).toBe('Failed to load by category');
      expect(state.loading).toBe(false);
    });
  });

  describe('setProcedureFilters', () => {
    it('sets filters and resets procedures', () => {
      const filters = { category: 'permit' };
      vi.spyOn(procedureApi, 'list').mockResolvedValue({ data: [], page: 1, total: 0, totalPages: 1 });

      procedureStore.setProcedureFilters(filters);
      // setFilters is synchronous; check immediate state
      const state = getState();
      expect(state.procedureFilters).toEqual(filters);
      expect(state.page).toBe(1);
      expect(state.hasMore).toBe(true);
    });
  });

  describe('loadApplications', () => {
    it('loads applications successfully (reset)', async () => {
      const apps = [mockApplication(), mockApplication()];
      const response = { data: apps, page: 1, total: 2, totalPages: 1 };
      vi.spyOn(applicationApi, 'list').mockResolvedValue(response);

      await procedureStore.loadApplications(true);

      expect(applicationApi.list).toHaveBeenCalledWith({}, 1, 20);
      const state = getState();
      expect(state.applications).toEqual(apps);
      expect(state.page).toBe(2);
      expect(state.hasMore).toBe(false);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('appends applications when not reset', async () => {
      const first = [mockApplication({ id: 'a1' })];
      const second = [mockApplication({ id: 'a2' })];
      vi.spyOn(applicationApi, 'list').mockResolvedValueOnce({ data: first, page: 1, total: 1, totalPages: 1 })
        .mockResolvedValueOnce({ data: second, page: 2, total: 1, totalPages: 1 });

      await procedureStore.loadApplications(true);
      await procedureStore.loadApplications(false);

      const state = getState();
      expect(state.applications).toHaveLength(2);
      expect(state.page).toBe(3);
    });

    it('handles error', async () => {
      const error = new Error('Failed to load applications');
      vi.spyOn(applicationApi, 'list').mockRejectedValue(error);

      await procedureStore.loadApplications();

      const state = getState();
      expect(state.error).toBe('Failed to load applications');
      expect(state.loading).toBe(false);
      expect(state.applications).toEqual([]);
    });
  });

  describe('createApplication', () => {
    it('creates application and adds to list', async () => {
      const newApp = mockApplication({ id: 'new-app' });
      vi.spyOn(applicationApi, 'create').mockResolvedValue(newApp);
      vi.spyOn(GovernmentEventBus, 'publish').mockImplementation(() => {});

      const request = mockCreateApplicationRequest();
      const result = await procedureStore.createApplication(request);

      expect(result).toEqual(newApp);
      expect(applicationApi.create).toHaveBeenCalledWith(request);
      expect(GovernmentEventBus.publish).toHaveBeenCalledWith({
        type: 'government.application.submitted',
        payload: {
          application: newApp,
          timestamp: expect.any(String),
        },
      });
      const state = getState();
      expect(state.applications).toContainEqual(newApp);
      expect(state.saving).toBe(false);
    });

    it('throws and sets error on failure', async () => {
      const error = new Error('Create failed');
      vi.spyOn(applicationApi, 'create').mockRejectedValue(error);

      const request = mockCreateApplicationRequest();
      await expect(procedureStore.createApplication(request)).rejects.toThrow('Create failed');

      const state = getState();
      expect(state.error).toBe('Create failed');
      expect(state.saving).toBe(false);
    });
  });

  describe('updateApplicationStatus', () => {
    it('updates status and publishes completed event', async () => {
      const app = mockApplication({ id: 'app-1', status: 'processing' });
      const updated = { ...app, status: 'completed' as const };
      vi.spyOn(applicationApi, 'updateStatus').mockResolvedValue(updated);
      vi.spyOn(GovernmentEventBus, 'publish').mockImplementation(() => {});

      vi.spyOn(applicationApi, 'list').mockResolvedValue({ data: [app], page: 1, total: 1, totalPages: 1 });
      await procedureStore.loadApplications();

      const result = await procedureStore.updateApplicationStatus('app-1', 'completed', 'All good');

      expect(result).toEqual(updated);
      expect(applicationApi.updateStatus).toHaveBeenCalledWith('app-1', 'completed', 'All good');
      expect(GovernmentEventBus.publish).toHaveBeenCalledWith({
        type: 'government.application.completed',
        payload: {
          application: updated,
          result: 'All good',
          completedAt: expect.any(String),
          timestamp: expect.any(String),
        },
      });
      const state = getState();
      expect(state.applications[0].status).toBe('completed');
      expect(state.saving).toBe(false);
    });

    it('publishes status_changed event for non-completed status', async () => {
      const app = mockApplication({ id: 'app-1', status: 'submitted' });
      const updated = { ...app, status: 'processing' as const };
      vi.spyOn(applicationApi, 'updateStatus').mockResolvedValue(updated);
      vi.spyOn(GovernmentEventBus, 'publish').mockImplementation(() => {});

      vi.spyOn(applicationApi, 'list').mockResolvedValue({ data: [app], page: 1, total: 1, totalPages: 1 });
      await procedureStore.loadApplications();

      await procedureStore.updateApplicationStatus('app-1', 'processing');

      expect(GovernmentEventBus.publish).toHaveBeenCalledWith({
        type: 'government.application.status_changed',
        payload: {
          applicationId: 'app-1',
          oldStatus: 'submitted',
          newStatus: 'processing',
          processedBy: updated.processedBy,
          notes: null,
          timestamp: expect.any(String),
        },
      });
    });

    it('handles error', async () => {
      const error = new Error('Update failed');
      vi.spyOn(applicationApi, 'updateStatus').mockRejectedValue(error);

      await expect(procedureStore.updateApplicationStatus('app-1', 'completed')).rejects.toThrow('Update failed');

      const state = getState();
      expect(state.error).toBe('Update failed');
      expect(state.saving).toBe(false);
    });
  });

  describe('loadPendingApplications', () => {
    it('loads pending applications and replaces list', async () => {
      const pending = [mockApplication({ status: 'submitted' }), mockApplication({ status: 'processing' })];
      vi.spyOn(applicationApi, 'getPending').mockResolvedValue(pending);

      await procedureStore.loadPendingApplications();

      expect(applicationApi.getPending).toHaveBeenCalled();
      const state = getState();
      expect(state.applications).toEqual(pending);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      const error = new Error('Failed to load pending');
      vi.spyOn(applicationApi, 'getPending').mockRejectedValue(error);

      await procedureStore.loadPendingApplications();

      const state = getState();
      expect(state.error).toBe('Failed to load pending');
      expect(state.loading).toBe(false);
    });
  });

  describe('loadApplicationsByCitizen', () => {
    it('loads applications for citizen', async () => {
      const apps = [mockApplication({ citizenId: 'cit-1' })];
      vi.spyOn(applicationApi, 'getByCitizen').mockResolvedValue(apps);

      await procedureStore.loadApplicationsByCitizen('cit-1');

      expect(applicationApi.getByCitizen).toHaveBeenCalledWith('cit-1');
      const state = getState();
      expect(state.applications).toEqual(apps);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to load citizen apps');
      vi.spyOn(applicationApi, 'getByCitizen').mockRejectedValue(error);

      await procedureStore.loadApplicationsByCitizen('cit-1');

      const state = getState();
      expect(state.error).toBe('Failed to load citizen apps');
      expect(state.loading).toBe(false);
    });
  });

  describe('setApplicationFilters', () => {
    it('sets filters and resets applications', () => {
      const filters = { status: 'submitted' };
      vi.spyOn(applicationApi, 'list').mockResolvedValue({ data: [], page: 1, total: 0, totalPages: 1 });

      procedureStore.setApplicationFilters(filters);
      const state = getState();
      expect(state.applicationFilters).toEqual(filters);
      expect(state.page).toBe(1);
      expect(state.hasMore).toBe(true);
    });
  });

  describe('selection actions', () => {
    it('selectProcedure', () => {
      const proc = mockProcedure();
      procedureStore.selectProcedure(proc);
      expect(getState().selectedProcedure).toEqual(proc);
    });

    it('selectApplication', () => {
      const app = mockApplication();
      procedureStore.selectApplication(app);
      expect(getState().selectedApplication).toEqual(app);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      const err = new Error('test');
      vi.spyOn(procedureApi, 'list').mockRejectedValue(err);
      await procedureStore.loadProcedures();

      expect(getState().error).toBe('test');

      procedureStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      const proc = mockProcedure();
      vi.spyOn(procedureApi, 'list').mockResolvedValue({ data: [proc], page: 1, total: 1, totalPages: 1 });
      await procedureStore.loadProcedures(true);
      procedureStore.selectProcedure(proc);
      expect(getState().procedures).toContainEqual(proc);
      expect(getState().selectedProcedure).toEqual(proc);

      procedureStore.reset();
      const state = getState();
      expect(state.procedures).toEqual([]);
      expect(state.applications).toEqual([]);
      expect(state.selectedProcedure).toBeNull();
      expect(state.selectedApplication).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.saving).toBe(false);
      expect(state.error).toBeNull();
      expect(state.procedureFilters).toEqual({});
      expect(state.applicationFilters).toEqual({});
      expect(state.page).toBe(1);
      expect(state.perPage).toBe(20);
      expect(state.hasMore).toBe(true);
    });
  });
});
