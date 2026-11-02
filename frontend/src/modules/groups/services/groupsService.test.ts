import { describe, it, expect, vi, beforeEach } from 'vitest';
import { GroupsService } from './groupsService';
import { apiClient } from '../../../appcore/api-client';

// Mock apiClient
vi.mock('../../../appcore/api-client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn()
  }
}));

describe('GroupsService', () => {
  let service: GroupsService;

  beforeEach(() => {
    service = new GroupsService();
    vi.clearAllMocks();
  });

  describe('getGroups', () => {
    it('should fetch groups successfully', async () => {
      const groups = [{ id: 'g1', name: 'Hikers' }];
      (apiClient.get as any).mockResolvedValue(groups);

      const result = await service.getGroups();

      expect(result).toEqual(groups);
      expect(apiClient.get).toHaveBeenCalledWith('/api/groups');
    });

    it('should propagate errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Network error'));

      await expect(service.getGroups()).rejects.toThrow('Network error');
    });
  });

  describe('getGroup', () => {
    it('should fetch group by id', async () => {
      const group = { id: 'g1', name: 'Hikers' };
      (apiClient.get as any).mockResolvedValue(group);

      const result = await service.getGroup('g1');

      expect(result).toEqual(group);
      expect(apiClient.get).toHaveBeenCalledWith('/api/groups/g1');
    });

    it('should throw on error', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Not found'));

      await expect(service.getGroup('invalid')).rejects.toThrow('Not found');
    });
  });

  describe('createGroup', () => {
    it('should create group with correct payload', async () => {
      const data = { name: 'New Group', description: 'Desc', isPrivate: false };
      const created = { id: 'g2', ...data };
      (apiClient.post as any).mockResolvedValue(created);

      const result = await service.createGroup(data);

      expect(result).toEqual(created);
      expect(apiClient.post).toHaveBeenCalledWith('/api/groups', data);
    });

    it('should handle errors', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Forbidden'));

      await expect(service.createGroup({ name: 'X', description: 'Y', isPrivate: false })).rejects.toThrow('Forbidden');
    });
  });

  describe('joinGroup', () => {
    it('should join group', async () => {
      (apiClient.post as any).mockResolvedValue(undefined);

      await expect(service.joinGroup('g1')).resolves.toBeUndefined();
      expect(apiClient.post).toHaveBeenCalledWith('/api/groups/g1/join');
    });

    it('should throw on failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Already joined'));

      await expect(service.joinGroup('g1')).rejects.toThrow('Already joined');
    });
  });

  describe('leaveGroup', () => {
    it('should leave group', async () => {
      (apiClient.post as any).mockResolvedValue(undefined);

      await expect(service.leaveGroup('g1')).resolves.toBeUndefined();
      expect(apiClient.post).toHaveBeenCalledWith('/api/groups/g1/leave');
    });

    it('should throw on failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Not a member'));

      await expect(service.leaveGroup('g1')).rejects.toThrow('Not a member');
    });
  });
});
