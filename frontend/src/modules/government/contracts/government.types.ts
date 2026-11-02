// Government Module Contracts - TypeScript types matching PHP backend DTOs

// ============================================
// Citizen Types
// ============================================
export interface Citizen {
  id: string;
  fullName: string;
  identityNumber: string;
  dateOfBirth: string | null;
  gender: 'male' | 'female' | 'other';
  email: string | null;
  phone: string | null;
  province: string;
  district: string;
  ward: string;
  street: string;
  apartmentNumber: string | null;
  ethnicity: string | null;
  nationality: string;
  occupation: string | null;
  educationLevel: string | null;
  maritalStatus: string | null;
  householdId: string | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface CreateCitizenRequest {
  fullName: string;
  identityNumber: string;
  province: string;
  district: string;
  ward: string;
  street: string;
  apartmentNumber?: string;
  email?: string;
  phone?: string;
  dateOfBirth?: string;
  gender?: 'male' | 'female' | 'other';
  ethnicity?: string;
  nationality?: string;
  occupation?: string;
  educationLevel?: string;
  maritalStatus?: string;
  householdId?: string;
}

export interface UpdateCitizenRequest {
  fullName?: string;
  email?: string;
  phone?: string;
  dateOfBirth?: string;
  occupation?: string;
  educationLevel?: string;
  maritalStatus?: string;
  householdId?: string;
}

export interface CitizenFilters {
  search?: string;
  province?: string;
  district?: string;
  ward?: string;
  active?: boolean;
}

export interface CitizenStatistics {
  total: number;
  active: number;
  inactive: number;
  byGender: {
    male: number;
    female: number;
    other: number;
  };
  byAgeGroup: {
    under18: number;
    age18to60: number;
    over60: number;
  };
}

// ============================================
// Document Types
// ============================================
export type DocumentType = 
  | 'decision' 
  | 'notice' 
  | 'instruction' 
  | 'report' 
  | 'letter' 
  | 'circular' 
  | 'other';

export type DocumentStatus = 
  | 'draft' 
  | 'pending_approval' 
  | 'approved' 
  | 'published' 
  | 'archived';

export interface GovernmentDocument {
  id: string;
  title: string;
  type: DocumentType;
  content: string;
  documentNumber: string | null;
  issuedBy: string;
  issuedDate: string | null;
  effectiveDate: string | null;
  signedBy: string | null;
  summary: string | null;
  tags: string[];
  status: DocumentStatus;
  createdAt: string;
  updatedAt: string;
}

export interface CreateDocumentRequest {
  title: string;
  type: DocumentType;
  content: string;
  issuedBy: string;
  documentNumber?: string;
  issuedDate?: string;
  effectiveDate?: string;
  signedBy?: string;
  summary?: string;
  tags?: string[];
}

export interface DocumentFilters {
  type?: DocumentType;
  status?: DocumentStatus;
}

export interface DocumentStatistics {
  total: number;
  byType: Record<DocumentType, number>;
  byStatus: Record<DocumentStatus, number>;
  publishedThisMonth: number;
}

// ============================================
// Administrative Procedure (TTHC) Types
// ============================================
export type ProcedureCategory = 
  | 'household' 
  | 'civil_status' 
  | 'land' 
  | 'construction' 
  | 'business' 
  | 'social' 
  | 'other';

export type ProcedureLevel = 'ward' | 'district' | 'province' | 'central';

export interface AdministrativeProcedure {
  id: string;
  code: string;
  name: string;
  category: ProcedureCategory;
  level: ProcedureLevel;
  description: string;
  requiredDocuments: string[];
  processingTimeDays: number;
  fee: number;
  legalBasis: string[];
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface CreateProcedureRequest {
  code: string;
  name: string;
  category: ProcedureCategory;
  level: ProcedureLevel;
  description: string;
  requiredDocuments: string[];
  processingTimeDays: number;
  fee: number;
  legalBasis: string[];
}

export interface ProcedureFilters {
  category?: ProcedureCategory;
  level?: ProcedureLevel;
  search?: string;
}

// ============================================
// Procedure Application Types
// ============================================
export type ApplicationStatus = 
  | 'draft' 
  | 'submitted' 
  | 'received' 
  | 'processing' 
  | 'awaiting_additional_docs' 
  | 'approved' 
  | 'rejected' 
  | 'completed';

export interface ProcedureApplication {
  id: string;
  procedureId: string;
  procedureCode: string;
  procedureName: string;
  citizenId: string;
  citizenName: string;
  submittedAt: string;
  expectedCompletionDate: string;
  status: ApplicationStatus;
  submittedDocuments: string[];
  notes: string | null;
  processedBy: string | null;
  processedAt: string | null;
  result: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface CreateApplicationRequest {
  procedureId: string;
  citizenId: string;
  submittedDocuments: string[];
  notes?: string;
}

export interface ApplicationFilters {
  procedureId?: string;
  citizenId?: string;
  status?: ApplicationStatus;
  search?: string;
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

// ============================================
// Officer Types (for reference)
// ============================================
export interface Officer {
  id: string;
  fullName: string;
  identityNumber: string;
  email: string;
  phone: string;
  position: Position;
  department: Department;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface Position {
  code: string;
  name: string;
  level: number;
}

export interface Department {
  code: string;
  name: string;
  parentCode: string | null;
}
