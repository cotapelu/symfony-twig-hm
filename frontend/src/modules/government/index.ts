// Government Module - Full Stack Integration
// Backend: PHP Symfony API
// Frontend: Svelte + TypeScript

// ============================================
// Contracts (Types)
// ============================================
export type {
  Citizen,
  CreateCitizenRequest,
  UpdateCitizenRequest,
  CitizenFilters,
  CitizenStatistics,
  GovernmentDocument,
  CreateDocumentRequest,
  DocumentFilters,
  DocumentStatistics,
  AdministrativeProcedure,
  CreateProcedureRequest,
  ProcedureFilters,
  ProcedureApplication,
  CreateApplicationRequest,
  ApplicationFilters,
  ApiResponse,
  PaginatedResponse,
  DocumentType,
  DocumentStatus,
  ProcedureCategory,
  ApplicationStatus,
} from './contracts/government.types';

// ============================================
// Services (API)
// ============================================
export {
  citizenApi,
  documentApi,
  procedureApi,
  applicationApi,
} from './services/government.service';

// ============================================
// Events
// ============================================
export {
  GovernmentEventBus,
} from './events/government.events';

export type {
  GovernmentEvent,
  CitizenCreatedEvent,
  CitizenUpdatedEvent,
  DocumentCreatedEvent,
  DocumentPublishedEvent,
  ApplicationSubmittedEvent,
  ApplicationStatusChangedEvent,
} from './events/government.events';

// ============================================
// State (Stores)
// ============================================
export {
  citizenStore,
  activeCitizens,
  citizensByGender,
  citizensByWard,
  isLoading as citizenLoading,
  isSaving as citizenSaving,
  citizenError,
  selectedCitizen,
  citizenStatistics,
} from './state/citizenStore';

export {
  documentStore,
  publishedDocuments,
  documentsByType,
  recentDocuments,
  isDocumentLoading,
  isDocumentSaving,
  documentError,
} from './state/documentStore';

export {
  procedureStore,
  activeProcedures,
  proceduresByCategory,
  pendingApplications,
  completedApplications,
  applicationsByStatus,
  isProcedureLoading,
  isProcedureSaving,
  procedureError,
} from './state/procedureStore';

// ============================================
// UI Components
// ============================================
export { default as CitizenManagement } from './ui/CitizenManagement.svelte';
export { default as DocumentManagement } from './ui/DocumentManagement.svelte';
export { default as ProcedureManagement } from './ui/ProcedureManagement.svelte';
export { default as GovernmentPage } from './ui/GovernmentPage.svelte';
export { default as GovernmentDashboard } from './ui/GovernmentDashboard.svelte';
