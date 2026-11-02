import { describe, it, expect, vi, beforeEach } from 'vitest';
import { goIntegrationStore } from './go-all';
import { Go } from '../lib/api';

vi.mock('../lib/api', () => ({
  Go: {
    getIntegrations: vi.fn(),
    connectIntegration: vi.fn(),
    disconnectIntegration: vi.fn(),
    createWebhook: vi.fn(),
    getWebhooks: vi.fn(),
    triggerWebhook: vi.fn(),
  }
}));

describe('GoIntegrationStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    goIntegrationStore.reset();
    goIntegrationStore.subscribe(s => { state = s; });
  });

  describe('getIntegrations', () => {
    it('should fetch integrations', async () => {
      const integrations = [{ id: 'i1' }];
      (Go.getIntegrations as any).mockResolvedValue(integrations);

      const result = await goIntegrationStore.getIntegrations();

      expect(result).toEqual(integrations);
      expect(state.integrations).toEqual(integrations);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.getIntegrations as any).mockRejectedValue(new Error('Get int failed'));
      await expect(goIntegrationStore.getIntegrations()).rejects.toThrow('Get int failed');
      expect(state.error).toBe('Get int failed');
    });
  });

  describe('connectIntegration', () => {
    it('should connect integration and add to list', async () => {
      const integration = { id: 'i2' };
      (Go.connectIntegration as any).mockResolvedValue(integration);

      const result = await goIntegrationStore.connectIntegration('slack');

      expect(result).toEqual(integration);
      expect(state.integrations).toContain(integration);
    });

    it('should handle error', async () => {
      (Go.connectIntegration as any).mockRejectedValue(new Error('Connect failed'));
      await expect(goIntegrationStore.connectIntegration('slack')).rejects.toThrow('Connect failed');
      expect(state.error).toBe('Connect failed');
    });
  });

  describe('disconnectIntegration', () => {
    it('should disconnect and remove integration from list', async () => {
      state.integrations = [{ id: 'i1' }, { id: 'i2' }];
      (Go.disconnectIntegration as any).mockResolvedValue(undefined);

      await goIntegrationStore.disconnectIntegration('i1');

      expect(state.integrations).toHaveLength(1);
      expect(state.integrations[0].id).toBe('i2');
    });

    it('should handle error', async () => {
      state.integrations = [{ id: 'i1' }];
      (Go.disconnectIntegration as any).mockRejectedValue(new Error('Disconnect failed'));
      await expect(goIntegrationStore.disconnectIntegration('i1')).rejects.toThrow('Disconnect failed');
      expect(state.error).toBe('Disconnect failed');
    });
  });

  describe('createWebhook', () => {
    it('should create webhook and add to list', async () => {
      const webhook = { id: 'w2' };
      (Go.createWebhook as any).mockResolvedValue(webhook);

      const result = await goIntegrationStore.createWebhook({ url: 'https://x', events: ['push'] });

      expect(result).toEqual(webhook);
      expect(state.webhooks).toContain(webhook);
    });

    it('should handle error', async () => {
      (Go.createWebhook as any).mockRejectedValue(new Error('Create webhook failed'));
      await expect(goIntegrationStore.createWebhook({})).rejects.toThrow('Create webhook failed');
      expect(state.error).toBe('Create webhook failed');
    });
  });

  describe('getWebhooks', () => {
    it('should fetch webhooks', async () => {
      const webhooks = [{ id: 'w1' }];
      (Go.getWebhooks as any).mockResolvedValue(webhooks);

      const result = await goIntegrationStore.getWebhooks();

      expect(result).toEqual(webhooks);
      expect(state.webhooks).toEqual(webhooks);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.getWebhooks as any).mockRejectedValue(new Error('Get webhook failed'));
      await expect(goIntegrationStore.getWebhooks()).rejects.toThrow('Get webhook failed');
      expect(state.error).toBe('Get webhook failed');
    });
  });

  describe('triggerWebhook', () => {
    it('should trigger webhook and return result', async () => {
      const result = { triggered: true };
      (Go.triggerWebhook as any).mockResolvedValue(result);

      const res = await goIntegrationStore.triggerWebhook('w1', { data: 1 });

      expect(res).toEqual(result);
    });

    it('should handle error', async () => {
      (Go.triggerWebhook as any).mockRejectedValue(new Error('Trigger failed'));
      await expect(goIntegrationStore.triggerWebhook('w1', {})).rejects.toThrow('Trigger failed');
      expect(state.error).toBe('Trigger failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.integrations = [{ id: 'i1' }];
      state.webhooks = [{ id: 'w1' }];
      state.error = 'err';
      goIntegrationStore.reset();
      expect(state.integrations).toEqual([]);
      expect(state.webhooks).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Go.getIntegrations as any).mockRejectedValue(new Error('test'));
      try { await goIntegrationStore.getIntegrations(); } catch (e) {}
      expect(state.error).toBe('test');
      goIntegrationStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
