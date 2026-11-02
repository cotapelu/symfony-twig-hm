import { describe, it, expect, beforeEach } from 'vitest';
import { groupsStore, groups, currentGroup, loading, error } from './groupsStore';
import type { Group } from '../contracts/groupsTypes';

function getCurrentState() {
  let current: any;
  const unsub = groupsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockGroup = (overrides: Partial<Group> = {}): Group => ({
  id: `grp-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Group Name',
  description: 'Description',
  avatar: '',
  members: ['user1', 'user2'],
  admins: ['user1'],
  isPrivate: false,
  createdBy: 'user1',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

describe('GroupsStore', () => {
  beforeEach(() => {
    groupsStore.set({ groups: [], currentGroup: null, loading: false, error: null });
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.groups).toEqual([]);
    expect(state.currentGroup).toBeNull();
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let g: Group[] = [], cg: Group | null = [], l = false, e: string | null = null;
    const unsubG = groups.subscribe(s => { g = s; });
    const unsubC = currentGroup.subscribe(s => { cg = s; });
    const unsubL = loading.subscribe(s => { l = s; });
    const unsubE = error.subscribe(s => { e = s; });
    expect(g).toEqual([]);
    expect(cg).toBeNull();
    expect(l).toBe(false);
    expect(e).toBeNull();
    unsubG(); unsubC(); unsubL(); unsubE();
  });

  // Since there are no exported methods, we test direct set.
  it('can add a group via direct store set', () => {
    const grp = mockGroup();
    groupsStore.update(s => ({ ...s, groups: [grp] }));
    const state = getCurrentState();
    expect(state.groups).toContainEqual(grp);
  });

  it('can set currentGroup', () => {
    const grp = mockGroup();
    groupsStore.update(s => ({ ...s, currentGroup: grp }));
    const state = getCurrentState();
    expect(state.currentGroup).toEqual(grp);
  });
});
