import { describe, it, expect, vi, beforeEach } from 'vitest';
import { pythonBotStore } from './python-all';
import { Python } from '../lib/api';

vi.mock('../lib/api', () => ({
  Python: {
    createBot: vi.fn(),
    getBots: vi.fn(),
    getBotResponse: vi.fn(),
    toggleBot: vi.fn(),
    deleteBot: vi.fn(),
  }
}));

describe('PythonBotStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    pythonBotStore.reset();
    pythonBotStore.subscribe(s => { state = s; });
  });

  describe('createBot', () => {
    it('should create and add bot', async () => {
      const bot = { id: 'b1', name: 'Bot1' };
      (Python.createBot as any).mockResolvedValue(bot);

      const result = await pythonBotStore.createBot({ name: 'Bot1' });

      expect(result).toEqual(bot);
      expect(state.bots).toContain(bot);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.createBot as any).mockRejectedValue(new Error('Create failed'));
      await expect(pythonBotStore.createBot({})).rejects.toThrow('Create failed');
      expect(state.error).toBe('Create failed');
    });
  });

  describe('getBots', () => {
    it('should fetch bots list', async () => {
      const bots = [{ id: 'b1' }, { id: 'b2' }];
      (Python.getBots as any).mockResolvedValue(bots);

      const result = await pythonBotStore.getBots();

      expect(result).toEqual(bots);
      expect(state.bots).toEqual(bots);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.getBots as any).mockRejectedValue(new Error('GetBots failed'));
      await expect(pythonBotStore.getBots()).rejects.toThrow('GetBots failed');
      expect(state.error).toBe('GetBots failed');
    });
  });

  describe('getBotResponse', () => {
    it('should get response and update chat history', async () => {
      (Python.getBotResponse as any).mockResolvedValue('Hello!');
      state.bots = [{ id: 'b1', isActive: true }];

      const result = await pythonBotStore.getBotResponse('b1', 'Hi');

      expect(result).toBe('Hello!');
      expect(state.chatHistory['b1']).toEqual([{ message: 'Hi', response: 'Hello!' }]);
    });

    it('should handle response object with response field', async () => {
      (Python.getBotResponse as any).mockResolvedValue({ response: 'Howdy' });
      state.bots = [{ id: 'b1' }];

      const result = await pythonBotStore.getBotResponse('b1', 'Hey');

      expect(result).toBe('Howdy');
      expect(state.chatHistory['b1'][0].response).toBe('Howdy');
    });

    it('should handle error', async () => {
      (Python.getBotResponse as any).mockRejectedValue(new Error('Response failed'));
      await expect(pythonBotStore.getBotResponse('b1', 'Hi')).rejects.toThrow('Response failed');
      expect(state.error).toBe('Response failed');
    });
  });

  describe('toggleBot', () => {
    it('should update bot active status', async () => {
      state.bots = [{ id: 'b1', isActive: false }, { id: 'b2', isActive: true }];
      (Python.toggleBot as any).mockResolvedValue(undefined);

      await pythonBotStore.toggleBot('b1', true);

      expect(state.bots[0].isActive).toBe(true);
      expect(state.bots[1].isActive).toBe(true);
    });

    it('should handle error', async () => {
      state.bots = [{ id: 'b1', isActive: false }];
      (Python.toggleBot as any).mockRejectedValue(new Error('Toggle failed'));
      await expect(pythonBotStore.toggleBot('b1', true)).rejects.toThrow('Toggle failed');
      expect(state.error).toBe('Toggle failed');
    });
  });

  describe('deleteBot', () => {
    it('should remove bot from list', async () => {
      state.bots = [{ id: 'b1' }, { id: 'b2' }];
      (Python.deleteBot as any).mockResolvedValue(undefined);

      await pythonBotStore.deleteBot('b1');

      expect(state.bots).toHaveLength(1);
      expect(state.bots[0].id).toBe('b2');
    });

    it('should handle error', async () => {
      state.bots = [{ id: 'b1' }];
      (Python.deleteBot as any).mockRejectedValue(new Error('Delete failed'));
      await expect(pythonBotStore.deleteBot('b1')).rejects.toThrow('Delete failed');
      expect(state.error).toBe('Delete failed');
      expect(state.bots).toHaveLength(1);
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.bots = [{ id: 'b1' }];
      state.activeBot = { id: 'b1' };
      state.chatHistory = { b1: [{ message: 'hi', response: 'hello' }] };
      state.error = 'err';
      pythonBotStore.reset();
      expect(state.bots).toEqual([]);
      expect(state.activeBot).toBeNull();
      expect(state.chatHistory).toEqual({});
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Python.createBot as any).mockRejectedValue(new Error('test'));
      try { await pythonBotStore.createBot({}); } catch (e) {}
      expect(state.error).toBe('test');
      pythonBotStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
