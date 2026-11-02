import { describe, it, expect, vi, beforeEach } from 'vitest';
import { SocialService } from './socialService';
import { apiClient } from '../../../appcore/api-client';

vi.mock('../../../appcore/api-client', () => ({
  apiClient: { get: vi.fn(), post: vi.fn(), delete: vi.fn() }
}));

describe('SocialService', () => {
  let service: SocialService;

  beforeEach(() => {
    service = new SocialService();
    vi.clearAllMocks();
  });

  describe('getPosts', () => {
    it('should fetch posts', async () => {
      (apiClient.get as any).mockResolvedValue([{ id: 'p1' }]);
      const result = await service.getPosts();
      expect(result).toEqual([{ id: 'p1' }]);
      expect(apiClient.get).toHaveBeenCalledWith('/api/social/posts');
    });

    it('should propagate errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Fail'));
      await expect(service.getPosts()).rejects.toThrow('Fail');
    });
  });

  describe('createPost', () => {
    it('should create a post', async () => {
      (apiClient.post as any).mockResolvedValue({ id: 'new' });
      const postData = { content: 'Hello', privacy: 'public' };
      const result = await service.createPost(postData);
      expect(result).toEqual({ id: 'new' });
      expect(apiClient.post).toHaveBeenCalledWith('/api/social/posts', postData);
    });

    it('should include optional images', async () => {
      (apiClient.post as any).mockResolvedValue({});
      const postData = { content: 'Img', images: ['url1'], privacy: 'friends' };
      await service.createPost(postData);
      expect(apiClient.post).toHaveBeenCalledWith('/api/social/posts', postData);
    });

    it('should propagate errors', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Bad'));
      await expect(service.createPost({ content: '', privacy: '' } as any)).rejects.toThrow('Bad');
    });
  });

  describe('likePost', () => {
    it('should like a post', async () => {
      (apiClient.post as any).mockResolvedValue({ success: true });
      await service.likePost('p1');
      expect(apiClient.post).toHaveBeenCalledWith('/api/social/posts/p1/like');
    });

    it('should propagate errors', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Already liked'));
      await expect(service.likePost('p1')).rejects.toThrow('Already liked');
    });
  });

  describe('unlikePost', () => {
    it('should unlike a post', async () => {
      (apiClient.delete as any).mockResolvedValue({ ok: true });
      await service.unlikePost('p1');
      expect(apiClient.delete).toHaveBeenCalledWith('/api/social/posts/p1/like');
    });

    it('should propagate errors', async () => {
      (apiClient.delete as any).mockRejectedValue(new Error('Not liked'));
      await expect(service.unlikePost('p1')).rejects.toThrow('Not liked');
    });
  });

  describe('addComment', () => {
    it('should add comment to post', async () => {
      (apiClient.post as any).mockResolvedValue({ id: 'c1' });
      const comment = { content: 'Nice!' };
      await service.addComment('p1', comment);
      expect(apiClient.post).toHaveBeenCalledWith('/api/social/posts/p1/comments', comment);
    });

    it('should propagate errors', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Failed'));
      await expect(service.addComment('p1', { content: '' })).rejects.toThrow('Failed');
    });
  });

  describe('deleteComment', () => {
    it('should delete comment', async () => {
      (apiClient.delete as any).mockResolvedValue({ ok: true });
      await service.deleteComment('p1', 'c1');
      expect(apiClient.delete).toHaveBeenCalledWith('/api/social/posts/p1/comments/c1');
    });

    it('should propagate errors', async () => {
      (apiClient.delete as any).mockRejectedValue(new Error('No perm'));
      await expect(service.deleteComment('p1', 'c1')).rejects.toThrow('No perm');
    });
  });

  describe('sharePost', () => {
    it('should share post with optional content', async () => {
      (apiClient.post as any).mockResolvedValue({});
      await service.sharePost('p1', 'Check this out');
      expect(apiClient.post).toHaveBeenCalledWith('/api/social/posts/p1/share', { content: 'Check this out' });
    });

    it('should share post without content', async () => {
      (apiClient.post as any).mockResolvedValue({});
      await service.sharePost('p1');
      expect(apiClient.post).toHaveBeenCalledWith('/api/social/posts/p1/share', { content: undefined });
    });

    it('should propagate errors', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Err'));
      await expect(service.sharePost('p1')).rejects.toThrow('Err');
    });
  });

  describe('uploadImages', () => {
    it('should upload images with FormData', async () => {
      (apiClient.post as any).mockResolvedValue({ urls: ['u1'] });
      const files = [new File([''], 'img1.png'), new File([''], 'img2.png')];
      const result = await service.uploadImages(files);
      expect(result).toEqual({ urls: ['u1'] });
      expect(apiClient.post).toHaveBeenCalledWith(
        '/api/social/upload',
        expect.any(FormData)
      );
      const formArg = (apiClient.post as any).mock.calls[0][1];
      expect(formArg instanceof FormData).toBe(true);
    });

    it('should propagate errors', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Upload fail'));
      const files = [new File([''], 'x.jpg')];
      await expect(service.uploadImages(files)).rejects.toThrow('Upload fail');
    });
  });

  describe('deletePost', () => {
    it('should delete a post', async () => {
      (apiClient.delete as any).mockResolvedValue({ ok: true });
      await service.deletePost('p1');
      expect(apiClient.delete).toHaveBeenCalledWith('/api/social/posts/p1');
    });

    it('should propagate errors', async () => {
      (apiClient.delete as any).mockRejectedValue(new Error('No perm'));
      await expect(service.deletePost('p1')).rejects.toThrow('No perm');
    });
  });
});
