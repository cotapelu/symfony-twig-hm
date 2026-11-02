import { createEventDispatcher } from 'svelte';

export interface EcommerceEvent {
  type: 'product_viewed' | 'added_to_cart' | 'removed_from_cart' | 'cart_updated' | 'checkout_started' | 'order_placed' | 'order_status_changed';
  data?: any;
  timestamp: number;
}

export function createEcommerceEvents() {
  const dispatch = createEventDispatcher();

  return {
    dispatchProductViewed(product: any) {
      dispatch('product_viewed', {
        type: 'product_viewed',
        data: product,
        timestamp: Date.now()
      });
    },

    dispatchAddedToCart(item: any) {
      dispatch('added_to_cart', {
        type: 'added_to_cart',
        data: item,
        timestamp: Date.now()
      });
    },

    dispatchRemovedFromCart(itemId: string) {
      dispatch('removed_from_cart', {
        type: 'removed_from_cart',
        data: { itemId },
        timestamp: Date.now()
      });
    },

    dispatchCartUpdated(cart: any[]) {
      dispatch('cart_updated', {
        type: 'cart_updated',
        data: cart,
        timestamp: Date.now()
      });
    },

    dispatchCheckoutStarted(cart: any[]) {
      dispatch('checkout_started', {
        type: 'checkout_started',
        data: cart,
        timestamp: Date.now()
      });
    },

    dispatchOrderPlaced(order: any) {
      dispatch('order_placed', {
        type: 'order_placed',
        data: order,
        timestamp: Date.now()
      });
    },

    dispatchOrderStatusChanged(orderId: string, status: string) {
      dispatch('order_status_changed', {
        type: 'order_status_changed',
        data: { orderId, status },
        timestamp: Date.now()
      });
    }
  };
}

export const ecommerceEventTypes = {
  PRODUCT_VIEWED: 'product_viewed',
  ADDED_TO_CART: 'added_to_cart',
  REMOVED_FROM_CART: 'removed_from_cart',
  CART_UPDATED: 'cart_updated',
  CHECKOUT_STARTED: 'checkout_started',
  ORDER_PLACED: 'order_placed',
  ORDER_STATUS_CHANGED: 'order_status_changed'
} as const;
