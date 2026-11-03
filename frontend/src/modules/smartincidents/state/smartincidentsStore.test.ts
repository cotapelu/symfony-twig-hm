import { describe, it, expect, vi, beforeEach } from 'vitest';
import { smartIncidentStore } from './smartincidentsStore';
import { smartIncidentApi } from '../services/smartincidents.service';
import { SmartIncidentEventBus, SmartIncidentEvents } from '../events/smartincidents.events';
import type {
  SmartIncident,
  CreateIncidentRequest,
  UpdateIncidentRequest,
  IncidentFilters,
  IncidentStatistics,
  IncidentActivity,
  MapIncidentMarker,
  MapBounds,
  IncidentHeatmapData,
  IncidentAttachment
} from '../contracts/smartincidents.types';

// Helper
function getState() {
  let current: any;
  const unsub = smartIncidentStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock factories
const mockIncident = (overrides: Partial<SmartIncident> = {}): SmartIncident => ({
  id: Math.floor(Math.random() * 10000),
  title: 'Test Incident',
  description: 'A test incident',
  status: 'open',
  priority: 'medium',
  location: { lat: 0, lng: 0, address: 'Test St' },
  category: 'infrastructure',
  createdBy: 'user-1',
  assignedTo: null,
  attachments: [],
  messages: [],
  activity: [],
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  ...overrides
});

const mockCreateRequest = (overrides: Partial<CreateIncidentRequest> = {}): CreateIncidentRequest => ({
  title: 'New Incident',
  description: 'New desc',
  category: 'infrastructure',
  ...overrides
});

const mockUpdateRequest = (overrides: Partial<UpdateIncidentRequest> = {}): UpdateIncidentRequest => ({
  title: 'Updated Title',
  description: 'Updated desc',
  priority: 'high',
  ...overrides
});

const mockFilters = (overrides: Partial<IncidentFilters> = {}): IncidentFilters => ({
  status: 'open',
  category: 'infrastructure',
  ...overrides
});

const mockStatistics = (overrides: Partial<IncidentStatistics> = {}): IncidentStatistics => ({
  totalIncidents: 100,
  openIncidents: 30,
  resolvedIncidents: 70,
  avgResolutionTime: 3600,
  ...overrides
});

const mockActivity = (overrides: Partial<IncidentActivity> = {}): IncidentActivity => ({
  id: `act-${Math.random()}`,
  incidentId: 1,
  userId: 'user-1',
  action: 'status_change',
  details: 'Status changed',
  timestamp: new Date().toISOString(),
  ...overrides
});

const mockMarker = (overrides: Partial<MapIncidentMarker> = {}): MapIncidentMarker => ({
  id: 1,
  lat: 0,
  lng: 0,
  title: 'Incident',
  status: 'open',
  ...overrides
});

const mockHeatmap = (overrides: Partial<IncidentHeatmapData> = {}): IncidentHeatmapData => ({
  points: [{ lat: 0, lng: 0, intensity: 0.5 }],
  bounds: { north: 1, south: -1, east: 1, west: -1 },
  ...overrides
});

const mockAttachment = (overrides: Partial<IncidentAttachment> = {}): IncidentAttachment => ({
  id: `att-${Math.random()}`,
  filename: 'file.jpg',
  fileType: 'image/jpeg',
  size: 1024,
  url: 'http://example.com/file.jpg',
  uploadedAt: new Date().toISOString(),
  ...overrides
});

describe('SmartIncidentStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    smartIncidentStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const s = getState();
      expect(s.incidents).toEqual([]);
      expect(s.selectedIncident).toBeNull();
      expect(s.statistics).toBeNull();
      expect(s.activityLog).toEqual([]);
      expect(s.mapMarkers).toEqual([]);
      expect(s.heatmapData).toBeNull();
      expect(s.loading).toBe(false);
      expect(s.saving).toBe(false);
      expect(s.uploading).toBe(false);
      expect(s.transcribing).toBe(false);
      expect(s.error).toBeNull();
      expect(s.filters).toEqual({});
      expect(s.page).toBe(1);
      expect(s.perPage).toBe(20);
      expect(s.hasMore).toBe(true);
      expect(s.unreadCount).toBe(0);
      expect(s.isOnline).toBe(true);
    });
  });

  describe('loadIncidents', () => {
    it('loads incidents on reset', async () => {
      const incidents = [mockIncident(), mockIncident()];
      vi.spyOn(smartIncidentApi, 'list').mockResolvedValue({ data: incidents, hasMore: false });

      await smartIncidentStore.loadIncidents(true);

      expect(smartIncidentApi.list).toHaveBeenCalledWith({}, 1, 20);
      const s = getState();
      expect(s.incidents).toEqual(incidents);
      expect(s.page).toBe(2);
      expect(s.hasMore).toBe(false);
      expect(s.loading).toBe(false);
      expect(s.error).toBeNull();
    });

    it('appends incidents when not reset', async () => {
      const first = [mockIncident({ id: 1 })];
      const second = [mockIncident({ id: 2 })];
      vi.spyOn(smartIncidentApi, 'list')
        .mockResolvedValueOnce({ data: first, hasMore: true })
        .mockResolvedValueOnce({ data: second, hasMore: false });

      await smartIncidentStore.loadIncidents(true);
      expect(getState().incidents).toHaveLength(1);

      await smartIncidentStore.loadIncidents(false);
      expect(getState().incidents).toHaveLength(2);
    });

    it('sets loading and handles error', async () => {
      const err = new Error('List failed');
      vi.spyOn(smartIncidentApi, 'list').mockRejectedValue(err);

      const p = smartIncidentStore.loadIncidents();
      // loading is set synchronously
      expect(getState().loading).toBe(true);

      await p;
      const s = getState();
      expect(s.loading).toBe(false);
      expect(s.error).toBe('List failed');
    });
  });

  describe('searchIncidents', () => {
    it('searches and replaces incidents', async () => {
      const results = [mockIncident({ id: 10 })];
      vi.spyOn(smartIncidentApi, 'search').mockResolvedValue(results);

      await smartIncidentStore.searchIncidents('query');

      expect(smartIncidentApi.search).toHaveBeenCalledWith('query');
      const s = getState();
      expect(s.incidents).toEqual(results);
      expect(s.hasMore).toBe(false);
    });

    it('calls loadIncidents when query empty', async () => {
      const listSpy = vi.spyOn(smartIncidentApi, 'list').mockResolvedValue({ data: [], hasMore: false });

      await smartIncidentStore.searchIncidents('   ');

      expect(listSpy).toHaveBeenCalled();
    });

    it('publishes event on search', async () => {
      const results = [mockIncident()];
      vi.spyOn(smartIncidentApi, 'search').mockResolvedValue(results);
      const publishSpy = vi.spyOn(SmartIncidentEventBus, 'publish').mockImplementation(() => {});

      await smartIncidentStore.searchIncidents('query');

      // Events carry a runtime timestamp (new Date()) — compare the deterministic
      // fields instead of exact object equality (two captures differ by ms).
      expect(publishSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'smartincident.updated',
          payload: expect.objectContaining({ incidentId: 0, changes: { searchResults: 1 } }),
        })
      );
    });
  });

  describe('getIncident', () => {
    it('gets incident and sets selected', async () => {
      const inc = mockIncident();
      vi.spyOn(smartIncidentApi, 'getById').mockResolvedValue(inc);

      const result = await smartIncidentStore.getIncident(1);

      expect(result).toEqual(inc);
      expect(getState().selectedIncident).toEqual(inc);
    });

    it('returns null on error and sets error', async () => {
      const err = new Error('Not found');
      vi.spyOn(smartIncidentApi, 'getById').mockRejectedValue(err);

      const result = await smartIncidentStore.getIncident(1);

      expect(result).toBeNull();
      expect(getState().error).toBe('Not found');
    });
  });

  describe('createIncident', () => {
    it('creates incident and adds to list', async () => {
      const newInc = mockIncident({ id: 100 });
      vi.spyOn(smartIncidentApi, 'create').mockResolvedValue(newInc);
      vi.spyOn(SmartIncidentEventBus, 'publish').mockImplementation(() => {});

      const request = mockCreateRequest();
      const result = await smartIncidentStore.createIncident(request);

      expect(result).toEqual(newInc);
      expect(getState().incidents[0]).toEqual(newInc);
      expect(getState().selectedIncident).toEqual(newInc);
      expect(getState().saving).toBe(false);
    });

    it('throws on failure', async () => {
      const err = new Error('Create failed');
      vi.spyOn(smartIncidentApi, 'create').mockRejectedValue(err);

      await expect(smartIncidentStore.createIncident({} as any)).rejects.toThrow('Create failed');

      expect(getState().error).toBe('Create failed');
    });
  });

  describe('updateIncident', () => {
    it('updates incident in list and selection', async () => {
      const existing = mockIncident({ id: 1 });
      vi.spyOn(smartIncidentApi, 'list').mockResolvedValue({ data: [existing], hasMore: false });
      await smartIncidentStore.loadIncidents(true);
      smartIncidentStore.selectIncident(existing);

      const updated = { ...existing, title: 'Updated' };
      vi.spyOn(smartIncidentApi, 'update').mockResolvedValue(updated);
      vi.spyOn(SmartIncidentEventBus, 'publish').mockImplementation(() => {});

      const result = await smartIncidentStore.updateIncident(1, mockUpdateRequest());

      expect(result).toEqual(updated);
      expect(getState().incidents[0]).toEqual(updated);
      expect(getState().selectedIncident).toEqual(updated);
    });

    it('throws on error', async () => {
      const existing = mockIncident({ id: 1 });
      vi.spyOn(smartIncidentApi, 'list').mockResolvedValue({ data: [existing], hasMore: false });
      await smartIncidentStore.loadIncidents(true);

      const err = new Error('Update failed');
      vi.spyOn(smartIncidentApi, 'update').mockRejectedValue(err);

      await expect(smartIncidentStore.updateIncident(1, mockUpdateRequest())).rejects.toThrow('Update failed');
      expect(getState().error).toBe('Update failed');
    });
  });

  describe('status & assignment actions', () => {
    const incident = mockIncident({ id: 1, status: 'open' });

    beforeEach(async () => {
      vi.spyOn(smartIncidentApi, 'list').mockResolvedValue({ data: [incident], hasMore: false });
      await smartIncidentStore.loadIncidents(true);
    });

    it('updateStatus updates and publishes', async () => {
      const updated = { ...incident, status: 'resolved' };
      vi.spyOn(smartIncidentApi, 'updateStatus').mockResolvedValue(updated);
      vi.spyOn(SmartIncidentEventBus, 'publish').mockImplementation(() => {});

      const result = await smartIncidentStore.updateStatus(1, 'resolved');

      expect(result).toEqual(updated);
      expect(SmartIncidentEventBus.publish).toHaveBeenCalledWith(
        SmartIncidentEvents.statusChanged(1, 'open', 'resolved', 'current_user')
      );
    });

    it('assignIncident updates', async () => {
      const assigned = { ...incident, assignedTo: 'officer-1' };
      vi.spyOn(smartIncidentApi, 'assign').mockResolvedValue(assigned);
      vi.spyOn(SmartIncidentEventBus, 'publish').mockImplementation(() => {});

      const result = await smartIncidentStore.assignIncident(1, 'officer-1', 'team-1');

      expect(result).toEqual(assigned);
    });

    it('resolveIncident updates and publishes', async () => {
      const resolved = { ...incident, status: 'resolved' };
      vi.spyOn(smartIncidentApi, 'resolve').mockResolvedValue(resolved);
      vi.spyOn(SmartIncidentEventBus, 'publish').mockImplementation(() => {});

      const result = await smartIncidentStore.resolveIncident(1, 'summary');

      expect(result).toEqual(resolved);
      expect(SmartIncidentEventBus.publish).toHaveBeenCalled();
    });

    it('escalateIncident updates', async () => {
      const escalated = { ...incident, priority: 'high' };
      vi.spyOn(smartIncidentApi, 'escalate').mockResolvedValue(escalated);
      vi.spyOn(SmartIncidentEventBus, 'publish').mockImplementation(() => {});

      const result = await smartIncidentStore.escalateIncident(1, 'reason');

      expect(result).toEqual(escalated);
    });

    it('closeIncident updates', async () => {
      const closed = { ...incident, status: 'closed' };
      vi.spyOn(smartIncidentApi, 'close').mockResolvedValue(closed);
      vi.spyOn(SmartIncidentEventBus, 'publish').mockImplementation(() => {});

      const result = await smartIncidentStore.closeIncident(1);

      expect(result).toEqual(closed);
    });
  });

  describe('attachments & transcription', () => {
    const incident = mockIncident({ id: 1, attachments: [], messages: [] as any });

    beforeEach(async () => {
      vi.spyOn(smartIncidentApi, 'list').mockResolvedValue({ data: [incident], hasMore: false });
      await smartIncidentStore.loadIncidents(true);
      smartIncidentStore.selectIncident(incident);
    });

    it('addAttachment adds to selected incident', async () => {
      const att = mockAttachment();
      vi.spyOn(smartIncidentApi, 'addAttachment').mockResolvedValue(att);
      vi.spyOn(SmartIncidentEventBus, 'publish').mockImplementation(() => {});

      const result = await smartIncidentStore.addAttachment(1, new File([''], 'test.jpg'));

      expect(result).toEqual(att);
      expect(getState().selectedIncident?.attachments).toContainEqual(att);
    });

    it('transcribeAudio returns transcription result', async () => {
      const result = { text: 'Transcript', confidence: 0.9 };
      vi.spyOn(smartIncidentApi, 'transcribeAudio').mockResolvedValue(result);

      const res = await smartIncidentStore.transcribeAudio(new File([''], 'audio.wav'));

      expect(res).toEqual(result);
      expect(getState().transcribing).toBe(false);
    });
  });

  describe('statistics, activity, map, heatmap', () => {
    it('loadStatistics', async () => {
      const stats = mockStatistics();
      vi.spyOn(smartIncidentApi, 'getStatistics').mockResolvedValue(stats);

      const result = await smartIncidentStore.loadStatistics();

      expect(result).toEqual(stats);
      expect(getState().statistics).toEqual(stats);
    });

    it('loadActivityLog', async () => {
      const acts = [mockActivity(), mockActivity()];
      vi.spyOn(smartIncidentApi, 'getActivityLog').mockResolvedValue(acts);

      const result = await smartIncidentStore.loadActivityLog(1);

      expect(result).toEqual(acts);
      expect(getState().activityLog).toEqual(acts);
    });

    it('loadMapMarkers', async () => {
      const markers = [mockMarker(), mockMarker()];
      vi.spyOn(smartIncidentApi, 'getMapMarkers').mockResolvedValue(markers);

      const result = await smartIncidentStore.loadMapMarkers();

      expect(result).toEqual(markers);
      expect(getState().mapMarkers).toEqual(markers);
    });

    it('loadHeatmapData', async () => {
      const heat = mockHeatmap();
      vi.spyOn(smartIncidentApi, 'getHeatmapData').mockResolvedValue(heat);

      const result = await smartIncidentStore.loadHeatmapData();

      expect(result).toEqual(heat);
      expect(getState().heatmapData).toEqual(heat);
    });
  });

  describe('selectIncident', () => {
    it('selects incident', () => {
      smartIncidentStore.selectIncident(mockIncident());
      expect(getState().selectedIncident).not.toBeNull();
    });

    it('clears selection when null', () => {
      smartIncidentStore.selectIncident(mockIncident());
      expect(getState().selectedIncident).not.toBeNull();
      smartIncidentStore.selectIncident(null);
      expect(getState().selectedIncident).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(smartIncidentApi, 'list').mockRejectedValue(new Error('err'));
      await smartIncidentStore.loadIncidents();
      expect(getState().error).toBe('err');

      smartIncidentStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('setFilters', () => {
    it('sets filters and resets incidents', () => {
      const filters = mockFilters({ status: 'closed' });
      vi.spyOn(smartIncidentApi, 'list').mockResolvedValue({ data: [], hasMore: false });

      smartIncidentStore.setFilters(filters);
      // setFilters is synchronous; loadIncidents is async but hasn't updated state yet
      const s = getState();
      expect(s.filters).toEqual(filters);
      expect(s.page).toBe(1);
      expect(s.hasMore).toBe(true);
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Load incidents
      vi.spyOn(smartIncidentApi, 'list').mockResolvedValue({ data: [mockIncident()], hasMore: false });
      await smartIncidentStore.loadIncidents(true);
      // Load statistics
      const stats = mockStatistics();
      vi.spyOn(smartIncidentApi, 'getStatistics').mockResolvedValue(stats);
      await smartIncidentStore.loadStatistics();
      // Set error by causing a failure (list again)
      const err = new Error('test err');
      vi.spyOn(smartIncidentApi, 'list').mockRejectedValue(err);
      await smartIncidentStore.loadIncidents().catch(() => {});
      expect(getState().error).toBe('test err');

      smartIncidentStore.reset();
      const s = getState();
      expect(s.incidents).toEqual([]);
      expect(s.selectedIncident).toBeNull();
      expect(s.statistics).toBeNull();
      expect(s.activityLog).toEqual([]);
      expect(s.mapMarkers).toEqual([]);
      expect(s.heatmapData).toBeNull();
      expect(s.loading).toBe(false);
      expect(s.saving).toBe(false);
      expect(s.uploading).toBe(false);
      expect(s.transcribing).toBe(false);
      expect(s.error).toBeNull();
      expect(s.filters).toEqual({});
      expect(s.page).toBe(1);
      expect(s.hasMore).toBe(true);
      expect(s.unreadCount).toBe(0);
      expect(s.isOnline).toBe(true);
    });
  });
});
