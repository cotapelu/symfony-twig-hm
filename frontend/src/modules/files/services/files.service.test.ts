import { describe, it, expect, vi, beforeEach } from 'vitest';
import { FileService } from './files.service';

// Mock fileStore
vi.mock('../state/files-store', () => ({
  fileStore: {
    uploadFile: vi.fn(),
    loadFiles: vi.fn(),
    loadMyFiles: vi.fn(),
    getFile: vi.fn(),
    deleteFile: vi.fn()
  }
}));

import { fileStore } from '../state/files-store';

global.fetch = vi.fn();

describe('FileService', () => {
  let service: FileService;

  beforeEach(() => {
    service = new FileService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  const mockBlob = () => new Blob(['content'], { type: 'text/plain' });

  describe('uploadFile', () => {
    it('should upload file with folder', async () => {
      const file = new File([''], 'test.txt');
      const result = { id: 'f1' };
      fileStore.uploadFile.mockResolvedValue(result);
      const res = await service.uploadFile(file, 'folder1');
      expect(res).toEqual(result);
      expect(fileStore.uploadFile).toHaveBeenCalledWith(file, 'folder1');
    });

    it('should upload file without folder', async () => {
      const file = new File([''], 'test.txt');
      const result = { id: 'f2' };
      fileStore.uploadFile.mockResolvedValue(result);
      const res = await service.uploadFile(file);
      expect(res).toEqual(result);
      expect(fileStore.uploadFile).toHaveBeenCalledWith(file, undefined);
    });

    it('should propagate errors', async () => {
      fileStore.uploadFile.mockRejectedValue(new Error('Upload failed'));
      const file = new File([''], 'x.txt');
      await expect(service.uploadFile(file)).rejects.toThrow('Upload failed');
    });
  });

  describe('loadFiles', () => {
    it('should load files for a folder', async () => {
      const files = [{ id: 'f1' }];
      fileStore.loadFiles.mockResolvedValue(files);
      const result = await service.loadFiles('folder1');
      expect(result).toEqual(files);
      expect(fileStore.loadFiles).toHaveBeenCalledWith('folder1');
    });

    it('should load all files when no folder', async () => {
      const files = [{ id: 'f1' }];
      fileStore.loadFiles.mockResolvedValue(files);
      const result = await service.loadFiles();
      expect(result).toEqual(files);
      expect(fileStore.loadFiles).toHaveBeenCalledWith(undefined);
    });

    it('should propagate errors', async () => {
      fileStore.loadFiles.mockRejectedValue(new Error('Load failed'));
      await expect(service.loadFiles()).rejects.toThrow('Load failed');
    });
  });

  describe('loadMyFiles', () => {
    it('should load my files for a folder', async () => {
      const files = [{ id: 'f1' }];
      fileStore.loadMyFiles.mockResolvedValue(files);
      const result = await service.loadMyFiles('folder1');
      expect(result).toEqual(files);
      expect(fileStore.loadMyFiles).toHaveBeenCalledWith('folder1');
    });

    it('should propagate errors', async () => {
      fileStore.loadMyFiles.mockRejectedValue(new Error('Fail'));
      await expect(service.loadMyFiles()).rejects.toThrow('Fail');
    });
  });

  describe('getFile', () => {
    it('should get file by id', async () => {
      const file = { id: 'f1' };
      fileStore.getFile.mockResolvedValue(file);
      const result = await service.getFile('f1');
      expect(result).toEqual(file);
      expect(fileStore.getFile).toHaveBeenCalledWith('f1');
    });

    it('should propagate errors', async () => {
      fileStore.getFile.mockRejectedValue(new Error('Not found'));
      await expect(service.getFile('f1')).rejects.toThrow('Not found');
    });
  });

  describe('deleteFile', () => {
    it('should delete file', async () => {
      fileStore.deleteFile.mockResolvedValue(undefined);
      await service.deleteFile('f1');
      expect(fileStore.deleteFile).toHaveBeenCalledWith('f1');
    });

    it('should propagate errors', async () => {
      fileStore.deleteFile.mockRejectedValue(new Error('Delete failed'));
      await expect(service.deleteFile('f1')).rejects.toThrow('Delete failed');
    });
  });

  describe('createFolder', () => {
    it('should create a folder with name and parent', async () => {
      const folder = { id: 'folder1', name: 'New Folder', parentId: 'parent1' };
      (global.fetch as any).mockResolvedValue(mockResponse(folder));
      const result = await service.createFolder('New Folder', 'parent1');
      expect(result).toEqual(folder);
      expect(fetch).toHaveBeenCalledWith('/api/files/folders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: 'New Folder', parentId: 'parent1' })
      });
    });

    it('should create a folder without parent', async () => {
      const folder = { id: 'folder2', name: 'Solo' };
      (global.fetch as any).mockResolvedValue(mockResponse(folder));
      const result = await service.createFolder('Solo');
      expect(result).toEqual(folder);
      expect(fetch).toHaveBeenCalledWith('/api/files/folders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: 'Solo', parentId: undefined })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.createFolder('Fail')).rejects.toThrow('Failed to create folder');
    });
  });

  describe('downloadFile', () => {
    it('should download file as blob', async () => {
      const blob = mockBlob();
      (global.fetch as any).mockResolvedValue({ ok: true, blob: () => Promise.resolve(blob) });
      const result = await service.downloadFile('f1');
      expect(result).toBe(blob);
      expect(fetch).toHaveBeenCalledWith('/api/files/f1/download');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.downloadFile('f1')).rejects.toThrow('Failed to download file');
    });
  });

  describe('shareFile', () => {
    it('should share file with permissions', async () => {
      const result = { id: 'share1' };
      (global.fetch as any).mockResolvedValue(mockResponse(result));
      const permissions = ['read', 'write'];
      const res = await service.shareFile('f1', permissions);
      expect(res).toEqual(result);
      expect(fetch).toHaveBeenCalledWith('/api/files/f1/share', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ permissions })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.shareFile('f1', ['read'])).rejects.toThrow('Failed to share file');
    });
  });

  describe('verifyOwnership', () => {
    it('should return true when owner matches', async () => {
      const file = { ownerId: 'u1' };
      fileStore.getFile.mockResolvedValue(file);
      const result = await service.verifyOwnership('f1', 'u1');
      expect(result).toBe(true);
      expect(fileStore.getFile).toHaveBeenCalledWith('f1');
    });

    it('should return false when owner does not match', async () => {
      const file = { ownerId: 'u2' };
      fileStore.getFile.mockResolvedValue(file);
      const result = await service.verifyOwnership('f1', 'u1');
      expect(result).toBe(false);
    });

    it('should propagate errors from getFile', async () => {
      fileStore.getFile.mockRejectedValue(new Error('Not found'));
      await expect(service.verifyOwnership('f1', 'u1')).rejects.toThrow('Not found');
    });
  });
});
