// Social/Groups Service - Connects to PHP Social + Groups modules
import { getApiUrl } from '../config/api';

export interface Post {
  id: string;
  userId: string;
  userName: string;
  userAvatar?: string;
  content: string;
  images?: string[];
  likesCount: number;
  commentsCount: number;
  isLiked: boolean;
  createdAt: string;
}

export interface Group {
  id: string;
  name: string;
  description: string;
  image?: string;
  membersCount: number;
  isMember: boolean;
}

export interface Comment {
  id: string;
  postId: string;
  userId: string;
  userName: string;
  content: string;
  createdAt: string;
}

class SocialService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getFeed(): Promise<Post[]> {
    const url = getApiUrl('social', '/api/social/feed');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch feed');
    const data = await response.json();
    return data.posts || [];
  }

  async createPost(content: string, images?: string[]): Promise<Post> {
    const url = getApiUrl('social', '/api/social/posts');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ content, images }),
    });
    if (!response.ok) throw new Error('Failed to create post');
    return response.json();
  }

  async likePost(postId: string): Promise<void> {
    const url = getApiUrl('social', `/api/social/posts/${postId}/like`);
    await fetch(url, { method: 'POST', headers: this.getHeaders() });
  }

  async commentPost(postId: string, content: string): Promise<Comment> {
    const url = getApiUrl('social', `/api/social/posts/${postId}/comments`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ content }),
    });
    if (!response.ok) throw new Error('Failed to comment');
    return response.json();
  }

  async getGroups(): Promise<Group[]> {
    const url = getApiUrl('social', '/api/groups');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch groups');
    const data = await response.json();
    return data.groups || [];
  }

  async joinGroup(groupId: string): Promise<void> {
    const url = getApiUrl('social', `/api/groups/${groupId}/join`);
    await fetch(url, { method: 'POST', headers: this.getHeaders() });
  }
}

export const socialService = new SocialService();
