import { describe, it, expect, vi, beforeEach } from 'vitest';
import { groupsStore } from './groups';
import { PHP } from '../lib/api';

vi.mock('../lib/api', () => ({
  PHP: {
    getGroups: vi.fn(),
    createGroup: vi.fn(),
    joinGroup: vi.fn(),
    leaveGroup: vi.fn(),
    addGroupParticipant: vi.fn(),
    updateGroup: vi.fn(),
  }
}));

describe('GroupsStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    groupsStore.reset();
    groupsStore.subscribe(s => { state = s; });
  });

  const mockGroup = (id: string, title: string): any => ({
    id,
    title,
    description: '',
    members: [],
    createdAt: new Date().toISOString()
  });

  describe('loadGroups', () => {
    it('should load groups successfully', async () => {
      const groups = [mockGroup('g1', 'Group1'), mockGroup('g2', 'Group2')];
      (PHP.getGroups as any).mockResolvedValue(groups);

      const result = await groupsStore.loadGroups();

      expect(result).toEqual(groups);
      expect(state.groups).toEqual(groups);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('should handle empty list', async () => {
      (PHP.getGroups as any).mockResolvedValue([]);
      await groupsStore.loadGroups();
      expect(state.groups).toEqual([]);
    });

    it('should set error on failure', async () => {
      const error = new Error('API down');
      (PHP.getGroups as any).mockRejectedValue(error);
      await expect(groupsStore.loadGroups()).rejects.toThrow('API down');
      expect(state.loading).toBe(false);
      expect(state.error).toBe('API down');
    });
  });

  describe('createGroup', () => {
    it('should create group via API and add to store', async () => {
      const newGroup = mockGroup('g1', 'New');
      (PHP.createGroup as any).mockResolvedValue(newGroup);

      const result = await groupsStore.createGroup('New', 'Desc');

      expect(result).toEqual(newGroup);
      expect(PHP.createGroup).toHaveBeenCalledWith({ title: 'New', description: 'Desc' });
      expect(state.groups[0]).toEqual(newGroup);
    });

    it('should handle create error', async () => {
      const error = new Error('Create failed');
      (PHP.createGroup as any).mockRejectedValue(error);
      await expect(groupsStore.createGroup('New', 'Desc')).rejects.toThrow('Create failed');
      expect(state.error).toBe('Create failed');
    });
  });

  describe('addGroup', () => {
    it('should add group locally', () => {
      const group = mockGroup('g1', 'Local');
      groupsStore.addGroup(group);
      expect(state.groups).toContain(group);
    });
  });

  describe('removeGroup', () => {
    it('should remove group locally', () => {
      const group = mockGroup('g1', 'Remove');
      groupsStore.addGroup(group);
      expect(state.groups).toHaveLength(1);
      groupsStore.removeGroup('g1');
      expect(state.groups).toHaveLength(0);
    });
  });

  describe('updateGroup', () => {
    it('should update group in store', () => {
      const group = mockGroup('g1', 'Old');
      groupsStore.addGroup(group);
      groupsStore.updateGroup({ id: 'g1', title: 'Updated' });
      expect(state.groups[0].title).toBe('Updated');
    });
  });

  describe('joinGroup', () => {
    it('should call API to join and update existing group', async () => {
      const group = mockGroup('g1', 'Join');
      groupsStore.addGroup(group);
      const returnedGroup = mockGroup('g1', 'Joined');
      (PHP.joinGroup as any).mockResolvedValue(returnedGroup);
      await groupsStore.joinGroup('g1');
      expect(PHP.joinGroup).toHaveBeenCalledWith('g1');
      expect(state.groups[0]).toEqual(returnedGroup);
    });

    it('should add group to store if not present', async () => {
      const returnedGroup = mockGroup('g1', 'Joined');
      (PHP.joinGroup as any).mockResolvedValue(returnedGroup);
      expect(state.groups).toHaveLength(0);
      await groupsStore.joinGroup('g1');
      expect(state.groups).toHaveLength(1);
      expect(state.groups[0]).toEqual(returnedGroup);
    });

    it('should handle join error and not modify groups', async () => {
      const group = mockGroup('g1', 'Join');
      groupsStore.addGroup(group);
      const initialGroups = [...state.groups];
      (PHP.joinGroup as any).mockRejectedValue(new Error('Join failed'));
      await expect(groupsStore.joinGroup('g1')).rejects.toThrow('Join failed');
      expect(state.groups).toEqual(initialGroups);
    });
  });

  describe('leaveGroup', () => {
    it('should call API to leave and remove group from store', async () => {
      const group = mockGroup('g1', 'Leave');
      groupsStore.addGroup(group);
      expect(state.groups).toHaveLength(1);
      (PHP.leaveGroup as any).mockResolvedValue({ success: true });
      await groupsStore.leaveGroup('g1');
      expect(PHP.leaveGroup).toHaveBeenCalledWith('g1');
      expect(state.groups).toHaveLength(0);
    });

    it('should handle leave error and keep group', async () => {
      const group = mockGroup('g1', 'Leave');
      groupsStore.addGroup(group);
      (PHP.leaveGroup as any).mockRejectedValue(new Error('Leave failed'));
      await expect(groupsStore.leaveGroup('g1')).rejects.toThrow('Leave failed');
      expect(state.groups).toHaveLength(1);
    });
  });

  describe('addParticipant', () => {
    it('should add participant to group in store', async () => {
      const group = mockGroup('g1', 'Group');
      groupsStore.addGroup(group);
      (PHP.addGroupParticipant as any).mockResolvedValue({ success: true });
      await groupsStore.addParticipant('g1', 'u1', 'member');
      expect(state.groups[0].members).toContain('u1');
    });

    it('does not modify groups if groupId not found', async () => {
      (PHP.addGroupParticipant as any).mockResolvedValue({ success: true });
      expect(state.groups).toHaveLength(0);
      await groupsStore.addParticipant('g1', 'u1', 'member');
      expect(state.groups).toHaveLength(0);
    });

    it('handles add participant error', async () => {
      const group = mockGroup('g1', 'Group');
      groupsStore.addGroup(group);
      const initialMembers = [...group.members];
      (PHP.addGroupParticipant as any).mockRejectedValue(new Error('Add failed'));
      await expect(groupsStore.addParticipant('g1', 'u1', 'member')).rejects.toThrow('Add failed');
      expect(state.groups[0].members).toEqual(initialMembers);
    });
  });

  describe('error handling', () => {
    it('should set error on joinGroup failure', async () => {
      const group = mockGroup('g1', 'Join');
      groupsStore.addGroup(group);
      (PHP.joinGroup as any).mockRejectedValue(new Error('Join failed'));
      await expect(groupsStore.joinGroup('g1')).rejects.toThrow('Join failed');
      expect(state.error).toBe('Join failed');
    });

    it('should set error on leaveGroup failure', async () => {
      (PHP.leaveGroup as any).mockRejectedValue(new Error('Leave failed'));
      await expect(groupsStore.leaveGroup('g1')).rejects.toThrow('Leave failed');
      expect(state.error).toBe('Leave failed');
    });

    it('should set error on addParticipant failure', async () => {
      (PHP.addGroupParticipant as any).mockRejectedValue(new Error('Add participant failed'));
      await expect(groupsStore.addParticipant('g1', 'u1', 'member')).rejects.toThrow('Add participant failed');
      expect(state.error).toBe('Add participant failed');
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (PHP.getGroups as any).mockRejectedValue(new Error('err'));
      try { await groupsStore.loadGroups(); } catch (e) {}
      expect(state.error).toBe('err');
      groupsStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
