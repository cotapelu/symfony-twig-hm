import { apiClient } from '../../../appcore/api-client';

export class SocialService {
  private baseUrl = '/api/social';

  async getPosts() {
    return apiClient.get(`${this.baseUrl}/posts`);
  }

  async createPost(postData: { content: string; images?: string[]; privacy: string }) {
    return apiClient.post(`${this.baseUrl}/posts`, postData);
  }

  async likePost(postId: string) {
    return apiClient.post(`${this.baseUrl}/posts/${postId}/like`);
  }

  async unlikePost(postId: string) {
    return apiClient.delete(`${this.baseUrl}/posts/${postId}/like`);
  }

  async addComment(postId: string, comment: { content: string }) {
    return apiClient.post(`${this.baseUrl}/posts/${postId}/comments`, comment);
  }

  async deleteComment(postId: string, commentId: string) {
    return apiClient.delete(`${this.baseUrl}/posts/${postId}/comments/${commentId}`);
  }

  async sharePost(postId: string, content?: string) {
    return apiClient.post(`${this.baseUrl}/posts/${postId}/share`, { content });
  }

  async uploadImages(files: File[]) {
    const formData = new FormData();
    files.forEach(file => { formData.append('images', file); });
    return apiClient.post(`${this.baseUrl}/upload`, formData);
  }

  async deletePost(postId: string) {
    return apiClient.delete(`${this.baseUrl}/posts/${postId}`);
  }
}
