// sample-module Module Contracts - TypeScript types
// Replace sample-module with the actual module name during generation

// ============================================
// Types
// ============================================
export interface SampleModuleItem {
  id: string;
  // Add common fields as needed
  name: string;
  description: string | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface CreateSampleModuleRequest {
  name: string;
  description?: string;
}

export interface UpdateSampleModuleRequest {
  name?: string;
  description?: string;
}

export interface SampleModuleFilters {
  search?: string;
  active?: boolean;
}

export interface SampleModuleStatistics {
  total: number;
  active: number;
  inactive: number;
}

// ============================================
// API Response Types
// ============================================
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  error?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  page: number;
  perPage: number;
  total?: number;
}
