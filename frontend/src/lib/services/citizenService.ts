import { writable } from 'svelte/store';
import { browser } from '$app/environment';

interface Citizen {
  id: string;
  fullName: string;
  email?: string | null;
  phone?: string | null;
  identityNumber?: string | null;
  gender: string;
  isActive: boolean;
}

interface CitizenFilters {
  province?: string;
  district?: string;
  ward?: string;
  isActive?: boolean;
  search?: string;
  page?: number;
  perPage?: number;
}

class CitizenService {
  private apiUrl = '/api/government';
  private citizensStore = writable<Citizen[]>([]);
  private loadingStore = writable<boolean>(false);
  private errorStore = writable<string | null>(null);
  private totalStore = writable<number>(0);

  citizens = this.citizensStore;
  loading = this.loadingStore;
  error = this.errorStore;
  total = this.totalStore;

  async fetchCitizens(filters: CitizenFilters = {}): Promise<void> {
    if (!browser) return;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const params = new URLSearchParams();
      
      if (filters.province) params.append('province', filters.province);
      if (filters.district) params.append('district', filters.district);
      if (filters.ward) params.append('ward', filters.ward);
      if (filters.isActive !== undefined) params.append('isActive', String(filters.isActive));
      if (filters.search) params.append('search', filters.search);
      if (filters.page) params.append('page', String(filters.page));
      if (filters.perPage) params.append('perPage', String(filters.perPage));

      const response = await fetch(`${this.apiUrl}/statistics/citizens?${params}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      this.citizensStore.set(data.data || []);
      this.totalStore.set(parseInt(data.pagination?.total) || 0);
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to fetch citizens:', err);
    } finally {
      this.loadingStore.set(false);
    }
  }

  async createCitizen(citizenData: Partial<Citizen>): Promise<Citizen | null> {
    if (!browser) return null;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/import/citizens`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          citizens: [citizenData]
        })
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      // Refresh the citizen list
      await this.fetchCitizens();
      return result.data?.[0] || null;
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to create citizen:', err);
      return null;
    } finally {
      this.loadingStore.set(false);
    }
  }

  async updateCitizen(id: string, citizenData: Partial<Citizen>): Promise<boolean> {
    if (!browser) return false;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/citizens/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(citizenData)
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      // Refresh the citizen list
      await this.fetchCitizens();
      return true;
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to update citizen:', err);
      return false;
    } finally {
      this.loadingStore.set(false);
    }
  }

  async deleteCitizen(id: string): Promise<boolean> {
    if (!browser) return false;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      // Use the deactivate endpoint to soft delete
      const response = await fetch(`${this.apiUrl}/citizens/${id}/deactivate`, {
        method: 'POST'
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      // Refresh the citizen list
      await this.fetchCitizens();
      return true;
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to delete citizen:', err);
      return false;
    } finally {
      this.loadingStore.set(false);
    }
  }

  async exportCitizens(format: 'csv' | 'json', filters: CitizenFilters = {}): Promise<Blob | null> {
    if (!browser) return null;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const params = new URLSearchParams();
      
      if (filters.province) params.append('province', filters.province);
      if (filters.district) params.append('district', filters.district);
      if (filters.ward) params.append('ward', filters.ward);
      if (filters.isActive !== undefined) params.append('isActive', String(filters.isActive));

      const response = await fetch(`${this.apiUrl}/export/${format}?${params}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const blob = await response.blob();
      return blob;
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error(`Failed to export citizens as ${format}:`, err);
      return null;
    } finally {
      this.loadingStore.set(false);
    }
  }

  async getCitizenById(id: string): Promise<Citizen | null> {
    if (!browser) return null;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/citizens/${id}`);
      
      if (!response.ok) {
        if (response.status === 404) {
          return null;
        }
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to fetch citizen by ID:', err);
      return null;
    } finally {
      this.loadingStore.set(false);
    }
  }
}

export const citizenService = new CitizenService();