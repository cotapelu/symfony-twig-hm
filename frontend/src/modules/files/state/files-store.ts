import { writable, derived, get } from 'svelte/store';

interface FileItem {
  id: string;
  name: string;
  type: string;
  size: number;
  uploaded: Date;
  url?: string;
  ownerId: string;
}

interface FileState {
  files: FileItem[];
  folders: Folder[];
  currentFolder: string | null;
  loading: boolean;
  error: string | null;
  uploadProgress: { [key: string]: number };
  currentUserId: string | null;
}

interface Folder {
  id: string;
  name: string;
  parentId: string | null;
  created: Date;
}

function createFileStore() {
  const initialState: FileState = {
    files: [],
    folders: [],
    currentFolder: null,
    loading: false,
    error: null,
    uploadProgress: {},
    currentUserId: null
  };
  const { subscribe, set, update } = writable<FileState>(initialState);

  return {
    subscribe,

    setCurrentUser(userId: string) {
      update(state => ({ ...state, currentUserId: userId }));
    },

    uploadFile: async (file: File, folderId?: string) => {
      const fileId = Date.now().toString();
      
      update(state => ({
        ...state,
        uploadProgress: { ...state.uploadProgress, [fileId]: 0 }
      }));

      const formData = new FormData();
      formData.append('file', file);
      if (folderId) formData.append('folderId', folderId);

      try {
        const response = await fetch('/api/files/upload', {
          method: 'POST',
          body: formData
        });

        if (!response.ok) {
          throw new Error('Upload failed');
        }

        const uploadedFile = await response.json();
        
        update(state => ({
          ...state,
          files: [...state.files, uploadedFile],
          uploadProgress: { ...state.uploadProgress, [fileId]: 100 }
        }));

        return uploadedFile;
      } catch (error) {
        update(state => ({
          ...state,
          error: (error as Error).message,
          uploadProgress: { ...state.uploadProgress, [fileId]: 0 }
        }));
        throw error;
      }
    },

    loadFiles: async (folderId?: string) => {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const state = get({ subscribe });
        const url = state.currentUserId 
          ? (folderId ? `/api/files/my/folder/${folderId}` : '/api/files/my')
          : (folderId ? `/api/files/folder/${folderId}` : '/api/files');
          
        const response = await fetch(url);
        
        if (!response.ok) {
          throw new Error('Failed to load files');
        }

        const { files, folders } = await response.json();
        
        update(state => ({
          ...state,
          files,
          folders,
          currentFolder: folderId || null,
          loading: false
        }));
      } catch (error) {
        update(state => ({
          ...state,
          loading: false,
          error: (error as Error).message
        }));
      }
    },

    loadMyFiles: async (folderId?: string) => {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const url = folderId 
          ? `/api/files/my/folder/${folderId}` 
          : '/api/files/my';
          
        const response = await fetch(url);
        
        if (!response.ok) {
          throw new Error('Failed to load my files');
        }

        const { files, folders } = await response.json();
        
        update(state => ({
          ...state,
          files,
          folders,
          currentFolder: folderId || null,
          loading: false
        }));
      } catch (error) {
        update(state => ({
          ...state,
          loading: false,
          error: (error as Error).message
        }));
      }
    },

    async getFile(fileId: string) {
      const state = get({ subscribe });
      const file = state.files.find(f => f.id === fileId);
      
      if (!file) {
        const response = await fetch(`/api/files/${fileId}`);
        if (!response.ok) throw new Error('File not found');
        return await response.json();
      }
      
      if (state.currentUserId && file.ownerId !== state.currentUserId) {
        throw new Error('Unauthorized: You can only access your own files');
      }
      
      return file;
    },

    deleteFile: async (fileId: string) => {
      const state = get({ subscribe });
      const file = state.files.find(f => f.id === fileId);
      
      if (file && state.currentUserId && file.ownerId !== state.currentUserId) {
        throw new Error('Unauthorized: You can only delete your own files');
      }
      
      try {
        const response = await fetch(`/api/files/${fileId}`, {
          method: 'DELETE'
        });

        if (!response.ok) {
          throw new Error('Failed to delete file');
        }

        update(state => ({
          ...state,
          files: state.files.filter(f => f.id !== fileId)
        }));
      } catch (error) {
        update(state => ({
          ...state,
          error: (error as Error).message
        }));
      }
    },
    reset: () => set(initialState)
  };
}

export const fileStore = createFileStore();

export const currentFiles = derived(
  fileStore,
  $fileStore => $fileStore.files
);

export const currentFolders = derived(
  fileStore,
  $fileStore => $fileStore.folders
);