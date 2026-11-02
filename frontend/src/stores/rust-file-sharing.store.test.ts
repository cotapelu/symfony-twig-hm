import { describe, it, expect, vi, beforeEach } from 'vitest';
import { rustFileSharingStore } from './rust-stores';
import { Rust } from '../lib/api';

vi.mock('../lib/api', () => ({
  Rust: {
    uploadFile: vi.fn(),
    downloadFile: vi.fn(),
    shareFile: vi.fn(),
    getSharedFiles: vi.fn(),
    stopSharing: vi.fn(),
  }
}));

describe('RustFileSharingStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    rustFileSharingStore.reset();
    rustFileSharingStore.subscribe(s => { state = s; });
  });

  const mockFile = (id: string): any => ({ id, name: `file${id}` });

  describe('uploadFile', () => {
    it('should upload and add to sharedFiles', async () => {
      const file = mockFile('1');
      (Rust.uploadFile as any).mockResolvedValue(file);
      const input = new File(['data'], 'upload.txt', { type: 'text/plain' });

      const result = await rustFileSharingStore.uploadFile(input);

      expect(result).toEqual(file);
      expect(state.mySharedFiles).toContain(file);
      expect(state.uploading).toBe(false);
    });

    it('should handle error', async () => {
      (Rust.uploadFile as any).mockRejectedValue(new Error('Upload failed'));
      const input = new File(['data'], 'upload.txt');
      await expect(rustFileSharingStore.uploadFile(input)).rejects.toThrow('Upload failed');
      expect(state.error).toBe('Upload failed');
    });
  });

  describe('downloadFile', () => {
    it('should download file', async () => {
      const blob = new Blob(['downloaded']);
      (Rust.downloadFile as any).mockResolvedValue(blob);

      const result = await rustFileSharingStore.downloadFile('file1');

      expect(result).toBe(blob);
    });

    it('should handle error', async () => {
      (Rust.downloadFile as any).mockRejectedValue(new Error('Download failed'));
      await expect(rustFileSharingStore.downloadFile('file1')).rejects.toThrow('Download failed');
      expect(state.error).toBe('Download failed');
    });
  });

  describe('shareFile', () => {
    it('should share file and update sharedFiles', async () => {
      const share = { id: 'share1', fileId: 'f1' };
      (Rust.shareFile as any).mockResolvedValue(share);
      state.sharedFiles = [mockFile('f1')];

      const result = await rustFileSharingStore.shareFile('f1', 'user2');

      expect(result).toEqual(share);
      expect(state.sharedFiles).toContain(share);
    });

    it('should handle error', async () => {
      (Rust.shareFile as any).mockRejectedValue(new Error('Share failed'));
      await expect(rustFileSharingStore.shareFile('f1', 'user2')).rejects.toThrow('Share failed');
      expect(state.error).toBe('Share failed');
    });
  });

  describe('getSharedFiles', () => {
    it('should fetch shared files', async () => {
      const files = [mockFile('1'), mockFile('2')];
      (Rust.getSharedFiles as any).mockResolvedValue(files);

      const result = await rustFileSharingStore.getSharedFiles();

      expect(result).toEqual(files);
      expect(state.sharedFiles).toEqual(files);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Rust.getSharedFiles as any).mockRejectedValue(new Error('Fetch failed'));
      await expect(rustFileSharingStore.getSharedFiles()).rejects.toThrow('Fetch failed');
      expect(state.error).toBe('Fetch failed');
    });
  });

  describe('stopSharing', () => {
    it('should remove shared file from list', async () => {
      state.sharedFiles = [mockFile('1'), mockFile('2')];
      (Rust.stopSharing as any).mockResolvedValue(undefined);

      await rustFileSharingStore.stopSharing('1');

      expect(state.sharedFiles).toHaveLength(1);
      expect(state.sharedFiles[0].id).toBe('2');
    });

    it('should handle error', async () => {
      state.sharedFiles = [mockFile('1')];
      (Rust.stopSharing as any).mockRejectedValue(new Error('Stop failed'));
      await expect(rustFileSharingStore.stopSharing('1')).rejects.toThrow('Stop failed');
      expect(state.error).toBe('Stop failed');
      expect(state.sharedFiles).toHaveLength(1);
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.sharedFiles = [mockFile('1')];
      state.mySharedFiles = [mockFile('2')];
      state.downloadCount = 5;
      state.peerCount = 3;
      state.uploading = true;
      state.error = 'err';
      rustFileSharingStore.reset();
      expect(state.sharedFiles).toEqual([]);
      expect(state.mySharedFiles).toEqual([]);
      expect(state.downloadCount).toBe(0);
      expect(state.peerCount).toBe(0);
      expect(state.loading).toBe(false);
      expect(state.uploading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Rust.uploadFile as any).mockRejectedValue(new Error('test'));
      try { await rustFileSharingStore.uploadFile(new File(['a'], 'a')); } catch (e) {}
      expect(state.error).toBe('test');
      rustFileSharingStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
