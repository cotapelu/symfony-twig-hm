import { writable, derived, get } from 'svelte/store';

export interface File {
  id: string;
  name: string;
  type: string;
  size: number;
  url: string;
  thumbnail?: string;
  uploadedAt: Date;
  ownerId: string;
  folder?: string;
}

export interface FilesState {
  files: File[];
  currentFolder: string | null;
  loading: boolean;
  error: string | null;
  uploadProgress: Record<string, number>;
  currentUserId: string | null;
}

export const filesStore = writable<FilesState>({
  files: [],
  currentFolder: null,
  loading: false,
  error: null,
  uploadProgress: {},
  currentUserId: null
});

export const files = derived(filesStore, $state => $state.files);
export const currentFolder = derived(filesStore, $state => $state.currentFolder);
export const loading = derived(filesStore, $state => $state.loading);
export const error = derived(filesStore, $state => $state.error);
export const uploadProgress = derived(filesStore, $state => $state.uploadProgress);

export function setCurrentUser(userId: string) {
  filesStore.update(s => ({ ...s, currentUserId: userId }));
}

export function canAccessFile(file: File): boolean {
  const state = get(filesStore);
  return !state.currentUserId || file.ownerId === state.currentUserId;
}

export function canModifyFile(file: File): boolean {
  const state = get(filesStore);
  return state.currentUserId === file.ownerId;
}