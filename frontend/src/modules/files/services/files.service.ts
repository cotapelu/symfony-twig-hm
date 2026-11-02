import { fileStore } from '../state/files-store';

export class FileService {
  async uploadFile(file: File, folderId?: string): Promise<any> {
    return await fileStore.uploadFile(file, folderId);
  }

  async loadFiles(folderId?: string): Promise<any> {
    return await fileStore.loadFiles(folderId);
  }

  async loadMyFiles(folderId?: string): Promise<any> {
    return await fileStore.loadMyFiles(folderId);
  }

  async getFile(fileId: string): Promise<any> {
    return await fileStore.getFile(fileId);
  }

  async deleteFile(fileId: string): Promise<void> {
    return await fileStore.deleteFile(fileId);
  }

  async createFolder(name: string, parentId?: string): Promise<any> {
    try {
      const response = await fetch('/api/files/folders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, parentId })
      });

      if (!response.ok) {
        throw new Error('Failed to create folder');
      }

      return await response.json();
    } catch (error) {
      throw error;
    }
  }

  async downloadFile(fileId: string): Promise<Blob> {
    try {
      const response = await fetch(`/api/files/${fileId}/download`);
      
      if (!response.ok) {
        throw new Error('Failed to download file');
      }

      return await response.blob();
    } catch (error) {
      throw error;
    }
  }

  async shareFile(fileId: string, permissions: string[]): Promise<any> {
    try {
      const response = await fetch(`/api/files/${fileId}/share`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ permissions })
      });

      if (!response.ok) {
        throw new Error('Failed to share file');
      }

      return await response.json();
    } catch (error) {
      throw error;
    }
  }

  async verifyOwnership(fileId: string, userId: string): Promise<boolean> {
    const file = await this.getFile(fileId);
    return file.ownerId === userId;
  }
}

export const fileService = new FileService();