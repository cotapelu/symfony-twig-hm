import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mediaService } from './media';
import { getApiUrl } from '../config/api';

vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('MediaService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getMyFiles', () => {
    it('fetches user files', async () => {
      const files = [{ id: 'f1', name: 'doc.pdf', url: '/f1', type: 'pdf', size: 1234, uploadedAt: '2024-01-01' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ files }) });

      const result = await mediaService.getMyFiles();

      expect(getApiUrl).toHaveBeenCalledWith('media', '/api/media/my-files');
      expect(result).toEqual(files);
    });

    it('returns empty array if no files', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await mediaService.getMyFiles();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(mediaService.getMyFiles()).rejects.toThrow('Failed to fetch files');
    });
  });

  describe('deleteFile', () => {
    it('deletes a file by id', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      await mediaService.deleteFile('f1');

      expect(getApiUrl).toHaveBeenCalledWith('media', '/api/media/f1');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'DELETE',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(mediaService.deleteFile('f1')).rejects.toThrow('Failed to delete file');
    });
  });

  describe('uploadFile', () => {
    let xhrInstance: any;
    let loadListeners: (() => void)[] = [];
    let errorListeners: (() => void)[] = [];
    let progressListeners: ((e: any) => void)[] = [];

    beforeEach(() => {
      xhrInstance = {
        upload: {
          addEventListener: vi.fn((event: string, cb: any) => {
            if (event === 'progress') progressListeners.push(cb);
          }),
        },
        open: vi.fn(),
        send: vi.fn(),
        setRequestHeader: vi.fn(),
        status: 200,
        responseText: JSON.stringify({ id: 'f1', name: 'upload.pdf', url: '/f1', type: 'pdf', size: 1234, uploadedAt: '2024-01-01' }),
        addEventListener: vi.fn((event: string, cb: any) => {
          if (event === 'load') loadListeners.push(cb);
          if (event === 'error') errorListeners.push(cb);
        }),
      };
      global.XMLHttpRequest = vi.fn(() => xhrInstance) as any;
      loadListeners = [];
      errorListeners = [];
      progressListeners = [];
    });

    afterEach(() => {
      delete global.XMLHttpRequest;
    });

    function simulateProgress(loaded: number, total: number) {
      if (progressListeners.length) {
        progressListeners[0]({ lengthComputable: true, loaded, total });
      }
    }

    function simulateLoad() {
      if (loadListeners.length) {
        loadListeners[0]();
      }
    }

    function simulateError() {
      if (errorListeners.length) {
        errorListeners[0]();
      }
    }

    it('uploads file and returns MediaFile', async () => {
      const promise = mediaService.uploadFile(new File([''], 'test.pdf'));
      simulateLoad();
      const result = await promise;
      expect(result).toEqual({ id: 'f1', name: 'upload.pdf', url: '/f1', type: 'pdf', size: 1234, uploadedAt: '2024-01-01' });
      expect(xhrInstance.open).toHaveBeenCalledWith('POST', '/');
      expect(xhrInstance.send).toHaveBeenCalled();
    });

    it('calls onProgress during upload', async () => {
      let receivedProgress: any = null;
      const promise = mediaService.uploadFile(new File([''], 'test.pdf'), (progress) => {
        receivedProgress = progress;
      });
      simulateProgress(50, 100);
      expect(receivedProgress).toEqual({ loaded: 50, total: 100, percentage: 50 });
      simulateLoad();
      await promise;
    });

    it('rejects on XHR error', async () => {
      const promise = mediaService.uploadFile(new File([''], 'test.pdf'));
      simulateError();
      await expect(promise).rejects.toThrow('Upload failed');
    });

    it('rejects on non-2xx status', async () => {
      xhrInstance.status = 500;
      xhrInstance.responseText = JSON.stringify({ error: 'fail' });
      const promise = mediaService.uploadFile(new File([''], 'test.pdf'));
      simulateLoad();
      await expect(promise).rejects.toThrow('Upload failed');
    });
  });
});
