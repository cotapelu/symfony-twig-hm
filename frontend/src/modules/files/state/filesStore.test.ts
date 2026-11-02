import { describe, it, expect, vi, beforeEach } from 'vitest';
import { filesStore, setCurrentUser, canAccessFile, canModifyFile } from './filesStore';
import type { File } from './filesStore';

function getState() {
  let current: any;
  const unsub = filesStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockFile = (overrides: Partial<File> = {}): File => ({
  id: `file-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test File',
  type: 'application/pdf',
  size: 1024,
  uploaded: new Date().toISOString(),
  url: 'http://example.com/file',
  ownerId: 'user-1',
  ...overrides
});

describe('FilesStore', () => {
  beforeEach(() => {
    // Reset store to initial state
    filesStore.set({
      files: [],
      folders: [],
      currentFolder: null,
      loading: false,
      error: null,
      uploadProgress: {},
      currentUserId: null
    });
  });

  describe('initial state', () => {
    it('has default values', () => {
      const s = getState();
      expect(s.files).toEqual([]);
      expect(s.folders).toEqual([]);
      expect(s.currentFolder).toBeNull();
      expect(s.loading).toBe(false);
      expect(s.error).toBeNull();
      expect(s.uploadProgress).toEqual({});
      expect(s.currentUserId).toBeNull();
    });
  });

  describe('setCurrentUser', () => {
    it('sets currentUserId', () => {
      setCurrentUser('user-2');
      expect(getState().currentUserId).toBe('user-2');
    });
  });

  describe('canAccessFile', () => {
    it('returns true when no current user set', () => {
      const file = mockFile({ ownerId: 'user-1' });
      expect(canAccessFile(file)).toBe(true);
    });

    it('returns true if file owner matches current user', () => {
      setCurrentUser('user-1');
      const file = mockFile({ ownerId: 'user-1' });
      expect(canAccessFile(file)).toBe(true);
    });

    it('returns false if file owner differs from current user', () => {
      setCurrentUser('user-1');
      const file = mockFile({ ownerId: 'user-2' });
      expect(canAccessFile(file)).toBe(false);
    });
  });

  describe('canModifyFile', () => {
    it('returns false when no current user set', () => {
      const file = mockFile({ ownerId: 'user-1' });
      expect(canModifyFile(file)).toBe(false);
    });

    it('returns true only if current user is owner', () => {
      setCurrentUser('user-1');
      const file = mockFile({ ownerId: 'user-1' });
      expect(canModifyFile(file)).toBe(true);
    });

    it('returns false if current user is not owner', () => {
      setCurrentUser('user-2');
      const file = mockFile({ ownerId: 'user-1' });
      expect(canModifyFile(file)).toBe(false);
    });
  });

  describe('state mutations via update', () => {
    it('allows adding a file directly via update', () => {
      const file = mockFile({ id: '1' });
      filesStore.update(state => ({
        ...state,
        files: [...state.files, file]
      }));
      expect(getState().files).toContainEqual(file);
    });

    it('allows clearing loading flag', () => {
      filesStore.update(s => ({ ...s, loading: true }));
      expect(getState().loading).toBe(true);
      filesStore.update(s => ({ ...s, loading: false }));
      expect(getState().loading).toBe(false);
    });
  });
});
