import { describe, it, expect, vi, beforeEach } from 'vitest';
import { rustMarketplaceStore } from './rust-stores';
import { Rust } from '../lib/api';

vi.mock('../lib/api', () => ({
  Rust: {
    listProducts: vi.fn(),
    getListings: vi.fn(),
    createListing: vi.fn(),
    purchaseListing: vi.fn(),
    createProduct: vi.fn(),
    getProduct: vi.fn(),
    placeBid: vi.fn(),
    buyProduct: vi.fn(),
  }
}));

describe('RustMarketplaceStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    rustMarketplaceStore.reset();
    rustMarketplaceStore.subscribe(s => { state = s; });
  });

  const mockProduct = (id: string): any => ({ id, name: `product${id}` });
  const mockListing = (id: string): any => ({ id, productId: id });

  describe('listProducts', () => {
    it('should list products with optional filters', async () => {
      const products = [mockProduct('1'), mockProduct('2')];
      (Rust.listProducts as any).mockResolvedValue(products);
      const filters = { category: 'tech' };

      const result = await rustMarketplaceStore.listProducts(filters);

      expect(result).toEqual(products);
      expect(state.products).toEqual(products);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Rust.listProducts as any).mockRejectedValue(new Error('List failed'));
      await expect(rustMarketplaceStore.listProducts()).rejects.toThrow('List failed');
      expect(state.error).toBe('List failed');
    });
  });

  describe('getListings', () => {
    it('should fetch listings', async () => {
      const listings = [mockListing('1')];
      (Rust.listProducts as any).mockResolvedValue(listings);

      const result = await rustMarketplaceStore.getListings();

      expect(result).toEqual(listings);
      expect(state.listings).toEqual(listings);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Rust.listProducts as any).mockRejectedValue(new Error('Get failed'));
      await expect(rustMarketplaceStore.getListings()).rejects.toThrow('Get failed');
      expect(state.error).toBe('Get failed');
    });
  });

  describe('createListing', () => {
    it('should create listing and add to state', async () => {
      const product = mockProduct('new');
      (Rust.createProduct as any).mockResolvedValue(product);

      const result = await rustMarketplaceStore.createListing({ productId: 'p1' });

      expect(result).toEqual(product);
      expect(state.listings).toContain(product);
    });

    it('should handle error', async () => {
      (Rust.createProduct as any).mockRejectedValue(new Error('Create failed'));
      await expect(rustMarketplaceStore.createListing({})).rejects.toThrow('Create failed');
      expect(state.error).toBe('Create failed');
    });
  });

  describe('purchaseListing', () => {
    it('should purchase listing', async () => {
      const purchase = { listingId: 'l1', status: 'purchased' };
      (Rust.buyProduct as any).mockResolvedValue(purchase);

      const result = await rustMarketplaceStore.purchaseListing('l1');

      expect(result).toEqual(purchase);
    });

    it('should handle error', async () => {
      (Rust.buyProduct as any).mockRejectedValue(new Error('Purchase failed'));
      await expect(rustMarketplaceStore.purchaseListing('l1')).rejects.toThrow('Purchase failed');
      expect(state.error).toBe('Purchase failed');
    });
  });

  describe('createProduct', () => {
    it('should create product and add to state', async () => {
      const product = mockProduct('new');
      (Rust.createProduct as any).mockResolvedValue(product);

      const result = await rustMarketplaceStore.createProduct({ name: 'New' });

      expect(result).toEqual(product);
      expect(state.products).toContain(product);
    });

    it('should handle error', async () => {
      (Rust.createProduct as any).mockRejectedValue(new Error('Create product failed'));
      await expect(rustMarketplaceStore.createProduct({})).rejects.toThrow('Create product failed');
      expect(state.error).toBe('Create product failed');
    });
  });

  describe('getProduct', () => {
    it('should fetch product and set currentProduct', async () => {
      const product = mockProduct('1');
      (Rust.getProduct as any).mockResolvedValue(product);

      const result = await rustMarketplaceStore.getProduct('1');

      expect(result).toEqual(product);
      expect(state.currentProduct).toEqual(product);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Rust.getProduct as any).mockRejectedValue(new Error('Get product failed'));
      await expect(rustMarketplaceStore.getProduct('1')).rejects.toThrow('Get product failed');
      expect(state.error).toBe('Get product failed');
    });
  });

  describe('placeBid', () => {
    it('should place bid and add to myBids', async () => {
      const bid = { id: 'b1', productId: 'p1' };
      (Rust.placeBid as any).mockResolvedValue(bid);

      const result = await rustMarketplaceStore.placeBid('p1', 100);

      expect(result).toEqual(bid);
      expect(state.myBids).toContain(bid);
    });

    it('should handle error', async () => {
      (Rust.placeBid as any).mockRejectedValue(new Error('Bid failed'));
      await expect(rustMarketplaceStore.placeBid('p1', 100)).rejects.toThrow('Bid failed');
      expect(state.error).toBe('Bid failed');
    });
  });

  describe('buyProduct', () => {
    it('should buy product', async () => {
      const purchase = { productId: 'p1', status: 'bought' };
      (Rust.buyProduct as any).mockResolvedValue(purchase);

      const result = await rustMarketplaceStore.buyProduct('p1');

      expect(result).toEqual(purchase);
    });

    it('should handle error', async () => {
      (Rust.buyProduct as any).mockRejectedValue(new Error('Buy failed'));
      await expect(rustMarketplaceStore.buyProduct('p1')).rejects.toThrow('Buy failed');
      expect(state.error).toBe('Buy failed');
    });
  });

  describe('reset', () => {
    it('should clear state', () => {
      state.products = [mockProduct('1')];
      state.listings = [mockListing('1')];
      state.currentProduct = mockProduct('1');
      state.myBids = [{ id: 'b1' }];
      state.error = 'err';
      rustMarketplaceStore.reset();
      expect(state.products).toEqual([]);
      expect(state.listings).toEqual([]);
      expect(state.currentProduct).toBeNull();
      expect(state.myBids).toEqual([]);
      expect(state.error).toBeNull();
      expect(state.loading).toBe(false);
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Rust.listProducts as any).mockRejectedValue(new Error('test'));
      try { await rustMarketplaceStore.listProducts(); } catch (e) {}
      expect(state.error).toBe('test');
      rustMarketplaceStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
