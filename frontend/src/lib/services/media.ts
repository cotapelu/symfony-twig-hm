// Media/Upload Service - Connects to PHP Media + Zig modules
import { getApiUrl } from '../config/api';

export interface MediaFile {
  id: string;
  name: string;
  url: string;
  type: string;
  size: number;
  uploadedAt: string;
}

export interface UploadProgress {
  loaded: number;
  total: number;
  percentage: number;
}

class MediaService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async uploadFile(file: File, onProgress?: (progress: UploadProgress) => void): Promise<MediaFile> {
    const url = getApiUrl('media', '/api/media/upload');
    
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      const formData = new FormData();
      formData.append('file', file);

      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable && onProgress) {
          onProgress({
            loaded: e.loaded,
            total: e.total,
            percentage: Math.round((e.loaded / e.total) * 100)
          });
        }
      });

      xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve(JSON.parse(xhr.responseText));
        } else {
          reject(new Error('Upload failed'));
        }
      });

      xhr.addEventListener('error', () => reject(new Error('Upload failed')));
      
      xhr.open('POST', url);
      const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
      if (token) xhr.setRequestHeader('Authorization', `Bearer ${token}`);
      xhr.send(formData);
    });
  }

  async getMyFiles(): Promise<MediaFile[]> {
    const url = getApiUrl('media', '/api/media/my-files');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch files');
    const data = await response.json();
    return data.files || [];
  }

  async deleteFile(id: string): Promise<void> {
    const url = getApiUrl('media', `/api/media/${id}`);
    const response = await fetch(url, {
      method: 'DELETE',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to delete file');
  }
}

export const mediaService = new MediaService();
