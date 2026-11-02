import { describe, it, expect, vi, beforeEach } from 'vitest';
import { rustEncryptionStore } from './rust-stores';
import { Rust } from '../lib/api';

vi.mock('../lib/api', () => ({
  Rust: {
    generateKeyPair: vi.fn(),
    encryptMessage: vi.fn(),
    decryptMessage: vi.fn(),
  }
}));

describe('RustEncryptionStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    rustEncryptionStore.reset();
    rustEncryptionStore.subscribe(s => { state = s; });
  });

  describe('generateKeyPair', () => {
    it('should generate and store key pair', async () => {
      const keyPair = { publicKey: 'pub1', privateKey: 'priv1' };
      (Rust.generateKeyPair as any).mockResolvedValue(keyPair);

      const result = await rustEncryptionStore.generateKeyPair();

      expect(result).toEqual(keyPair);
      expect(state.keyPair).toEqual(keyPair);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Rust.generateKeyPair as any).mockRejectedValue(new Error('Gen failed'));
      await expect(rustEncryptionStore.generateKeyPair()).rejects.toThrow('Gen failed');
      expect(state.error).toBe('Gen failed');
      expect(state.loading).toBe(false);
    });
  });

  describe('encryptMessage', () => {
    it('should encrypt message', async () => {
      const encrypted = { encrypted: 'cipher' };
      (Rust.encryptMessage as any).mockResolvedValue(encrypted);
      state.keyPair = { publicKey: 'pub', privateKey: 'priv' };

      const result = await rustEncryptionStore.encryptMessage('hello', 'pub');

      expect(result).toEqual(encrypted);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Rust.encryptMessage as any).mockRejectedValue(new Error('Encrypt failed'));
      await expect(rustEncryptionStore.encryptMessage('hello', 'pub')).rejects.toThrow('Encrypt failed');
      expect(state.error).toBe('Encrypt failed');
    });
  });

  describe('decryptMessage', () => {
    it('should decrypt message', async () => {
      const decrypted = { decrypted: 'plain' };
      (Rust.decryptMessage as any).mockResolvedValue(decrypted);

      const result = await rustEncryptionStore.decryptMessage('cipher', 'priv');

      expect(result).toEqual(decrypted);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Rust.decryptMessage as any).mockRejectedValue(new Error('Decrypt failed'));
      await expect(rustEncryptionStore.decryptMessage('cipher', 'priv')).rejects.toThrow('Decrypt failed');
      expect(state.error).toBe('Decrypt failed');
    });
  });

  describe('reset', () => {
    it('should clear state', () => {
      state.keyPair = { publicKey: 'p', privateKey: 'pr' };
      state.encryptionStatus = 'active';
      state.error = 'err';
      rustEncryptionStore.reset();
      expect(state.keyPair).toBeNull();
      expect(state.encryptionStatus).toBe('idle');
      expect(state.error).toBeNull();
      expect(state.loading).toBe(false);
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Rust.generateKeyPair as any).mockRejectedValue(new Error('test'));
      try { await rustEncryptionStore.generateKeyPair(); } catch (e) {}
      expect(state.error).toBe('test');
      rustEncryptionStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
