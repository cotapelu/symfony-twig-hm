export interface DiscoverItemViewedEvent { type: 'discover.item.viewed'; payload: { itemId: string }; }
export type DiscoverEvent = DiscoverItemViewedEvent;

export const discoverEvents = {
  itemViewed: (itemId: string): DiscoverItemViewedEvent => ({ type: 'discover.item.viewed', payload: { itemId } })
};
