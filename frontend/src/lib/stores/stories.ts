import { writable } from "svelte/store";
import { Elixir } from "../../lib/api";

interface Story {
  id: string;
  userId: string;
  userName: string;
  userAvatar?: string;
  content?: string;
  type?: "image" | "video";
  mediaUrl?: string;
  createdAt: Date | string;
  expiresAt?: Date | string;
  reactions?: Array<{ emoji: string; userId: string }>;
  viewers?: string[];
  viewCount?: number;
  items?: Array<{
    id: string;
    type: 'image' | 'video';
    url: string;
    text?: string;
    duration?: number;
  }>;
}

interface StoryReaction {
  emoji: string;
  userId: string;
  itemId: string;
}

interface StoriesState {
  stories: Story[];
  userStories: Story[];
  publicStories: Story[];
  storyViewers: string[];
  loading: boolean;
  error: string | null;
}

function createStoriesStore() {
  const initialState: StoriesState = {
    stories: [],
    userStories: [],
    publicStories: [],
    storyViewers: [],
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<StoriesState>(initialState);

  function getAuthHeaders(): HeadersInit {
    return {
      Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
    };
  }

  return {
    subscribe,
    set,
    update,

    async loadStories() {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const response = await fetch("/api/stories", {
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to load stories");
        }

        const data = await response.json();
        update((state) => ({ ...state, stories: data, loading: false }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Unknown error";
        update((state) => ({ ...state, error: errorMessage, loading: false }));
      }
    },

    async loadUserStories() {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const response = await fetch("/api/stories/my", {
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to load user stories");
        }

        const data = await response.json();
        update((state) => ({ ...state, userStories: data, loading: false }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Unknown error";
        update((state) => ({ ...state, error: errorMessage, loading: false }));
      }
    },

    async loadPublicStories() {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const response = await fetch("/api/stories/public", {
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to load public stories");
        }

        const data = await response.json();
        update((state) => ({ ...state, publicStories: data, loading: false }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Unknown error";
        update((state) => ({ ...state, error: errorMessage, loading: false }));
      }
    },

    async createStory(storyData: { files: File[]; text?: string }) {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const formData = new FormData();
        storyData.files.forEach((file) => {
          formData.append("files[]", file);
        });
        formData.append("text", storyData.text || "");

        const response = await fetch("/api/stories", {
          method: "POST",
          headers: getAuthHeaders(),
          body: formData,
        });

        if (!response.ok) {
          throw new Error("Failed to create story");
        }

        const data = await response.json();
        update((state) => ({
          ...state,
          userStories: [data, ...state.userStories],
          loading: false,
        }));

        return data;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Unknown error";
        update((state) => ({ ...state, error: errorMessage, loading: false }));
        throw error;
      }
    },

    async viewStory(storyId: string) {
      try {
        const response = await fetch(`/api/stories/${storyId}/view`, {
          method: "POST",
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          console.error("Failed to view story");
        }
      } catch (error) {
        console.error("Error viewing story:", error);
      }
    },

    async addReaction({
      storyId,
      emoji,
      itemId,
    }: {
      storyId: string;
      emoji: string;
      itemId?: string;
    }) {
      try {
        const response = await fetch(`/api/stories/${storyId}/reaction`, {
          method: "POST",
          headers: {
            ...getAuthHeaders(),
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ emoji, itemId }),
        });

        if (!response.ok) {
          throw new Error("Failed to add reaction");
        }

        update((state) => ({
          ...state,
          stories: state.stories.map((story) => {
            if (story.id === storyId) {
              return {
                ...story,
                reactions: [
                  ...(story.reactions || []),
                  { emoji, userId: localStorage.getItem("user_id") || "" },
                ],
              };
            }
            return story;
          }),
        }));
      } catch (error) {
        console.error("Error adding reaction:", error);
      }
    },

    async removeReaction({
      storyId,
      itemId,
    }: {
      storyId: string;
      itemId?: string;
    }) {
      try {
        const response = await fetch(`/api/stories/${storyId}/reaction`, {
          method: "DELETE",
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to remove reaction");
        }

        update((state) => ({
          ...state,
          stories: state.stories.map((story) => {
            if (story.id === storyId) {
              return {
                ...story,
                reactions: (story.reactions || []).filter(
                  (r) => r.userId !== localStorage.getItem("user_id"),
                ),
              };
            }
            return story;
          }),
        }));
      } catch (error) {
        console.error("Error removing reaction:", error);
      }
    },

    async loadStoryViewers(storyId: string) {
      try {
        const response = await fetch(`/api/stories/${storyId}/viewers`, {
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to load story viewers");
        }

        const data = await response.json();
        update((state) => ({ ...state, storyViewers: data }));
      } catch (error) {
        console.error("Error loading story viewers:", error);
      }
    },

    async deleteStory(storyId: string) {
      try {
        const response = await fetch(`/api/stories/${storyId}`, {
          method: "DELETE",
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to delete story");
        }

        update((state) => ({
          ...state,
          userStories: state.userStories.filter((s) => s.id !== storyId),
        }));
      } catch (error) {
        console.error("Error deleting story:", error);
        throw error;
      }
    },

    reset() {
      set(initialState);
    },
  };
}

export const storiesStore = createStoriesStore();

export const {
  loadStories,
  loadUserStories,
  loadPublicStories,
  createStory,
  viewStory,
  addReaction,
  removeReaction,
  loadStoryViewers,
  deleteStory,
} = storiesStore;
