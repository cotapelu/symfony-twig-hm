import { createEventDispatcher } from 'svelte';

export interface LocationEvent {
  type: 'location_updated' | 'location_shared' | 'place_selected' | 'directions_requested' | 'tracking_started' | 'tracking_stopped';
  data?: any;
  timestamp: number;
}

export function createLocationEvents() {
  const dispatch = createEventDispatcher();

  return {
    dispatchLocationUpdated(location: any) {
      dispatch('location_updated', {
        type: 'location_updated',
        data: location,
        timestamp: Date.now()
      });
    },

    dispatchLocationShared(share: any) {
      dispatch('location_shared', {
        type: 'location_shared',
        data: share,
        timestamp: Date.now()
      });
    },

    dispatchPlaceSelected(place: any) {
      dispatch('place_selected', {
        type: 'place_selected',
        data: place,
        timestamp: Date.now()
      });
    },

    dispatchDirectionsRequested(request: any) {
      dispatch('directions_requested', {
        type: 'directions_requested',
        data: request,
        timestamp: Date.now()
      });
    },

    dispatchTrackingStarted() {
      dispatch('tracking_started', {
        type: 'tracking_started',
        timestamp: Date.now()
      });
    },

    dispatchTrackingStopped() {
      dispatch('tracking_stopped', {
        type: 'tracking_stopped',
        timestamp: Date.now()
      });
    }
  };
}

export const locationEventTypes = {
  LOCATION_UPDATED: 'location_updated',
  LOCATION_SHARED: 'location_shared',
  PLACE_SELECTED: 'place_selected',
  DIRECTIONS_REQUESTED: 'directions_requested',
  TRACKING_STARTED: 'tracking_started',
  TRACKING_STOPPED: 'tracking_stopped'
} as const;
