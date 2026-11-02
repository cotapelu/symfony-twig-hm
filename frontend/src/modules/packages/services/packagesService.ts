import type { ServicePackage, PackagesState } from '../contracts/packagesTypes';

const API_BASE = '/api/packages';

export class PackagesService {
  async getPackages(): Promise<ServicePackage[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch packages');
    return response.json();
  }
}

export const packagesService = new PackagesService();
export default packagesService;
