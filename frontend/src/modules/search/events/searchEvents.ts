import { createEventDispatcher } from 'svelte';

export interface SearchEvent {
  type: 'search_initiated' | 'search_completed' | 'search_failed' | 'suggestion_selected' | 'filter_applied' | 'result_clicked';
  data?: any;
  timestamp: number;
}

export function createSearchEvents() {
  const dispatch = createEventDispatcher();

  return {
    dispatchSearchInitiated(query: string, filters?: any) {
      dispatch('search_initiated', {
        type: 'search_initiated',
        data: { query, filters },
        timestamp: Date.now()
      });
    },

    dispatchSearchCompleted(results: any[]) {
      dispatch('search_completed', {
        type: 'search_completed',
        data: { results, count: results.length },
        timestamp: Date.now()
      });
    },

    dispatchSearchFailed(error: any) {
      dispatch('search_failed', {
        type: 'search_failed',
        data: error,
        timestamp: Date.now()
      });
    },

    dispatchSuggestionSelected(suggestion: string) {
      dispatch('suggestion_selected', {
        type: 'suggestion_selected',
        data: { suggestion },
        timestamp: Date.now()
      });
    },

    dispatchFilterApplied(filters: any) {
      dispatch('filter_applied', {
        type: 'filter_applied',
        data: filters,
        timestamp: Date.now()
      });
    },

    dispatchResultClicked(resultId: string) {
      dispatch('result_clicked', {
        type: 'result_clicked',
        data: { resultId },
        timestamp: Date.now()
      });
    }
  };
}

export const searchEventTypes = {
  SEARCH_INITIATED: 'search_initiated',
  SEARCH_COMPLETED: 'search_completed',
  SEARCH_FAILED: 'search_failed',
  SUGGESTION_SELECTED: 'suggestion_selected',
  FILTER_APPLIED: 'filter_applied',
  RESULT_CLICKED: 'result_clicked'
} as const;
