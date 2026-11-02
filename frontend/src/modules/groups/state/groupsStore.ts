import { writable, derived } from 'svelte/store';

export interface Group {
  id: string;
  name: string;
  description: string;
  avatar?: string;
  members: string[];
  admins: string[];
  isPrivate: boolean;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface GroupsState {
  groups: Group[];
  currentGroup: Group | null;
  loading: boolean;
  error: string | null;
}

export const groupsStore = writable<GroupsState>({
  groups: [],
  currentGroup: null,
  loading: false,
  error: null
});

export const groups = derived(groupsStore, $state => $state.groups);
export const currentGroup = derived(groupsStore, $state => $state.currentGroup);
export const loading = derived(groupsStore, $state => $state.loading);
export const error = derived(groupsStore, $state => $state.error);