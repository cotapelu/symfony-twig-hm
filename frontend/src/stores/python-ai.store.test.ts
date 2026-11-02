import { describe, it, expect, vi, beforeEach } from 'vitest';
import { pythonAIStore } from './python-all';
import { Python } from '../lib/api';

vi.mock('../lib/api', () => ({
  Python: {
    getSmartReplies: vi.fn(),
    analyzeSentiment: vi.fn(),
    translateText: vi.fn(),
    moderateContent: vi.fn(),
    generateSummary: vi.fn(),
  }
}));

describe('PythonAIStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    pythonAIStore.reset();
    pythonAIStore.subscribe(s => { state = s; });
  });

  describe('getSmartReplies', () => {
    it('should fetch and store smart replies', async () => {
      const replies = ['Hi', 'Hello'];
      (Python.getSmartReplies as any).mockResolvedValue(replies);
      const context = { userId: 'u1' };

      const result = await pythonAIStore.getSmartReplies(context);

      expect(result).toEqual(replies);
      expect(state.smartReplies).toEqual(replies);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.getSmartReplies as any).mockRejectedValue(new Error('Smart failed'));
      await expect(pythonAIStore.getSmartReplies({})).rejects.toThrow('Smart failed');
      expect(state.error).toBe('Smart failed');
    });
  });

  describe('analyzeSentiment', () => {
    it('should store sentiment analysis', async () => {
      const sentiment = { sentiment: 'positive', score: 0.8 };
      (Python.analyzeSentiment as any).mockResolvedValue(sentiment);

      const result = await pythonAIStore.analyzeSentiment('I love this');

      expect(result).toEqual(sentiment);
      expect(state.sentimentAnalysis).toEqual(sentiment);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.analyzeSentiment as any).mockRejectedValue(new Error('Analyze failed'));
      await expect(pythonAIStore.analyzeSentiment('bad')).rejects.toThrow('Analyze failed');
      expect(state.error).toBe('Analyze failed');
    });
  });

  describe('translateText', () => {
    it('should store translation with composite key', async () => {
      const translation = { translated: 'Hola' };
      (Python.translateText as any).mockResolvedValue(translation);
      const text = 'Hello';
      const targetLang = 'es';

      const result = await pythonAIStore.translateText(text, 'en', targetLang);

      expect(result).toEqual(translation);
      const key = `${text}_${targetLang}`;
      expect(state.translations[key]).toEqual(translation);
    });

    it('should handle error', async () => {
      (Python.translateText as any).mockRejectedValue(new Error('Translate failed'));
      await expect(pythonAIStore.translateText('test', 'en', 'fr')).rejects.toThrow('Translate failed');
      expect(state.error).toBe('Translate failed');
    });
  });

  describe('moderateContent', () => {
    it('should store moderation result under timestamp key', async () => {
      const moderation = { safe: true, flags: [] };
      (Python.moderateContent as any).mockResolvedValue(moderation);

      const result = await pythonAIStore.moderateContent('spam');

      expect(result).toEqual(moderation);
      expect(Object.keys(state.moderationResults).length).toBeGreaterThan(0);
    });

    it('should handle error', async () => {
      (Python.moderateContent as any).mockRejectedValue(new Error('Moderate failed'));
      await expect(pythonAIStore.moderateContent('bad')).rejects.toThrow('Moderate failed');
      expect(state.error).toBe('Moderate failed');
    });
  });

  describe('generateSummary', () => {
    it('should store summary', async () => {
      const summary = { id: 's1', text: 'Summary' };
      (Python.generateSummary as any).mockResolvedValue(summary);

      const result = await pythonAIStore.generateSummary('conv1');

      expect(result).toEqual(summary);
      expect(state.summary).toEqual(summary);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.generateSummary as any).mockRejectedValue(new Error('Summary failed'));
      await expect(pythonAIStore.generateSummary('c1')).rejects.toThrow('Summary failed');
      expect(state.error).toBe('Summary failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.smartReplies = ['a'];
      state.sentimentAnalysis = { sentiment: 'neg' };
      state.translations = { en_fr: { translated: 'Bonjour' } };
      state.moderationResults = { 1: { safe: true } };
      state.summary = { text: 'sum' };
      state.error = 'err';
      pythonAIStore.reset();
      expect(state.smartReplies).toEqual([]);
      expect(state.sentimentAnalysis).toBeNull();
      expect(state.translations).toEqual({});
      expect(state.moderationResults).toEqual({});
      expect(state.summary).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Python.getSmartReplies as any).mockRejectedValue(new Error('test'));
      try { await pythonAIStore.getSmartReplies({}); } catch (e) {}
      expect(state.error).toBe('test');
      pythonAIStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
