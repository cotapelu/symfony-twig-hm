// SampleModule Module Barrel Export
// Replace sample-module with the actual module name during generation

// ============================================
// Contracts (Types)
// ============================================
export type {
  SampleModuleItem,
  CreateSampleModuleRequest,
  UpdateSampleModuleRequest,
  SampleModuleFilters,
  SampleModuleStatistics,
  ApiResponse,
  PaginatedResponse,
} from './contracts/sample-module.types';

// ============================================
// Services (API)
// ============================================
export {
  sample-moduleApi
} from './services/sample-module.service';

// ============================================
// Events
// ============================================
export {
  SampleModuleEventBus
} from './events/sample-module.events';

export type {
  SampleModuleEvent
} from './events/sample-module.events';

// ============================================
// State (Stores)
// ============================================
export {
  SampleModuleStore,
  activeSampleModuleItems,
  SampleModuleByStatus,
  isLoading,
  isSaving,
  SampleModuleError,
  selectedSampleModuleItem,
  SampleModuleStatistics
} from './state/sample-module.store';

// ============================================
// UI Components
// ============================================
export { default as SampleModule } from './ui/sample-module.svelte';
