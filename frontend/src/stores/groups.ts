// Groups Store - TypeScript Version
// PHP Conversation Module (Groups) with type safety

import { writable } from "svelte/store";
import { PHP } from "../lib/api";
import type { Group, GroupMember } from "../lib/types";

interface GroupsState {
  groups: Group[];
  loading: boolean;
  error: string | null;
}

function createGroupsStore() {
  const initialState: GroupsState = {
    groups: [],
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<GroupsState>(initialState);

  return {
    subscribe,

    loadGroups: async (): Promise<Group[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const groups = await PHP.getGroups();
        update((prev) => ({
          ...prev,
          groups,
          loading: false,
        }));
        return groups;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to load groups";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    createGroup: async (title: string, description = ""): Promise<Group> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const group = await PHP.createGroup({ title, description } as {
          title: string;
          description?: string;
        });
        update((prev) => ({
          ...prev,
          groups: [...prev.groups, group],
          loading: false,
        }));
        return group;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create group";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    joinGroup: async (groupId: string): Promise<Group> => {
      try {
        const group = await PHP.joinGroup(groupId);
        update((prev) => {
          const exists = prev.groups.find((g) => g.id === groupId);
          if (exists) {
            return {
              ...prev,
              groups: prev.groups.map((g) => (g.id === groupId ? group : g)),
            };
          } else {
            return {
              ...prev,
              groups: [...prev.groups, group],
            };
          }
        });
        return group;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to join group";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    leaveGroup: async (groupId: string): Promise<void> => {
      try {
        await PHP.leaveGroup(groupId);
        update((prev) => ({
          ...prev,
          groups: prev.groups.filter((g) => g.id !== groupId),
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to leave group";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    addParticipant: async (
      groupId: string,
      userId: string,
      role = "member",
    ): Promise<void> => {
      try {
        await PHP.addGroupParticipant(groupId, userId, role);
        update((prev) => ({
          ...prev,
          groups: prev.groups.map((g) =>
            g.id === groupId
              ? {
                  ...g,
                  members: [...(g.members || []), userId],
                }
              : g,
          ),
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to add participant";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    updateGroup: (group: Group): void => {
      update((state) => ({
        ...state,
        groups: state.groups.map((g) =>
          g.id === group.id ? { ...g, ...group } : g,
        ),
      }));
    },

    addGroup: (group: Group): void => {
      update((state) => ({
        ...state,
        groups: [group, ...state.groups],
      }));
    },

    removeGroup: (groupId: string): void => {
      update((state) => ({
        ...state,
        groups: state.groups.filter((g) => g.id !== groupId),
      }));
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        groups: [],
        loading: false,
        error: null,
      });
    },
  };
}

export const groupsStore = createGroupsStore();

export const {
  loadGroups,
  createGroup,
  joinGroup,
  leaveGroup,
  addParticipant,
  updateGroup,
  addGroup,
  removeGroup,
  clearError,
} = groupsStore;
