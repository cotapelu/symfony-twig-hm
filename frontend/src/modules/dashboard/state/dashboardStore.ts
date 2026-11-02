import { writable, derived } from 'svelte/store';

export interface DashboardStats {
  totalMessages: number;
  unreadMessages: number;
  totalFriends: number;
  onlineFriends: number;
  totalGroups: number;
  activeGroups: number;
  recentActivity: ActivityItem[];
}

export interface ActivityItem {
  id: string;
  type: 'message' | 'post' | 'friend' | 'group';
  title: string;
  description: string;
  timestamp: Date;
  unread: boolean;
}

export interface DashboardState {
  stats: DashboardStats | null;
  loading: boolean;
  error: string | null;
}

export const dashboardStore = writable<DashboardState>({
  stats: null,
  loading: false,
  error: null
});

export const stats = derived(dashboardStore, $state => $state.stats);
export const loading = derived(dashboardStore, $state => $state.loading);
export const error = derived(dashboardStore, $state => $state.error);