export interface ItemAddedEvent { type: 'compare.item.added'; payload: { itemId: string }; }
export interface ItemRemovedEvent { type: 'compare.item.removed'; payload: { itemId: string }; }
export type CompareEvent = ItemAddedEvent | ItemRemovedEvent;

export const compareEvents = {
  itemAdded: (itemId: string): ItemAddedEvent => ({ type: 'compare.item.added', payload: { itemId } }),
  itemRemoved: (itemId: string): ItemRemovedEvent => ({ type: 'compare.item.removed', payload: { itemId } })
};
