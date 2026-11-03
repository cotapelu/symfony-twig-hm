export * from './ui/+page.svelte';
export * from './state/aiStore';
export * from './services/aiService';
export * from './contracts/aiTypes';
export * from './events/aiEvents';

export { aiStore, loadAIConfig, processAIRequest, clearError } from './state/aiStore';
export { AIService } from './services/aiService';
// Government* / LLMResponse / ProcedureStep live in contracts (single source of
// truth); explicit re-export disambiguates the star re-exports.
export type {
  GovernmentContext,
  GovernmentQuery,
  ProcedureStep,
  LLMResponse,
} from './contracts/aiTypes';
