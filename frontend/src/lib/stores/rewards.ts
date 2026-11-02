import { writable } from "svelte/store";
import { Elixir } from "../../lib/api";

interface PointsHistoryItem {
  icon: string;
  description: string;
  points: number;
  timestamp: Date;
  type: "earned" | "spent";
}

interface Badge {
  id: string;
  name: string;
  icon: string;
  earned: boolean;
  earnedAt?: Date;
  progress?: number;
  target?: number;
  description?: string;
}

interface LeaderboardEntry {
  rank: number;
  userId: string;
  name?: string;
  userName: string;
  avatar?: string;
  points: number;
  level?: number;
  isCurrentUser?: boolean;
}

interface RewardsState {
  points: number;
  pointsHistory: PointsHistoryItem[];
  badges: Badge[];
  leaderboard: LeaderboardEntry[];
  canClaimDailyPoints: boolean;
  lastClaimedAt: Date | null;
  loading: boolean;
  error: string | null;
}

function createRewardsStore() {
  const initialState: RewardsState = {
    points: 0,
    pointsHistory: [],
    badges: [],
    leaderboard: [],
    canClaimDailyPoints: true,
    lastClaimedAt: null,
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<RewardsState>(initialState);

  function getAuthHeaders(): HeadersInit {
    return {
      Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
    };
  }

  return {
    subscribe,
    set,
    update,

    async loadPoints() {
      update((state) => ({ ...state, loading: true, error: null }));

      try {
        const response = await fetch("/api/rewards/points", {
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to load points");
        }

        const data = await response.json();

        update((state) => ({
          ...state,
          points: data.points,
          pointsHistory: data.history || [],
          canClaimDailyPoints: data.canClaimDaily || false,
          lastClaimedAt: data.lastClaimedAt || null,
          loading: false,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Unknown error";
        update((state) => ({
          ...state,
          error: errorMessage,
          loading: false,
        }));
      }
    },

    async loadBadges() {
      update((state) => ({ ...state, loading: true, error: null }));

      try {
        const response = await fetch("/api/rewards/badges", {
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to load badges");
        }

        const data = await response.json();

        update((state) => ({
          ...state,
          badges: data.badges || [],
          loading: false,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Unknown error";
        update((state) => ({
          ...state,
          error: errorMessage,
          loading: false,
        }));
      }
    },

    async loadLeaderboard() {
      update((state) => ({ ...state, loading: true, error: null }));

      try {
        const response = await fetch("/api/rewards/leaderboard", {
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to load leaderboard");
        }

        const data = await response.json();

        update((state) => ({
          ...state,
          leaderboard: data.leaderboard || [],
          loading: false,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Unknown error";
        update((state) => ({
          ...state,
          error: errorMessage,
          loading: false,
        }));
      }
    },

    async claimDailyPoints() {
      update((state) => ({ ...state, loading: true, error: null }));

      try {
        const response = await fetch("/api/rewards/points/claim-daily", {
          method: "POST",
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to claim daily points");
        }

        const data = await response.json();

        update((state) => ({
          ...state,
          points: state.points + data.points,
          canClaimDailyPoints: false,
          lastClaimedAt: new Date(),
          pointsHistory: [
            {
              icon: "🎁",
              description: "Daily login bonus",
              points: data.points,
              timestamp: new Date(),
              type: "earned",
            },
            ...state.pointsHistory,
          ],
          loading: false,
        }));

        return data;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Unknown error";
        update((state) => ({
          ...state,
          error: errorMessage,
          loading: false,
        }));
        throw error;
      }
    },

    async earnBadge(achievementId: string) {
      try {
        const response = await fetch(`/api/rewards/badges/earn`, {
          method: "POST",
          headers: {
            ...getAuthHeaders(),
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ achievementId }),
        });

        if (!response.ok) {
          throw new Error("Failed to earn badge");
        }

        const data = await response.json();

        update((state) => ({
          ...state,
          badges: state.badges.map((badge) =>
            badge.id === achievementId ? { ...badge, earned: true } : badge,
          ),
          points: state.points + data.pointsAwarded,
        }));

        return data;
      } catch (error) {
        console.error("Error earning badge:", error);
        throw error;
      }
    },

    async addPoints(points: number, description: string, icon = "✨") {
      try {
        const response = await fetch("/api/rewards/points/add", {
          method: "POST",
          headers: {
            ...getAuthHeaders(),
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ points, description, icon }),
        });

        if (!response.ok) {
          throw new Error("Failed to add points");
        }

        update((state) => ({
          ...state,
          points: state.points + points,
          pointsHistory: [
            {
              icon,
              description,
              points,
              timestamp: new Date(),
              type: points > 0 ? "earned" : "spent",
            },
            ...state.pointsHistory,
          ],
        }));

        return response.json();
      } catch (error) {
        console.error("Error adding points:", error);
        throw error;
      }
    },

    reset() {
      set(initialState);
    },
  };
}

export const rewardsStore = createRewardsStore();
