// Files Store - TypeScript Version
// PHP Media Module with type safety

import { writable } from "svelte/store";
import { PHP } from "../lib/api";
import type { FileItem } from "../lib/types";

interface FilesState {
  files: FileItem[];
  loading: boolean;
  uploading: boolean;
  uploadProgress: number;
  error: string | null;
  storageUsed?: number;
  storageTotal?: number;
}

function createFilesStore() {
  const initialState: FilesState = {
    files: [],
    loading: false,
    uploading: false,
    uploadProgress: 0,
    error: null,
  };

  const { subscribe, set, update } = writable<FilesState>(initialState);

  return {
    subscribe,

    loadFiles: async (): Promise<FileItem[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const data = await PHP.getFiles();
        const files = data || [];
        set({ ...initialState, files });
        return files;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to load files";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    uploadFile: async (file: File): Promise<FileItem> => {
      update((state) => ({
        ...state,
        uploading: true,
        uploadProgress: 0,
        error: null,
      }));
      try {
        const result = await PHP.uploadFile(file);
        update((prev) => ({
          ...prev,
          files: [result, ...prev.files],
          uploading: false,
          uploadProgress: 100,
        }));
        return result;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to upload file";
        update((state) => ({
          ...state,
          uploading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    deleteFile: async (fileId: string): Promise<void> => {
      try {
        await PHP.deleteFile(fileId);
        update((prev) => ({
          ...prev,
          files: prev.files.filter((f) => f.id !== fileId),
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to delete file";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    addFile: (file: FileItem): void => {
      update((prev) => ({
        ...prev,
        files: [file, ...prev.files],
      }));
    },

    removeFile: (fileId: string): void => {
      update((prev) => ({
        ...prev,
        files: prev.files.filter((f) => f.id !== fileId),
      }));
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset helper
    reset: (): void => {
      set({
        files: [],
        loading: false,
        uploading: false,
        uploadProgress: 0,
        error: null,
      });
    },
  };
}

export const filesStore = createFilesStore();

export const {
  loadFiles,
  uploadFile,
  deleteFile,
  addFile,
  removeFile,
  clearError,
} = filesStore;
