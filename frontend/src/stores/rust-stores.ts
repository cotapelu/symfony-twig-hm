// Rust Stores - TypeScript Version
// Encryption, FileSharing, Marketplace

import { writable } from "svelte/store";
import { Rust } from "../lib/api";

interface RustEncryptionState {
  keyPair: { publicKey: string; privateKey: string } | null;
  encryptionStatus: string;
  loading: boolean;
  error: string | null;
}

function createRustEncryptionStore() {
  const initialState: RustEncryptionState = {
    keyPair: null,
    encryptionStatus: "idle",
    loading: false,
    error: null,
  };

  const { subscribe, set, update } =
    writable<RustEncryptionState>(initialState);

  return {
    subscribe,

    generateKeyPair: async (): Promise<{
      publicKey: string;
      privateKey: string;
    }> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const keyPair = await Rust.generateKeyPair();
        update((prev) => ({ ...prev, keyPair, loading: false }));
        return keyPair;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to generate key pair";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    encryptMessage: async (
      message: string,
      publicKey: string,
    ): Promise<{ encrypted: string }> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const encrypted = await Rust.encryptMessage(message, publicKey);
        update((prev) => ({ ...prev, loading: false }));
        return encrypted;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to encrypt message";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    decryptMessage: async (
      encryptedMessage: string,
      privateKey: string,
    ): Promise<{ decrypted: string }> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const decrypted = await Rust.decryptMessage(
          encryptedMessage,
          privateKey,
        );
        update((prev) => ({ ...prev, loading: false }));
        return decrypted;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to decrypt message";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        keyPair: null,
        encryptionStatus: "idle",
        loading: false,
        error: null,
      });
    },
  };
}

interface RustFileSharingState {
  sharedFiles: any[];
  mySharedFiles: any[];
  downloadCount: number;
  peerCount: number;
  loading: boolean;
  uploading: boolean;
  error: string | null;
}

function createRustFileSharingStore() {
  const initialState: RustFileSharingState = {
    sharedFiles: [],
    mySharedFiles: [],
    downloadCount: 0,
    peerCount: 0,
    loading: false,
    uploading: false,
    error: null,
  };

  const { subscribe, set, update } =
    writable<RustFileSharingState>(initialState);

  return {
    subscribe,

    uploadFile: async (file: File, encryptionEnabled = false): Promise<any> => {
      update((state) => ({ ...state, uploading: true, error: null }));
      try {
        const fileData = await Rust.uploadFile(file, encryptionEnabled);
        update((prev) => ({
          ...prev,
          mySharedFiles: [fileData, ...prev.mySharedFiles],
          uploading: false,
        }));
        return fileData;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to upload file";
        update((state) => ({
          ...state,
          uploading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    downloadFile: async (fileId: string): Promise<Blob> => {
      try {
        const file = await Rust.downloadFile(fileId);
        return file;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to download file";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    shareFile: async (
      fileId: string,
      recipientEmail: string,
      permissions = "read",
    ): Promise<any> => {
      try {
        const share = await Rust.shareFile(fileId, recipientEmail, permissions);
        update((prev) => ({
          ...prev,
          sharedFiles: [...prev.sharedFiles, share],
        }));
        return share;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to share file";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    getSharedFiles: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const files = await Rust.getSharedFiles();
        update((prev) => ({ ...prev, sharedFiles: files, loading: false }));
        return files;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get shared files";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    stopSharing: async (fileId: string): Promise<void> => {
      try {
        await Rust.stopSharing(fileId);
        update((prev) => ({
          ...prev,
          sharedFiles: prev.sharedFiles.filter((f) => f.id !== fileId),
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to stop sharing";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        sharedFiles: [],
        mySharedFiles: [],
        downloadCount: 0,
        peerCount: 0,
        loading: false,
        uploading: false,
        error: null,
      });
    },
  };
}

interface RustMarketplaceState {
  products: any[];
  listings: any[];
  currentProduct: any | null;
  myBids: any[];
  loading: boolean;
  error: string | null;
}

function createRustMarketplaceStore() {
  const initialState: RustMarketplaceState = {
    products: [],
    listings: [],
    currentProduct: null,
    myBids: [],
    loading: false,
    error: null,
  };

  const { subscribe, set, update } =
    writable<RustMarketplaceState>(initialState);

  return {
    subscribe,

    listProducts: async (filters: Record<string, any> = {}): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const products = await Rust.listProducts(filters);
        update((prev) => ({ ...prev, products, loading: false }));
        return products;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to list products";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getListings: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const listings = await Rust.listProducts();
        update((prev) => ({ ...prev, listings, loading: false }));
        return listings;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get listings";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    createListing: async (data: any): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const product = await Rust.createProduct(data);
        update((prev) => ({
          ...prev,
          listings: [product, ...prev.listings],
          loading: false,
        }));
        return product;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create listing";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    purchaseListing: async (productId: string): Promise<any> => {
      try {
        const purchase = await Rust.buyProduct(productId);
        return purchase;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to purchase listing";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    createProduct: async (data: any): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const product = await Rust.createProduct(data);
        update((prev) => ({
          ...prev,
          products: [product, ...prev.products],
          loading: false,
        }));
        return product;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create product";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getProduct: async (productId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const product = await Rust.getProduct(productId);
        update((prev) => ({
          ...prev,
          currentProduct: product,
          loading: false,
        }));
        return product;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get product";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    placeBid: async (productId: string, amount: number): Promise<any> => {
      try {
        const bid = await Rust.placeBid(productId, amount);
        update((prev) => ({ ...prev, myBids: [...prev.myBids, bid] }));
        return bid;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to place bid";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    buyProduct: async (productId: string): Promise<any> => {
      try {
        const purchase = await Rust.buyProduct(productId);
        return purchase;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to buy product";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        products: [],
        listings: [],
        currentProduct: null,
        myBids: [],
        loading: false,
        error: null,
      });
    },
  };
}

export const rustEncryptionStore = createRustEncryptionStore();
export const rustFileSharingStore = createRustFileSharingStore();
export const rustMarketplaceStore = createRustMarketplaceStore();

export const {
  generateKeyPair,
  encryptMessage,
  decryptMessage,
  clearError: clearEncryptionError,
} = rustEncryptionStore;

export const {
  uploadFile,
  downloadFile,
  shareFile,
  getSharedFiles,
  stopSharing,
  clearError: clearFileSharingError,
} = rustFileSharingStore;

export const {
  listProducts,
  getListings,
  createListing,
  purchaseListing,
  createProduct,
  getProduct,
  placeBid,
  buyProduct,
  clearError: clearMarketplaceError,
} = rustMarketplaceStore;
