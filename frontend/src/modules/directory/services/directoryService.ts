import type { DirectoryEntry, DirectoryState } from '../contracts/directoryTypes';

const API_BASE = '/api/directory';

export class DirectoryService {
  async getEntries(): Promise<DirectoryEntry[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch directory');
    return response.json();
  }
}

export const directoryService = new DirectoryService();
export default directoryService;
