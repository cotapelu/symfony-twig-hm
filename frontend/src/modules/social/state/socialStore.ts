import { writable, derived } from 'svelte/store';

export interface Post {
  id: string;
  authorId: string;
  content: string;
  images?: string[];
  likes: string[];
  comments: Comment[];
  shares: number;
  timestamp: Date;
  privacy: 'public' | 'friends' | 'private';
}

export interface Comment {
  id: string;
  postId: string;
  authorId: string;
  content: string;
  likes: string[];
  timestamp: Date;
}

export interface SocialState {
  posts: Post[];
  loading: boolean;
  error: string | null;
  newPostContent: string;
  selectedImages: string[];
}

export const socialStore = writable<SocialState>({
  posts: [],
  loading: false,
  error: null,
  newPostContent: '',
  selectedImages: []
});

export const posts = derived(socialStore, $state => $state.posts);
export const loading = derived(socialStore, $state => $state.loading);
export const error = derived(socialStore, $state => $state.error);
export const newPostContent = derived(socialStore, $state => $state.newPostContent);
export const selectedImages = derived(socialStore, $state => $state.selectedImages);

export async function loadPosts() {
  socialStore.update(state => ({ ...state, loading: true, error: null }));
  
  try {
    const response = await fetch('/api/social/posts');
    if (!response.ok) throw new Error('Failed to load posts');
    
    const data = await response.json();
    socialStore.update(state => ({
      ...state,
      posts: data,
      loading: false
    }));
  } catch (err) {
    socialStore.update(state => ({
      ...state,
      loading: false,
      error: (err as Error).message
    }));
  }
}

export async function createPost(content: string, images: string[] = []) {
  try {
    const response = await fetch('/api/social/posts', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content, images })
    });
    
    if (!response.ok) throw new Error('Failed to create post');
    
    const newPost = await response.json();
    socialStore.update(state => ({
      ...state,
      posts: [newPost, ...state.posts],
      newPostContent: '',
      selectedImages: []
    }));
    
    return newPost;
  } catch (err) {
    socialStore.update(state => ({
      ...state,
      error: (err as Error).message
    }));
    throw err;
  }
}

export async function likePost(postId: string) {
  try {
    const response = await fetch(`/api/social/posts/${postId}/like`, {
      method: 'POST'
    });
    
    if (!response.ok) throw new Error('Failed to like post');
    
    socialStore.update(state => ({
      ...state,
      posts: state.posts.map(post =>
        post.id === postId
          ? { ...post, likes: [...post.likes, 'current-user'] }
          : post
      )
    }));
  } catch (err) {
    socialStore.update(state => ({
      ...state,
      error: (err as Error).message
    }));
  }
}

export async function unlikePost(postId: string) {
  try {
    const response = await fetch(`/api/social/posts/${postId}/like`, {
      method: 'DELETE'
    });
    
    if (!response.ok) throw new Error('Failed to unlike post');
    
    socialStore.update(state => ({
      ...state,
      posts: state.posts.map(post =>
        post.id === postId
          ? { ...post, likes: post.likes.filter(id => id !== 'current-user') }
          : post
      )
    }));
  } catch (err) {
    socialStore.update(state => ({
      ...state,
      error: (err as Error).message
    }));
  }
}

export async function addComment(postId: string, content: string) {
  try {
    const response = await fetch(`/api/social/posts/${postId}/comments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content })
    });
    
    if (!response.ok) throw new Error('Failed to add comment');
    
    const newComment = await response.json();
    socialStore.update(state => ({
      ...state,
      posts: state.posts.map(post =>
        post.id === postId
          ? { ...post, comments: [...post.comments, newComment] }
          : post
      )
    }));
    
    return newComment;
  } catch (err) {
    socialStore.update(state => ({
      ...state,
      error: (err as Error).message
    }));
    throw err;
  }
}

export function setNewPostContent(content: string) {
  socialStore.update(state => ({ ...state, newPostContent: content }));
}

export function setSelectedImages(images: string[]) {
  socialStore.update(state => ({ ...state, selectedImages: images }));
}

export function clearError() {
  socialStore.update(state => ({ ...state, error: null }));
}