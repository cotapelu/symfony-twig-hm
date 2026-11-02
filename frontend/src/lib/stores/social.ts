import { writable, derived } from "svelte/store";
import { Elixir } from "../../lib/api";

interface Post {
  id: string;
  userId: string;
  userName: string;
  userAvatar?: string;
  text: string;
  media?: string[];
  location?: { lat: number; lng: number; name?: string };
  privacy: "public" | "friends" | "private";
  createdAt: Date;
  likes: number;
  isLiked: boolean;
  comments: Comment[];
  shares: number;
  reactions?: Array<{ emoji: string }>;
}

interface Comment {
  id: string;
  userId: string;
  userName: string;
  userAvatar?: string;
  text: string;
  createdAt: Date;
  likes: number;
}

interface SocialState {
  posts: Post[];
  comments: Record<string, Comment[]>;
  reactions: Record<string, any[]>;
  filter: string;
  loading: boolean;
  loadingMore: boolean;
  hasMore: boolean;
  error: string | null;
  currentPage: number;
}

function createSocialStore() {
  const initialState: SocialState = {
    posts: [],
    comments: {},
    reactions: {},
    filter: "all",
    loading: false,
    loadingMore: false,
    hasMore: true,
    error: null,
    currentPage: 1,
  };

  const { subscribe, set, update } = writable<SocialState>(initialState);

  function getAuthHeaders(): HeadersInit {
    return {
      Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
    };
  }

  return {
    subscribe,
    set,
    update,

    async loadPosts(page = 1) {
      let currentFilter = "all";
      update((state) => {
        currentFilter = state.filter;
        return {
          ...state,
          loading: page === 1,
          loadingMore: page > 1,
          error: null,
        };
      });

      try {
        const filterParam =
          currentFilter === "all" ? "" : `?filter=${currentFilter}`;
        const response = await fetch(
          `/api/social/posts${filterParam}&page=${page}`,
          {
            headers: getAuthHeaders(),
          },
        );

        if (!response.ok) {
          throw new Error("Failed to load posts");
        }

        const data = await response.json();

        update((state) => ({
          ...state,
          posts: page === 1 ? data : [...state.posts, ...data],
          currentPage: page,
          hasMore: data.length === 10,
          loading: false,
          loadingMore: false,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Unknown error";
        update((state) => ({
          ...state,
          error: errorMessage,
          loading: false,
          loadingMore: false,
        }));
      }
    },

    loadMorePosts() {
      update((state) => {
        const newPage = state.currentPage + 1;
        this.loadPosts(newPage);
        return state;
      });
    },

    async createPost(postData: {
      text?: string;
      privacy?: string;
      location?: any;
      media?: File[];
    }) {
      update((state) => ({ ...state, loading: true, error: null }));

      try {
        const formData = new FormData();
        formData.append("text", postData.text || "");
        formData.append("privacy", postData.privacy || "public");

        if (postData.location) {
          formData.append("location", JSON.stringify(postData.location));
        }

        if (postData.media && postData.media.length > 0) {
          postData.media.forEach((file) => {
            formData.append("media[]", file);
          });
        }

        const response = await fetch("/api/social/posts", {
          method: "POST",
          headers: getAuthHeaders(),
          body: formData,
        });

        if (!response.ok) {
          throw new Error("Failed to create post");
        }

        const newPost = await response.json();

        update((state) => ({
          ...state,
          posts: [newPost, ...state.posts],
          loading: false,
        }));

        return newPost;
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

    async likePost(postId: string) {
      try {
        const response = await fetch(`/api/social/posts/${postId}/like`, {
          method: "POST",
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to like post");
        }

        update((state) => ({
          ...state,
          posts: state.posts.map((post) => {
            if (post.id === postId) {
              return {
                ...post,
                isLiked: !post.isLiked,
                likes: post.isLiked ? post.likes - 1 : post.likes + 1,
              };
            }
            return post;
          }),
        }));
      } catch (error) {
        console.error("Error liking post:", error);
      }
    },

    async unlikePost(postId: string) {
      try {
        const response = await fetch(`/api/social/posts/${postId}/like`, {
          method: "DELETE",
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to unlike post");
        }

        update((state) => ({
          ...state,
          posts: state.posts.map((post) => {
            if (post.id === postId) {
              return {
                ...post,
                isLiked: false,
                likes: Math.max(0, post.likes - 1),
              };
            }
            return post;
          }),
        }));
      } catch (error) {
        console.error("Error unliking post:", error);
      }
    },

    async reactToPost(postId: string, emoji: string) {
      try {
        const response = await fetch(`/api/social/posts/${postId}/react`, {
          method: "POST",
          headers: {
            ...getAuthHeaders(),
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ emoji }),
        });

        if (!response.ok) {
          throw new Error("Failed to react to post");
        }

        update((state) => ({
          ...state,
          posts: state.posts.map((post) => {
            if (post.id === postId) {
              return {
                ...post,
                reactions: [...(post.reactions || []), { emoji }],
              };
            }
            return post;
          }),
        }));
      } catch (error) {
        console.error("Error reacting to post:", error);
      }
    },

    async sharePost(postId: string) {
      try {
        const response = await fetch(`/api/social/posts/${postId}/share`, {
          method: "POST",
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to share post");
        }

        update((state) => ({
          ...state,
          posts: state.posts.map((post) => {
            if (post.id === postId) {
              return {
                ...post,
                shares: (post.shares || 0) + 1,
              };
            }
            return post;
          }),
        }));
      } catch (error) {
        console.error("Error sharing post:", error);
      }
    },

    async loadComments(postId: string) {
      try {
        const response = await fetch(`/api/social/posts/${postId}/comments`, {
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to load comments");
        }

        const comments = await response.json();

        update((state) => ({
          ...state,
          comments: {
            ...state.comments,
            [postId]: comments,
          },
          posts: state.posts.map((post) => {
            if (post.id === postId) {
              return {
                ...post,
                comments,
              };
            }
            return post;
          }),
        }));
      } catch (error) {
        console.error("Error loading comments:", error);
      }
    },

    async commentOnPost(postId: string, text: string) {
      try {
        const response = await fetch(`/api/social/posts/${postId}/comments`, {
          method: "POST",
          headers: {
            ...getAuthHeaders(),
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ text }),
        });

        if (!response.ok) {
          throw new Error("Failed to add comment");
        }

        const newComment = await response.json();

        update((state) => ({
          ...state,
          comments: {
            ...state.comments,
            [postId]: [...(state.comments[postId] || []), newComment],
          },
          posts: state.posts.map((post) => {
            if (post.id === postId) {
              return {
                ...post,
                comments: [...(post.comments || []), newComment],
              };
            }
            return post;
          }),
        }));

        return newComment;
      } catch (error) {
        console.error("Error adding comment:", error);
        throw error;
      }
    },

    async loadReactions(postId: string) {
      try {
        const response = await fetch(`/api/social/posts/${postId}/likes`, {
          headers: getAuthHeaders(),
        });

        if (!response.ok) {
          throw new Error("Failed to load reactions");
        }

        const reactions = await response.json();

        update((state) => ({
          ...state,
          reactions: {
            ...state.reactions,
            [postId]: reactions,
          },
        }));
      } catch (error) {
        console.error("Error loading reactions:", error);
      }
    },

    async likeComment(postId: string, commentId: string) {
      try {
        const response = await fetch(
          `/api/social/posts/${postId}/comments/${commentId}/like`,
          {
            method: "POST",
            headers: getAuthHeaders(),
          },
        );

        if (!response.ok) {
          throw new Error("Failed to like comment");
        }

        update((state) => ({
          ...state,
          comments: {
            ...state.comments,
            [postId]: (state.comments[postId] || []).map((comment) => {
              if (comment.id === commentId) {
                return {
                  ...comment,
                  likes: (comment.likes || 0) + 1,
                };
              }
              return comment;
            }),
          },
        }));
      } catch (error) {
        console.error("Error liking comment:", error);
      }
    },

    setFilter(filter: string) {
      update((state) => ({ ...state, filter, currentPage: 1 }));
      this.loadPosts(1);
    },

    getComments(postId: string): any[] {
      let comments: any[] = [];
      const unsubscribe = subscribe((state) => {
        comments = state.comments[postId] || [];
      });
      unsubscribe();
      return comments;
    },

    getReactions(postId: string): any[] {
      let reactions: any[] = [];
      const unsubscribe = subscribe((state) => {
        reactions = state.reactions[postId] || [];
      });
      unsubscribe();
      return reactions;
    },

    reset() {
      set(initialState);
    },
  };
}

export const socialStore = createSocialStore();
