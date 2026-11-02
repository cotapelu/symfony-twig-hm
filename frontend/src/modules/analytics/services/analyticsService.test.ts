import { describe, it, expect, vi, beforeEach } from 'vitest';
import type { AnalyticsReport, AnalyticsMetric, AnalyticsDashboard, AnalyticsEvent, CreateReportRequest, CreateDashboardRequest, TrackEventRequest } from '../contracts/analyticsTypes';

global.fetch = vi.fn();

import { AnalyticsService } from './analyticsService';
import { __testing__ } from './analyticsService';

describe('AnalyticsService', () => {
  let service: AnalyticsService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new AnalyticsService();
    // Set default role for write operations where needed; individual tests can override
    window.localStorage.setItem('userRole', 'admin'); // admin has all permissions
  });

  describe('getReports', () => {
    it('should fetch reports with default pagination', async () => {
      const reports = { data: [{ id: 'r1', title: 'Report1', createdAt: 'now' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(reports) });

      const result = await service.getReports();

      expect(result).toEqual(reports);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/reports?page=1&limit=20');
    });

    it('should fetch reports with custom pagination', async () => {
      const reports = { data: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(reports) });

      const result = await service.getReports(2, 50);

      expect(result).toEqual(reports);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/reports?page=2&limit=50');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getReports()).rejects.toThrow('Failed to fetch reports');
    });
  });

  describe('getReport', () => {
    it('should fetch report by id', async () => {
      const report: AnalyticsReport = { id: 'r1', title: 'Report', createdAt: 'now' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(report) });

      const result = await service.getReport('r1');

      expect(result).toEqual(report);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/reports/r1');
    });

    it('should throw on invalid id', async () => {
      await expect(service.getReport('')).rejects.toThrow('Report ID is required');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getReport('r1')).rejects.toThrow('Failed to fetch report');
    });
  });

  describe('createReport', () => {
    const validRequest: CreateReportRequest = {
      title: 'New Report',
      description: 'Description',
      config: { metrics: ['visits'] }
    };

    it('should validate title', async () => {
      await expect(service.createReport({ ...validRequest, title: '' })).rejects.toThrow('Report title is required');
      await expect(service.createReport({ ...validRequest, title: 'A' })).rejects.toThrow('Report title must be between 3 and 200 characters');
      await expect(service.createReport({ ...validRequest, title: 'A'.repeat(201) })).rejects.toThrow('Report title must be between 3 and 200 characters');
    });

    it('should validate description length', async () => {
      await expect(service.createReport({ ...validRequest, description: 'B'.repeat(2001) })).rejects.toThrow('Report description must not exceed 2000 characters');
    });

    it('should create report successfully', async () => {
      const report: AnalyticsReport = { id: 'r1', title: 'New Report', createdAt: 'now' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(report) });

      const result = await service.createReport(validRequest);

      expect(result).toEqual(report);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/reports', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"title":"New Report"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createReport(validRequest)).rejects.toThrow('Failed to create report');
    });

    it('should require permission', async () => {
      window.localStorage.setItem('userRole', 'guest'); // guest cannot create
      await expect(service.createReport(validRequest)).rejects.toThrow('Permission denied: Cannot create reports');
    });
  });

  describe('deleteReport', () => {
    it('should delete report successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.deleteReport('r1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/reports/r1', expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on API failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.deleteReport('r1')).rejects.toThrow('Failed to delete report');
    });

    it('should require permission', async () => {
      window.localStorage.setItem('userRole', 'guest');
      await expect(service.deleteReport('r1')).rejects.toThrow('Permission denied: Cannot delete reports');
    });
  });

  describe('getMetrics', () => {
    it('should fetch all metrics when no names provided', async () => {
      const metrics: AnalyticsMetric[] = [{ name: 'visits', value: 100, timestamp: 'now' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(metrics) });

      const result = await service.getMetrics();

      expect(result).toEqual(metrics);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/metrics');
    });

    it('should fetch specific metrics by name', async () => {
      const metrics = [{ name: 'visits', value: 100, timestamp: 'now' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(metrics) });

      const result = await service.getMetrics(['visits', 'clicks']);

      expect(result).toEqual(metrics);
      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('/api/analytics/metrics?metrics=');
      expect(url).toContain('visits');
      expect(url).toContain('clicks');
    });

    it('should throw on too many metric names', async () => {
      const many = Array(51).fill('metric');
      await expect(service.getMetrics(many as string[])).rejects.toThrow('Cannot request more than 50 metrics at once');
    });

    it('should throw on invalid metric names type', async () => {
      // @ts-ignore - testing invalid input
      await expect(service.getMetrics('not an array')).rejects.toThrow('Metric names must be an array');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getMetrics()).rejects.toThrow('Failed to fetch metrics');
    });
  });

  describe('getDashboards', () => {
    it('should fetch dashboards with default pagination', async () => {
      const dashboards = { data: [{ id: 'd1', name: 'Dashboard1', config: {} }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(dashboards) });

      const result = await service.getDashboards();

      expect(result).toEqual(dashboards);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/dashboards?page=1&limit=20');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getDashboards()).rejects.toThrow('Failed to fetch dashboards');
    });
  });

  describe('getDashboard', () => {
    it('should fetch dashboard by id', async () => {
      const dashboard: AnalyticsDashboard = { id: 'd1', name: 'Dash', config: {}, createdAt: 'now' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(dashboard) });

      const result = await service.getDashboard('d1');

      expect(result).toEqual(dashboard);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/dashboards/d1');
    });

    it('should throw on invalid id', async () => {
      await expect(service.getDashboard('')).rejects.toThrow('Dashboard ID is required');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getDashboard('d1')).rejects.toThrow('Failed to fetch dashboard');
    });
  });

  describe('createDashboard', () => {
    const validRequest: CreateDashboardRequest = {
      name: 'New Dashboard',
      config: { widgets: [] }
    };

    it('should validate name', async () => {
      await expect(service.createDashboard({ ...validRequest, name: '' })).rejects.toThrow('Dashboard name is required');
      await expect(service.createDashboard({ ...validRequest, name: 'A' })).rejects.toThrow('Dashboard name must be between 3 and 100 characters');
    });

    it('should validate config type', async () => {
      await expect(service.createDashboard({ ...validRequest, config: 'invalid' as any })).rejects.toThrow('Dashboard config must be a valid object');
    });

    it('should create dashboard successfully', async () => {
      const dashboard: AnalyticsDashboard = { id: 'd1', name: 'New Dashboard', config: validRequest.config, createdAt: 'now' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(dashboard) });

      const result = await service.createDashboard(validRequest);

      expect(result).toEqual(dashboard);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/dashboards', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"name":"New Dashboard"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createDashboard(validRequest)).rejects.toThrow('Failed to create dashboard');
    });

    it('should require permission', async () => {
      window.localStorage.setItem('userRole', 'guest');
      await expect(service.createDashboard(validRequest)).rejects.toThrow('Permission denied: Cannot create dashboards');
    });
  });

  describe('updateDashboard', () => {
    const updates: CreateDashboardRequest = { name: 'Updated', config: { widgets: [] } };
    const updatedDashboard = { id: 'd1', name: 'Updated', config: updates.config, createdAt: 'now' };

    it('should update dashboard successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(updatedDashboard) });

      const result = await service.updateDashboard('d1', updates);

      expect(result).toEqual(updatedDashboard);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/dashboards/d1', expect.objectContaining({
        method: 'PUT',
        body: expect.stringContaining('"name":"Updated"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.updateDashboard('d1', updates)).rejects.toThrow('Failed to update dashboard');
    });

    it('should require permission', async () => {
      window.localStorage.setItem('userRole', 'guest');
      await expect(service.updateDashboard('d1', updates)).rejects.toThrow('Permission denied: Cannot update dashboards');
    });
  });

  describe('deleteDashboard', () => {
    it('should delete dashboard successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.deleteDashboard('d1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/dashboards/d1', expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on API failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.deleteDashboard('d1')).rejects.toThrow('Failed to delete dashboard');
    });

    it('should require permission', async () => {
      window.localStorage.setItem('userRole', 'guest');
      await expect(service.deleteDashboard('d1')).rejects.toThrow('Permission denied: Cannot delete dashboards');
    });
  });

  describe('trackEvent', () => {
    const validRequest: TrackEventRequest = {
      eventName: 'click',
      properties: { button: 'submit' },
      timestamp: '2024-01-01T00:00:00Z'
    };

    const trackedEvent: AnalyticsEvent = {
      id: 'e1',
      eventName: 'click',
      properties: { button: 'submit' },
      timestamp: '2024-01-01T00:00:00Z'
    };

    it('should validate event name', async () => {
      await expect(service.trackEvent({ ...validRequest, eventName: '' })).rejects.toThrow('Event name is required');
      await expect(service.trackEvent({ ...validRequest, eventName: 'A'.repeat(101) })).rejects.toThrow('Event name must not exceed 100 characters');
    });

    it('should sanitize sensitive properties', async () => {
      const request = {
        eventName: 'login',
        properties: { password: 'secret', token: 'abc', safe: 'value' },
        timestamp: '2024-01-01T00:00:00Z'
      };
      const sanitized = { id: 'e1', eventName: 'login', properties: { safe: 'value' }, timestamp: '2024-01-01T00:00:00Z' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(sanitized) });

      const result = await service.trackEvent(request);

      expect(result).toEqual(sanitized);
      // The body should have sanitized properties
      const callBody = JSON.parse((global.fetch as any).mock.calls[0][1].body);
      expect(callBody.properties).toEqual({ safe: 'value' });
    });

    it('should track event successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(trackedEvent) });

      const result = await service.trackEvent(validRequest);

      expect(result).toEqual(trackedEvent);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/events', expect.objectContaining({
        method: 'POST'
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.trackEvent(validRequest)).rejects.toThrow('Failed to track event');
    });
  });

  describe('getEvents', () => {
    it('should fetch recent events', async () => {
      const events: AnalyticsEvent[] = [
        { id: 'e1', eventName: 'click', properties: {}, timestamp: 'now' }
      ];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(events) });

      const result = await service.getEvents();

      expect(result).toEqual(events);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/events?limit=100');
    });

    it('should filter by event name', async () => {
      const events = [{ id: 'e1', eventName: 'click', properties: {}, timestamp: 'now' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(events) });

      const result = await service.getEvents('click');

      expect(result).toEqual(events);
      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/events?event=click&limit=100');
    });

    it('should respect custom limit', async () => {
      const events = [];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(events) });

      await service.getEvents(undefined, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/analytics/events?limit=50');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getEvents()).rejects.toThrow('Failed to fetch events');
    });
  });
});
