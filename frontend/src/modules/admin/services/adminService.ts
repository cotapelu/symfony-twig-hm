import type {
  AdminUser,
  AdminStats,
  SystemLog,
  AdminDocument,
  PopulationRecord,
  UpdateDocumentRequest,
  CreateAdminUserRequest,
  AdminState
} from '../contracts/adminTypes';

const API_BASE = '/api/admin';

export class AdminService {
  async getStats(): Promise<AdminStats> {
    const response = await fetch(`${API_BASE}/stats`);
    if (!response.ok) throw new Error('Failed to fetch stats');
    return response.json();
  }

  async getUsers(): Promise<AdminUser[]> {
    const response = await fetch(`${API_BASE}/users`);
    if (!response.ok) throw new Error('Failed to fetch users');
    return response.json();
  }

  async getUser(id: string): Promise<AdminUser> {
    const response = await fetch(`${API_BASE}/users/${id}`);
    if (!response.ok) throw new Error('Failed to fetch user');
    return response.json();
  }

  async createUser(request: CreateAdminUserRequest): Promise<AdminUser> {
    const response = await fetch(`${API_BASE}/users`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create user');
    return response.json();
  }

  async updateUserStatus(userId: string, status: AdminUser['status']): Promise<AdminUser> {
    const response = await fetch(`${API_BASE}/users/${userId}/status`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status })
    });
    if (!response.ok) throw new Error('Failed to update user status');
    return response.json();
  }

  async getDocuments(): Promise<AdminDocument[]> {
    const response = await fetch(`${API_BASE}/documents`);
    if (!response.ok) throw new Error('Failed to fetch documents');
    return response.json();
  }

  async updateDocument(request: UpdateDocumentRequest): Promise<AdminDocument> {
    const response = await fetch(`${API_BASE}/documents/${request.documentId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: request.status, notes: request.notes })
    });
    if (!response.ok) throw new Error('Failed to update document');
    return response.json();
  }

  async getPopulation(page = 1, limit = 50): Promise<{ data: PopulationRecord[]; total: number }> {
    const response = await fetch(`${API_BASE}/population?page=${page}&limit=${limit}`);
    if (!response.ok) throw new Error('Failed to fetch population');
    return response.json();
  }

  async searchPopulation(query: string): Promise<PopulationRecord[]> {
    const response = await fetch(`${API_BASE}/population/search?q=${encodeURIComponent(query)}`);
    if (!response.ok) throw new Error('Failed to search population');
    return response.json();
  }

  async getLogs(page = 1, limit = 100): Promise<{ data: SystemLog[]; total: number }> {
    const response = await fetch(`${API_BASE}/logs?page=${page}&limit=${limit}`);
    if (!response.ok) throw new Error('Failed to fetch logs');
    return response.json();
  }
}

export const adminService = new AdminService();
export default adminService;

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};
