import { writable, derived, get } from 'svelte/store';
import type { Business, BusinessReview, BusinessState, PaginationParams } from '../contracts/businessTypes';
import { businessService } from '../services/businessService';

const initialState: BusinessState = {
  businesses: [],
  selectedBusiness: null,
  reviews: [],
  loading: false,
  error: null,
  pagination: {
    page: 1,
    pageSize: 10,
    total: 0,
    totalPages: 0
  },
  currentUserId: null
};

function createBusinessStore() {
  const { subscribe, set, update } = writable<BusinessState>(initialState);

  return {
    subscribe,

    async loadBusinesses(params?: PaginationParams) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const result = await businessService.getBusinesses(params);
        update(s => ({ 
          ...s, 
          businesses: result.businesses, 
          loading: false,
          pagination: {
            page: result.page,
            pageSize: params?.pageSize || 10,
            total: result.total,
            totalPages: result.totalPages
          }
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadMyBusinesses() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const businesses = await businessService.getMyBusinesses();
        update(s => ({ ...s, businesses, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadBusiness(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const selectedBusiness = await businessService.getBusiness(id);
        
        const state = get({ subscribe });
        const canModify = selectedBusiness.ownerId === state.currentUserId;
        
        update(s => ({ ...s, selectedBusiness: { ...selectedBusiness, isOwner: canModify }, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async createBusiness(request: { name: string; description?: string; category: string; address: string; phone?: string; email?: string; website?: string }) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const business = await businessService.createBusiness(request);
        update(s => ({ ...s, businesses: [...s.businesses, business], loading: false }));
        return business;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async updateBusiness(id: string, request: { name?: string; description?: string; category?: string; address?: string; phone?: string; email?: string; website?: string }) {
      const state = get({ subscribe });
      const business = state.businesses.find(b => b.id === id);
      
      if (!business || business.ownerId !== state.currentUserId) {
        throw new Error('Unauthorized: You do not own this business');
      }
      
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const updated = await businessService.updateBusiness(id, request);
        update(s => ({ 
          ...s, 
          businesses: s.businesses.map(b => b.id === id ? updated : b),
          selectedBusiness: s.selectedBusiness?.id === id ? updated : s.selectedBusiness,
          loading: false 
        }));
        return updated;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadReviews(businessId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const reviews = await businessService.getReviews(businessId);
        update(s => ({ ...s, reviews, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async createReview(businessId: string, rating: number, comment?: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const review = await businessService.createReview({ businessId, rating, comment });
        update(s => ({ ...s, reviews: [...s.reviews, review], loading: false }));
        return review;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async deleteBusiness(id: string) {
      const state = get({ subscribe });
      const business = state.businesses.find(b => b.id === id);
      
      if (!business || business.ownerId !== state.currentUserId) {
        throw new Error('Unauthorized: You do not own this business');
      }
      
      update(s => ({ ...s, loading: true, error: null }));
      try {
        await businessService.deleteBusiness(id);
        update(s => ({ 
          ...s, 
          businesses: s.businesses.filter(b => b.id !== id),
          loading: false 
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    setCurrentUser(userId: string) {
      update(s => ({ ...s, currentUserId: userId }));
    },

    selectBusiness(business: Business | null) {
      update(s => ({ ...s, selectedBusiness: business }));
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const businessStore = createBusinessStore();

export const businesses = derived(businessStore, $s => $s.businesses);
export const selectedBusiness = derived(businessStore, $s => $s.selectedBusiness);
export const reviews = derived(businessStore, $s => $s.reviews);
export const businessLoading = derived(businessStore, $s => $s.loading);
export const businessError = derived(businessStore, $s => $s.error);
export const pagination = derived(businessStore, $s => $s.pagination);