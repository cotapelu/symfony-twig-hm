export interface FileItem {
  id: string;
  name: string;
  type: string;
  size: number;
  uploaded: Date;
  url?: string;
}

export interface Folder {
  id: string;
  name: string;
  parentId: string | null;
  created: Date;
}

export interface UploadResponse {
  success: boolean;
  file?: FileItem;
  message?: string;
}

export interface FileResponse {
  success: boolean;
  files?: FileItem[];
  folders?: Folder[];
  message?: string;
}