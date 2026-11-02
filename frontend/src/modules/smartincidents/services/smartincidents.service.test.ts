import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => { store[key] = value; },
    removeItem: (key: string) => { delete store[key]; },
    clear: () => { store = {}; }
  };
})();
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// Mock global fetch
global.fetch = vi.fn();

// Import after mocks
import { smartIncidentApi } from './smartincidents.service';

describe('SmartIncidentService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.clear();
    localStorageMock.setItem('auth_token', 'test-token');
  });

  const mockFetchResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  const apiUrl = 'http://localhost:8080';

  describe('create', () => {
    it('should create incident without files', async () => {
      const incident = { id: 1, title: 'Test' };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: incident }));

      const result = await smartIncidentApi.create({ title: 'Test', description: 'Desc', severity: 'low' });

      expect(result).toEqual(incident);
      expect(global.fetch).toHaveBeenCalledWith(
        `${apiUrl}/api/smart-incidents`,
        expect.objectContaining({
          method: 'POST',
          headers: expect.objectContaining({ 'Content-Type': 'application/json' })
        })
      );
    });

    it('should upload files after creating incident', async () => {
      const incident = { id: 2 };
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: incident })); // create
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: { id: 'a1' } })); // upload

      const result = await smartIncidentApi.create({ title: 'T', description: 'D', severity: 'medium' }, [file]);

      expect(result).toEqual(incident);
      expect(global.fetch).toHaveBeenCalledTimes(2);
      // Upload call
      const uploadCall = (global.fetch as any).mock.calls[1];
      expect(uploadCall[0]).toContain('/api/smart-incidents/upload');
      expect(uploadCall[1].body instanceof FormData).toBe(true);
      const formData = uploadCall[1].body as FormData;
      expect(formData.get('file')).toBe(file);
      expect(formData.get('incidentId')).toBe('2');
    });
  });

  describe('getById', () => {
    it('should fetch incident by id', async () => {
      const incident = { id: 1, title: 'Inc' };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: incident }));

      const result = await smartIncidentApi.getById(1);

      expect(result).toEqual(incident);
      expect(global.fetch).toHaveBeenCalledWith(
        `${apiUrl}/api/smart-incidents/1`,
        expect.any(Object)
      );
    });
  });

  describe('list', () => {
    it('should list incidents with pagination and filters', async () => {
      const response = { data: [{ id: 1 }], total: 1, page: 1, perPage: 20 };
      global.fetch.mockResolvedValueOnce(mockFetchResponse(response));

      const result = await smartIncidentApi.list({ status: 'open', severity: 'high' }, 2, 50);

      expect(result).toEqual(response);
      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('page=2');
      expect(url).toContain('perPage=50');
      expect(url).toContain('status=open');
      expect(url).toContain('severity=high');
    });
  });

  describe('update', () => {
    it('should update incident', async () => {
      const updated = { id: 1, title: 'Updated' };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: updated }));

      const result = await smartIncidentApi.update(1, { title: 'Updated' });

      expect(result).toEqual(updated);
      expect(global.fetch).toHaveBeenCalledWith(
        `${apiUrl}/api/smart-incidents/1`,
        expect.objectContaining({ method: 'PUT' })
      );
    });
  });

  describe('updateStatus', () => {
    it('should update incident status', async () => {
      const updated = { id: 1, status: 'resolved' };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: updated }));

      const result = await smartIncidentApi.updateStatus(1, 'resolved', 'Fixed');

      expect(result).toEqual(updated);
      const [url, options] = (global.fetch as any).mock.calls[0];
      expect(url).toBe(`${apiUrl}/api/smart-incidents/1/status`);
      expect(options.method).toBe('POST');
      const body = JSON.parse(options.body);
      expect(body.status).toBe('resolved');
      expect(body.notes).toBe('Fixed');
    });
  });

  describe('assign', () => {
    it('should assign officer to incident', async () => {
      const res = { assigned: true };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: res }));

      await smartIncidentApi.assign(1, 'officer123', 'teamA');

      expect(global.fetch).toHaveBeenCalledWith(
        `${apiUrl}/api/smart-incidents/1/assign`,
        expect.objectContaining({
          method: 'POST',
          body: expect.stringContaining('"officerId":"officer123"')
        })
      );
    });
  });

  describe('resolve', () => {
    it('should resolve incident', async () => {
      const res = { resolved: true };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: res }));

      await smartIncidentApi.resolve(1, 'Fixed after investigation');

      expect(global.fetch).toHaveBeenCalledWith(
        `${apiUrl}/api/smart-incidents/1/resolve`,
        expect.objectContaining({
          method: 'POST',
          body: expect.stringContaining('"summary":"Fixed after investigation"')
        })
      );
    });
  });

  describe('getStatistics', () => {
    it('should fetch statistics', async () => {
      const stats = { total: 10, bySeverity: { high: 3 } };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: stats }));

      const result = await smartIncidentApi.getStatistics({ wardCode: '01' });

      expect(result).toEqual(stats);
      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('/api/smart-incidents/statistics');
      expect(url).toContain('wardCode=01');
    });
  });

  describe('getActivityLog', () => {
    it('should fetch activity logs', async () => {
      const activities = [{ id: 'a1', action: 'created' }];
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: activities }));

      const result = await smartIncidentApi.getActivityLog(1);

      expect(result).toEqual(activities);
      expect(global.fetch).toHaveBeenCalledWith(
        `${apiUrl}/api/smart-incidents/1/activity`,
        expect.any(Object)
      );
    });
  });

  describe('getMapMarkers', () => {
    it('should fetch map markers within bounds and filters', async () => {
      const markers = [{ latitude: 10, longitude: 20 }];
      const bounds = { north: 10.1, south: 9.9, east: 20.1, west: 19.9 };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: markers }));

      const result = await smartIncidentApi.getMapMarkers(bounds, { severity: 'high' });

      expect(result).toEqual(markers);
      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('north=10.1');
      expect(url).toContain('south=9.9');
      expect(url).toContain('east=20.1');
      expect(url).toContain('west=19.9');
      expect(url).toContain('severity=high');
    });
  });

  describe('getHeatmapData', () => {
    it('should fetch heatmap data with default timeframe', async () => {
      const data = { points: [[10, 20, 5]] };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data }));

      const result = await smartIncidentApi.getHeatmapData();

      expect(result).toEqual(data);
      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('/api/smart-incidents/map/heatmap');
      expect(url).toContain('timeframe=30d');
    });

    it('should fetch heatmap data with custom bounds and timeframe', async () => {
      const data = { points: [[10, 20, 5]] };
      const bounds = { north: 10.1, south: 9.9, east: 20.1, west: 19.9 };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data }));

      await smartIncidentApi.getHeatmapData(bounds, '7d');

      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('timeframe=7d');
      expect(url).toContain('north=10.1');
    });
  });

  describe('getMessages', () => {
    it('should fetch messages for incident', async () => {
      const messages = [{ id: 'm1', text: 'Hi' }];
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: messages }));

      const result = await smartIncidentApi.getMessages(1);

      expect(result).toEqual(messages);
      expect(global.fetch).toHaveBeenCalledWith(
        `${apiUrl}/api/smart-incidents/1/messages`,
        expect.any(Object)
      );
    });
  });

  describe('sendMessage', () => {
    it('should send message to incident', async () => {
      const msg = { id: 'm1', content: 'Hello' };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: msg }));

      const result = await smartIncidentApi.sendMessage(1, 'Hello');

      expect(result).toEqual(msg);
      const [url, options] = (global.fetch as any).mock.calls[0];
      expect(url).toBe(`${apiUrl}/api/smart-incidents/1/messages`);
      expect(options.method).toBe('POST');
      const body = JSON.parse(options.body);
      expect(body.content).toBe('Hello');
      expect(body.attachmentUrl).toBeUndefined();
    });
  });

  describe('addAttachment', () => {
    it('should upload file to incident', async () => {
      const file = new File(['data'], 'doc.pdf', { type: 'application/pdf' });
      const attachment = { id: 'a1', name: 'doc.pdf' };
      global.fetch.mockResolvedValueOnce(mockFetchResponse({ data: attachment }));

      const result = await smartIncidentApi.addAttachment(1, file);

      expect(result).toEqual(attachment);
      const [url, options] = (global.fetch as any).mock.calls[0];
      expect(url).toBe(`${apiUrl}/api/smart-incidents/1/attachments`);
      expect(options.method).toBe('POST');
      expect(options.body instanceof FormData).toBe(true);
      // headers should contain Authorization because token is set
      expect(options.headers).toEqual({ Authorization: 'Bearer test-token' });
      // FormData should contain file and incidentId
      const formData = options.body as FormData;
      expect(formData.get('file')).toBe(file);
      expect(formData.get('incidentId')).toBe('1');
    });
  });

  describe('deleteAttachment', () => {
    it('should delete attachment', async () => {
      global.fetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });

      await smartIncidentApi.deleteAttachment(1, 101);

      expect(global.fetch).toHaveBeenCalledWith(
        `${apiUrl}/api/smart-incidents/1/attachments/101`,
        expect.objectContaining({ method: 'DELETE' })
      );
    });
  });
});
