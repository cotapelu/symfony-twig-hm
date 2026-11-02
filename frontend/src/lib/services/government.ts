// Government Service - Connects to PHP Government module
import { getApiUrl } from '../config/api';

export interface Citizen {
  id: string;
  name: string;
  fullName?: string;
  cccd: string;
  dob: string;
  birthDate?: string;
  gender: string;
  address: string;
  ward: string;
  district: string;
  province?: string;
  phone: string;
  email?: string;
  status?: string;
  createdAt?: string;
}

export interface Officer {
  id: string;
  name: string;
  fullName?: string;
  position: string;
  department: string;
  phone: string;
  email: string;
  wards: string[];
  createdAt?: string;
}

export interface Procedure {
  id: string;
  name: string;
  code?: string;
  category: string;
  description: string;
  requirements: string[];
  processingTime: string;
  deadline?: string;
  fee: number;
  status: string;
  department?: string;
}

export interface Document {
  id: string;
  title: string;
  type: string;
  content: string;
  status: string;
  createdBy: string;
  createdAt: string;
  publishedAt?: string;
}

export interface GovernmentStats {
  totalCitizens: number;
  totalOfficers: number;
  totalProcedures: number;
  pendingDocuments: number;
  approvedDocuments: number;
}

class GovernmentService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  // Citizens
  async getCitizens(): Promise<Citizen[]> {
    const url = getApiUrl('government', '/api/government/citizens');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch citizens');
    const data = await response.json();
    return data.citizens || [];
  }

  async createCitizen(citizen: Omit<Citizen, 'id'>): Promise<Citizen> {
    const url = getApiUrl('government', '/api/government/citizens');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(citizen),
    });
    if (!response.ok) throw new Error('Failed to create citizen');
    return response.json();
  }

  async updateCitizen(id: string, citizen: Partial<Citizen>): Promise<Citizen> {
    const url = getApiUrl('government', `/api/government/citizens/${id}`);
    const response = await fetch(url, {
      method: 'PUT',
      headers: this.getHeaders(),
      body: JSON.stringify(citizen),
    });
    if (!response.ok) throw new Error('Failed to update citizen');
    return response.json();
  }

  async deleteCitizen(id: string): Promise<void> {
    const url = getApiUrl('government', `/api/government/citizens/${id}`);
    const response = await fetch(url, { method: 'DELETE', headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to delete citizen');
  }

  // Officers
  async getOfficers(): Promise<Officer[]> {
    const url = getApiUrl('government', '/api/government/officers');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch officers');
    const data = await response.json();
    return data.officers || [];
  }

  async createOfficer(officer: Omit<Officer, 'id'>): Promise<Officer> {
    const url = getApiUrl('government', '/api/government/officers');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(officer),
    });
    if (!response.ok) throw new Error('Failed to create officer');
    return response.json();
  }

  // Procedures (TTHC)
  async getProcedures(): Promise<Procedure[]> {
    const url = getApiUrl('government', '/api/government/procedures');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch procedures');
    const data = await response.json();
    return data.procedures || [];
  }

  async createProcedure(procedure: Omit<Procedure, 'id'>): Promise<Procedure> {
    const url = getApiUrl('government', '/api/government/procedures');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(procedure),
    });
    if (!response.ok) throw new Error('Failed to create procedure');
    return response.json();
  }

  // Documents
  async getDocuments(): Promise<Document[]> {
    const url = getApiUrl('government', '/api/government/documents');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch documents');
    const data = await response.json();
    return data.documents || [];
  }

  async createDocument(doc: Omit<Document, 'id'>): Promise<Document> {
    const url = getApiUrl('government', '/api/government/documents');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(doc),
    });
    if (!response.ok) throw new Error('Failed to create document');
    return response.json();
  }

  async publishDocument(id: string): Promise<Document> {
    const url = getApiUrl('government', `/api/government/documents/${id}/publish`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to publish document');
    return response.json();
  }

  // Statistics
  async getStatistics(): Promise<GovernmentStats> {
    const url = getApiUrl('government', '/api/government/statistics');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch statistics');
    return response.json();
  }
}

export const governmentService = new GovernmentService();
