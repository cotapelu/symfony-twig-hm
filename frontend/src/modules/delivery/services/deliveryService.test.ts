import { describe, it, expect, vi, beforeEach } from 'vitest';
import { DeliveryService } from './deliveryService';
import { apiClient } from '../../../appcore/api-client';

vi.mock('../../../appcore/api-client', () => ({
  apiClient: { get: vi.fn(), post: vi.fn(), put: vi.fn() }
}));

describe('DeliveryService', () => {
  let service: DeliveryService;

  beforeEach(() => {
    service = new DeliveryService();
    vi.clearAllMocks();
  });

  describe('getDeliveries', () => {
    it('should fetch deliveries', async () => {
      const deliveries = [{ id: 'd1' }];
      (apiClient.get as any).mockResolvedValue(deliveries);
      const result = await service.getDeliveries();
      expect(result).toEqual(deliveries);
      expect(apiClient.get).toHaveBeenCalledWith('/api/delivery');
    });

    it('should propagate errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('API error'));
      await expect(service.getDeliveries()).rejects.toThrow('API error');
    });
  });

  describe('getDelivery', () => {
    it('should fetch delivery by id', async () => {
      const delivery = { id: 'd1' };
      (apiClient.get as any).mockResolvedValue(delivery);
      const result = await service.getDelivery('d1');
      expect(result).toEqual(delivery);
      expect(apiClient.get).toHaveBeenCalledWith('/api/delivery/d1');
    });

    it('should propagate errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Not found'));
      await expect(service.getDelivery('d1')).rejects.toThrow('Not found');
    });
  });

  describe('createDelivery', () => {
    it('should create a delivery', async () => {
      const created = { id: 'new' };
      (apiClient.post as any).mockResolvedValue(created);
      const request = { address: '123 Main St', items: [] };
      const result = await service.createDelivery(request);
      expect(result).toEqual(created);
      expect(apiClient.post).toHaveBeenCalledWith('/api/delivery', request);
    });

    it('should propagate errors', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Invalid'));
      await expect(service.createDelivery({} as any)).rejects.toThrow('Invalid');
    });
  });

  describe('updateDeliveryStatus', () => {
    it('should update delivery status', async () => {
      const updated = { id: 'd1', status: 'shipped' };
      (apiClient.put as any).mockResolvedValue(updated);
      const result = await service.updateDeliveryStatus('d1', { status: 'shipped' });
      expect(result).toEqual(updated);
      expect(apiClient.put).toHaveBeenCalledWith('/api/delivery/d1/status', { status: 'shipped' });
    });

    it('should propagate errors', async () => {
      (apiClient.put as any).mockRejectedValue(new Error('Fail'));
      await expect(service.updateDeliveryStatus('d1', { status: 'x' })).rejects.toThrow('Fail');
    });
  });

  describe('cancelDelivery', () => {
    it('should cancel a delivery', async () => {
      (apiClient.post as any).mockResolvedValue({ ok: true });
      await service.cancelDelivery('d1');
      expect(apiClient.post).toHaveBeenCalledWith('/api/delivery/d1/cancel', {});
    });

    it('should propagate errors', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Cannot cancel'));
      await expect(service.cancelDelivery('d1')).rejects.toThrow('Cannot cancel');
    });
  });

  describe('getActiveDeliveries', () => {
    it('should fetch active deliveries with correct query', async () => {
      const active = [{ id: 'd2' }];
      (apiClient.get as any).mockResolvedValue(active);
      const result = await service.getActiveDeliveries();
      expect(result).toEqual(active);
      expect(apiClient.get).toHaveBeenCalledWith('/api/delivery?status=assigned,picked_up,in_transit');
    });

    it('should propagate errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Err'));
      await expect(service.getActiveDeliveries()).rejects.toThrow('Err');
    });
  });
});
