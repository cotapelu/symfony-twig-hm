import { describe, it, expect, vi, beforeEach } from 'vitest';
import { filesStore } from './files';
import type { FileItem } from './files';
import { PHP } from '../lib/api';

// Mock the PHP API
vi.mock('../lib/api', () => ({
  PHP: {
    getFiles: vi.fn(),
    uploadFile: vi.fn(),
    deleteFile: vi.fn(),
  }
}));

describe('FilesStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    filesStore.reset();
    // Subscribe to capture state
    filesStore.subscribe(s => { state = s; });
  });

  const mockFile = (id: string, name: string): FileItem => ({
    id,
    name,
    size: 1024,
    type: 'image/jpeg',
    url: `http://example.com/${name}`,
    uploadedAt: new Date().toISOString()
  });

  describe('loadFiles', () => {
    it('should load files successfully', async () => {
      const files = [mockFile('f1', 'a.jpg'), mockFile('f2', 'b.jpg')];
      (PHP.getFiles as any).mockResolvedValue(files);

      const result = await filesStore.loadFiles();

      expect(result).toEqual(files);
      expect(state.files).toEqual(files);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(PHP.getFiles).toHaveBeenCalledTimes(1);
    });

    it('should handle empty list', async () => {
      (PHP.getFiles as any).mockResolvedValue([]);

      await filesStore.loadFiles();

      expect(state.files).toEqual([]);
    });

    it('should set error on failure', async () => {
      const error = new Error('Network');
      (PHP.getFiles as any).mockRejectedValue(error);

      await expect(filesStore.loadFiles()).rejects.toThrow('Network');

      expect(state.loading).toBe(false);
      expect(state.error).toBe('Network');
    });
  });

  describe('uploadFile', () => {
    it('should upload file and add to store', async () => {
      const file = new File(['content'], 'upload.jpg', { type: 'image/jpeg' });
      const uploaded = mockFile('f1', 'upload.jpg');
      (PHP.uploadFile as any).mockResolvedValue(uploaded);

      await filesStore.uploadFile(file);

      expect(PHP.uploadFile).toHaveBeenCalledWith(file);
      expect(state.files).toContain(uploaded);
      expect(state.uploading).toBe(false);
      expect(state.uploadProgress).toBe(100);
    });

    it('should handle upload error', async () => {
      const file = new File(['content'], 'fail.jpg');
      const error = new Error('Upload failed');
      (PHP.uploadFile as any).mockRejectedValue(error);

      await expect(filesStore.uploadFile(file)).rejects.toThrow('Upload failed');

      expect(state.uploading).toBe(false);
      expect(state.error).toBe('Upload failed');
    });
  });

  describe('deleteFile', () => {
    it('should delete file from server and store', async () => {
      const file = mockFile('f1', 'a.jpg');
      filesStore.addFile(file);
      expect(state.files).toHaveLength(1);

      (PHP.deleteFile as any).mockResolvedValue(undefined);

      await filesStore.deleteFile('f1');

      expect(PHP.deleteFile).toHaveBeenCalledWith('f1');
      expect(state.files).toHaveLength(0);
    });

    it('should handle delete error', async () => {
      const file = mockFile('f1', 'a.jpg');
      filesStore.addFile(file);
      const error = new Error('Delete failed');
      (PHP.deleteFile as any).mockRejectedValue(error);

      await expect(filesStore.deleteFile('f1')).rejects.toThrow('Delete failed');

      expect(state.files).toHaveLength(1); // still there
      expect(state.error).toBe('Delete failed');
    });
  });

  describe('addFile', () => {
    it('should add file locally', () => {
      const file = mockFile('f1', 'a.jpg');
      filesStore.addFile(file);

      expect(state.files).toContain(file);
      expect(state.files).toHaveLength(1);
    });
  });

  describe('removeFile', () => {
    it('should remove file locally', () => {
      const file = mockFile('f1', 'a.jpg');
      filesStore.addFile(file);
      expect(state.files).toHaveLength(1);

      filesStore.removeFile('f1');

      expect(state.files).toHaveLength(0);
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (PHP.getFiles as any).mockRejectedValue(new Error('fail'));
      try {
        await filesStore.loadFiles();
      } catch (e) {}
      expect(state.error).toBe('fail');

      filesStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
