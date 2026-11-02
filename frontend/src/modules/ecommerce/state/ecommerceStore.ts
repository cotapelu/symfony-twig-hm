import { writable } from 'svelte/store';
import type { Product, CartItem, Order, EcommerceState, AddToCartRequest, CheckoutRequest } from '../contracts/ecommerceTypes';
import { EcommerceService } from '../services/ecommerceService';

const initialState: EcommerceState = {
  products: [],
  cart: [],
  orders: [],
  selectedProduct: null,
  loading: false,
  error: null
};

function createEcommerceStore() {
  const { subscribe, set, update } = writable<EcommerceState>(initialState);
  const service = new EcommerceService();

  // Load cart from localStorage
  const savedCart = typeof localStorage !== 'undefined' ? localStorage.getItem('cart') : null;
  if (savedCart) {
    try {
      initialState.cart = JSON.parse(savedCart);
    } catch { /* ignore */ }
  }

  return {
    subscribe,

    async loadProducts() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const products = await service.getProducts();
        update(state => ({ ...state, products, loading: false }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load products',
          loading: false
        }));
      }
    },

    async loadProduct(id: string) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const product = await service.getProduct(id);
        update(state => ({ ...state, selectedProduct: product, loading: false }));
        return product;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load product',
          loading: false
        }));
        throw error;
      }
    },

    addToCart(product: Product, quantity: number = 1) {
      update(state => {
        const existingItem = state.cart.find(item => item.productId === product.id);
        let newCart: CartItem[];

        if (existingItem) {
          newCart = state.cart.map(item =>
            item.productId === product.id
              ? { ...item, quantity: item.quantity + quantity }
              : item
          );
        } else {
          newCart = [...state.cart, {
            id: crypto.randomUUID(),
            productId: product.id,
            name: product.name,
            price: product.discount ? product.price * (1 - product.discount / 100) : product.price,
            quantity,
            image: product.image,
            currency: product.currency
          }];
        }

        if (typeof localStorage !== 'undefined') {
          localStorage.setItem('cart', JSON.stringify(newCart));
        }

        return { ...state, cart: newCart };
      });
    },

    removeFromCart(itemId: string) {
      update(state => {
        const newCart = state.cart.filter(item => item.id !== itemId);
        if (typeof localStorage !== 'undefined') {
          localStorage.setItem('cart', JSON.stringify(newCart));
        }
        return { ...state, cart: newCart };
      });
    },

    updateQuantity(itemId: string, quantity: number) {
      if (quantity < 1) {
        this.removeFromCart(itemId);
        return;
      }

      update(state => {
        const newCart = state.cart.map(item =>
          item.id === itemId ? { ...item, quantity } : item
        );
        if (typeof localStorage !== 'undefined') {
          localStorage.setItem('cart', JSON.stringify(newCart));
        }
        return { ...state, cart: newCart };
      });
    },

    clearCart() {
      update(state => {
        if (typeof localStorage !== 'undefined') {
          localStorage.removeItem('cart');
        }
        return { ...state, cart: [] };
      });
    },

    async loadOrders() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const orders = await service.getOrders();
        update(state => ({ ...state, orders, loading: false }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load orders',
          loading: false
        }));
      }
    },

    async checkout(request: CheckoutRequest) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const order = await service.checkout(request);
        update(state => ({
          ...state,
          orders: [order, ...state.orders],
          cart: [],
          loading: false
        }));
        if (typeof localStorage !== 'undefined') {
          localStorage.removeItem('cart');
        }
        return order;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Checkout failed',
          loading: false
        }));
        throw error;
      }
    },

    clearError() {
      update(state => ({ ...state, error: null }));
    },
    reset() {
      set(initialState);
      if (typeof localStorage !== 'undefined') {
        localStorage.removeItem('cart');
      }
    }
  };
}

export const ecommerceStore = createEcommerceStore();
export const { loadProducts, loadProduct, addToCart, removeFromCart, updateQuantity, clearCart, loadOrders, checkout, clearError, reset } = ecommerceStore;
