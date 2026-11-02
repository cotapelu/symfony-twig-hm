import { describe, it, expect, vi, beforeEach } from 'vitest';
import { pythonAnalyticsStore } from './python-all';
import { Python } from '../lib/api';

vi.mock('../lib/api', () => ({
  Python: {
    getUserAnalytics: vi.fn(),
    getConversationAnalytics: vi.fn(),
    getEngagementMetrics: vi.fn(),
    getRetentionData: vi.fn(),
    getChurnPrediction: vi.fn(),
    getMetrics: vi.fn(),
  }
}));

describe('PythonAnalyticsStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    pythonAnalyticsStore.reset();
    pythonAnalyticsStore.subscribe(s => { state = s; });
  });

  describe('getUserAnalytics', () => {
    it('should fetch user analytics', async () => {
      const analytics = { userId: 'u1', activity: 10 };
      (Python.getUserAnalytics as any).mockResolvedValue(analytics);

      const result = await pythonAnalyticsStore.getUserAnalytics('u1');

      expect(result).toEqual(analytics);
      expect(state.userAnalytics).toEqual(analytics);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.getUserAnalytics as any).mockRejectedValue(new Error('Get user failed'));
      await expect(pythonAnalyticsStore.getUserAnalytics('u1')).rejects.toThrow('Get user failed');
      expect(state.error).toBe('Get user failed');
    });
  });

  describe('getConversationAnalytics', () => {
    it('should fetch conversation analytics by conversationId', async () => {
      const convAnalytics = { total: 5, bots: 2 };
      (Python.getConversationAnalytics as any).mockResolvedValue(convAnalytics);
      const conversationId = 'conv123';

      const result = await pythonAnalyticsStore.getConversationAnalytics(conversationId);

      expect(result).toEqual(convAnalytics);
      expect(state.conversationAnalytics[conversationId]).toEqual(convAnalytics);
    });

    it('should handle error', async () => {
      (Python.getConversationAnalytics as any).mockRejectedValue(new Error('Conv failed'));
      await expect(pythonAnalyticsStore.getConversationAnalytics({})).rejects.toThrow('Conv failed');
      expect(state.error).toBe('Conv failed');
    });
  });

  describe('getEngagementMetrics', () => {
    it('should fetch engagement metrics', async () => {
      const metrics = { clicks: 100, views: 1000 };
      (Python.getEngagementMetrics as any).mockResolvedValue(metrics);

      const result = await pythonAnalyticsStore.getEngagementMetrics('user1');

      expect(result).toEqual(metrics);
      expect(state.engagementMetrics).toEqual(metrics);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.getEngagementMetrics as any).mockRejectedValue(new Error('Engage failed'));
      await expect(pythonAnalyticsStore.getEngagementMetrics()).rejects.toThrow('Engage failed');
      expect(state.error).toBe('Engage failed');
    });
  });

  describe('getRetentionData', () => {
    it('should fetch retention data', async () => {
      const retention = { day1: 90, day7: 60 };
      (Python.getRetentionData as any).mockResolvedValue(retention);

      const result = await pythonAnalyticsStore.getRetentionData('2024-01-01', '2024-01-31');

      expect(result).toEqual(retention);
      expect(state.retentionData).toEqual(retention);
    });

    it('should handle error', async () => {
      (Python.getRetentionData as any).mockRejectedValue(new Error('Retention failed'));
      await expect(pythonAnalyticsStore.getRetentionData('2024-01-01', '2024-01-31')).rejects.toThrow('Retention failed');
      expect(state.error).toBe('Retention failed');
    });
  });

  describe('getChurnPrediction', () => {
    it('should fetch churn prediction', async () => {
      const prediction = { churnRisk: 0.2 };
      (Python.getChurnPrediction as any).mockResolvedValue(prediction);

      const result = await pythonAnalyticsStore.getChurnPrediction('u1');

      expect(result).toEqual(prediction);
      expect(state.churnPrediction).toEqual(prediction);
    });

    it('should handle error', async () => {
      (Python.getChurnPrediction as any).mockRejectedValue(new Error('Churn failed'));
      await expect(pythonAnalyticsStore.getChurnPrediction('u1')).rejects.toThrow('Churn failed');
      expect(state.error).toBe('Churn failed');
    });
  });

  describe('getMetrics', () => {
    it('should fetch metrics and set userAnalytics', async () => {
      const metrics = { totalConversations: 5, totalMessages: 100 };
      (Python.getMetrics as any).mockResolvedValue(metrics);

      const result = await pythonAnalyticsStore.getMetrics();

      expect(result).toEqual(metrics);
      expect(state.userAnalytics).toEqual(metrics);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.getMetrics as any).mockRejectedValue(new Error('Metrics failed'));
      await expect(pythonAnalyticsStore.getMetrics()).rejects.toThrow('Metrics failed');
      expect(state.error).toBe('Metrics failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.userAnalytics = { active: true };
      state.conversationAnalytics = { convs: 2 };
      state.engagementMetrics = { rate: 0.5 };
      state.retentionData = { day1: 80 };
      state.churnPrediction = { risk: 0.1 };
      state.totalConversations = 10;
      state.totalMessages = 50;
      state.error = 'err';
      pythonAnalyticsStore.reset();
      expect(state.userAnalytics).toBeNull();
      expect(state.conversationAnalytics).toEqual({});
      expect(state.engagementMetrics).toBeNull();
      expect(state.retentionData).toBeNull();
      expect(state.churnPrediction).toBeNull();
      expect(state.totalConversations).toBe(0);
      expect(state.totalMessages).toBe(0);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Python.getUserAnalytics as any).mockRejectedValue(new Error('test'));
      try { await pythonAnalyticsStore.getUserAnalytics('u1'); } catch (e) {}
      expect(state.error).toBe('test');
      pythonAnalyticsStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
