export interface ItemAddedEvent { type: 'wishlist.item.added'; payload: { itemId: string }; }
export interface ItemRemovedEvent { type: 'wishlist.item.removed'; payload: { itemId: string }; }
export type WishlistEvent = ItemAddedEvent | ItemRemovedEvent;

export const wishlistEvents = {
  itemAdded: (itemId: string): ItemAddedEvent => ({ type: 'wishlist.item.added', payload: { itemId } }),
  itemRemoved: (itemId: string): ItemRemovedEvent => ({ type: 'wishlist.item.removed', payload: { itemId } })
};
