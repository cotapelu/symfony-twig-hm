import type { CompareItem, CompareList, CompareState } from '../contracts/compareTypes';

const API_BASE = '/api/compare';

export class CompareService {
  async getHistory(): Promise<CompareList[]> {
    const response = await fetch(`${API_BASE}/history`);
    if (!response.ok) throw new Error('Failed to fetch history');
    return response.json();
  }

  async addItem(item: CompareItem): Promise<CompareItem[]> {
    const response = await fetch(`${API_BASE}/items`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(item)
    });
    if (!response.ok) throw new Error('Failed to add item');
    return response.json();
  }

  async removeItem(itemId: string): Promise<CompareItem[]> {
    const response = await fetch(`${API_BASE}/items/${itemId}`, { method: 'DELETE' });
    if (!response.ok) throw new Error('Failed to remove item');
    return response.json();
  }

  async clearCompare(): Promise<void> {
    const response = await fetch(`${API_BASE}/items`, { method: 'DELETE' });
    if (!response.ok) throw new Error('Failed to clear compare');
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const compareService = new CompareService();
export default compareService;
