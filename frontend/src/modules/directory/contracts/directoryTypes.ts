export interface DirectoryEntry {
  id: string;
  name: string;
  category: string;
  phone?: string;
  email?: string;
  address?: string;
  website?: string;
  imageUrl?: string;
}

export interface DirectoryState {
  entries: DirectoryEntry[];
  loading: boolean;
  error: string | null;
}
