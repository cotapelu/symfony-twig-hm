import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import {
  ApiClient,
  PHP,
  Go,
  Python,
  Rust,
  Zig,
  Elixir,
  fetchCalls,
  startCall,
  joinCall,
  subscribeToTopic,
  connectToMercure,
  fetchConversations,
  sendMessageAPI,
  updateProfile,
  loginAPI,
  registerAPI,
  markMessageRead,
  sendSMSVerification,
  verifyPhoneCode,
  fetchDashboardStats,
} from './api';
import type { User, Message, Conversation, Group, FileItem } from './api';

// Mock storage
let storage: Record<string, string>;

function createFetchMock() {
  return vi.fn();
}

function mockJsonResponse<T>(data: T, status = 200) {
  return {
    ok: true,
    status,
    json: vi.fn().mockResolvedValue(data),
  } as Response;
}

describe('ApiClient', () => {
  let client: ApiClient;
  let fetchMock: ReturnType<typeof createFetchMock>;
  let localStorageMock: any;

  beforeEach(() => {
    storage = {};
    localStorageMock = {
      getItem: vi.fn((key: string) => storage[key] ?? null),
      setItem: vi.fn((key: string, value: string) => { storage[key] = value; }),
      removeItem: vi.fn((key: string) => { delete storage[key]; }),
      clear: vi.fn(() => { Object.keys(storage).forEach(k => delete storage[k]); }),
    };
    Object.defineProperty(global, 'localStorage', { value: localStorageMock, writable: true });

    fetchMock = createFetchMock();
    global.fetch = fetchMock;

    try {
      Object.defineProperty(navigator, 'onLine', { value: true, writable: true, configurable: true });
    } catch { }

    client = new ApiClient('https://api.test.com');
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('constructor', () => {
    it('sets custom baseUrl', () => {
      expect(client.baseUrl).toBe('https://api.test.com');
    });

    it('uses default API_BASE_URL when no argument', () => {
      const defaultClient = new ApiClient();
      expect(defaultClient.baseUrl).toBe('/api');
    });

    it('initializes defaultHeaders with Content-Type', () => {
      expect(client.defaultHeaders).toEqual({ 'Content-Type': 'application/json' });
    });
  });

  describe('getAuthHeader', () => {
    it('returns Authorization header when token exists in localStorage', () => {
      storage['auth_token'] = 'abc123';
      const headers = (client as any).getAuthHeader();
      expect(headers).toEqual({ Authorization: 'Bearer abc123' });
    });

    it('returns empty object when no token', () => {
      storage['auth_token'] = null;
      const headers = (client as any).getAuthHeader();
      expect(headers).toEqual({});
    });
  });

  describe('request', () => {
    it('successful GET request returns JSON data', async () => {
      const data = { users: [] };
      fetchMock.mockResolvedValue(mockJsonResponse(data));
      const result = await client.get<{ users: any[] }>('/users');
      expect(result).toEqual(data);
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/users',
        expect.objectContaining({ method: 'GET' })
      );
    });

    it('successful POST with JSON body', async () => {
      const data = { id: 1 };
      fetchMock.mockResolvedValue(mockJsonResponse(data));
      const result = await client.post<{ id: number }>('/items', { name: 'test' });
      expect(result).toEqual(data);
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/items',
        expect.objectContaining({
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name: 'test' }),
        })
      );
    });

    it('successful PUT request', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ updated: true }));
      const result = await client.put('/update', { value: 42 });
      expect(result).toEqual({ updated: true });
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/update',
        expect.objectContaining({ method: 'PUT' })
      );
    });

    it('successful DELETE request', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ deleted: true }));
      const result = await client.delete<{ deleted: boolean }>('/resource/123');
      expect(result).toEqual({ deleted: true });
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/resource/123',
        expect.objectContaining({ method: 'DELETE' })
      );
    });

    it('GET with query parameters', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ page: 1 }));
      await client.get('/list', { page: 1, limit: 10 });
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/list?page=1&limit=10',
        expect.objectContaining({ method: 'GET' })
      );
    });

    it('includes Authorization header when token present', async () => {
      storage['auth_token'] = 'token456';
      fetchMock.mockResolvedValue(mockJsonResponse({ ok: true }));
      await client.get('/protected');
      expect(fetchMock).toHaveBeenCalledWith(
        expect.stringMatching(/https:\/\/api\.test\.com/),
        expect.objectContaining({
          headers: expect.objectContaining({
            Authorization: 'Bearer token456',
          }),
        })
      );
    });

    it('sends FormData without JSON Content-Type', async () => {
      const formData = new FormData();
      formData.append('file', new File([''], 'test.txt'));
      fetchMock.mockResolvedValue(mockJsonResponse({ uploaded: true }));
      await client.post('/upload', formData);
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/upload',
        expect.objectContaining({
          headers: expect.not.objectContaining({ 'Content-Type': 'application/json' }),
          body: formData,
        })
      );
    });

    it('handles non-ok response and throws with error message', async () => {
      const notOkResp = {
        ok: false,
        status: 404,
        json: vi.fn().mockResolvedValue({ error: 'Not found' }),
      } as any;
      fetchMock.mockResolvedValue(notOkResp);
      await expect(client.get('/missing')).rejects.toThrow('Not found');
    });

    it('handles non-ok response with no JSON error, uses generic message', async () => {
      const badResp = {
        ok: false,
        status: 500,
        json: vi.fn().mockResolvedValue({}),
      } as any;
      fetchMock.mockResolvedValue(badResp);
      await expect(client.get('/error')).rejects.toThrow('HTTP 500');
    });

    it('handles network error', async () => {
      fetchMock.mockRejectedValue(new TypeError('Network failure'));
      await expect(client.get('/fail')).rejects.toThrow('Network failure');
    });

    it('clears timeout on success', async () => {
      const clearTimeoutSpy = vi.spyOn(global, 'clearTimeout');
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await client.get('/ok');
      expect(clearTimeoutSpy).toHaveBeenCalled();
    });

    it('uses custom headers option via request', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await client.request('/custom', { headers: { 'X-Custom': 'yes' } });
      expect(fetchMock).toHaveBeenCalledWith(
        expect.stringMatching(/https:\/\/api\.test\.com/),
        expect.objectContaining({
          headers: expect.objectContaining({
            'X-Custom': 'yes',
          }),
        })
      );
    });
  });

  describe('convenience methods', () => {
    it('get calls request with method GET', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await client.get('/gettest');
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/gettest',
        expect.objectContaining({ method: 'GET' })
      );
    });

    it('post calls request with method POST', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await client.post('/posttest', { a: 1 });
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/posttest',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('put calls request with method PUT', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await client.put('/puttest', { b: 2 });
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/puttest',
        expect.objectContaining({ method: 'PUT' })
      );
    });

    it('delete calls request with method DELETE', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await client.delete('/deltest');
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.test.com/deltest',
        expect.objectContaining({ method: 'DELETE' })
      );
    });
  });
});

describe('API Groups (PHP, Go, Python, Rust, Zig, Elixir)', () => {
  let fetchMock: ReturnType<typeof createFetchMock>;

  beforeEach(() => {
    fetchMock = createFetchMock();
    global.fetch = fetchMock;
  });

  describe('PHP', () => {
    it('login calls POST /auth/login with credentials', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ token: 't', user: {} as User }));
      await PHP.login('email@example.com', 'pass');
      expect(fetchMock).toHaveBeenCalledWith(
        '/api/auth/login',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({ email: 'email@example.com', password: 'pass' }),
        })
      );
    });

    it('register calls POST /auth/register', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ token: 't', user: {} as User }));
      await PHP.register('e@example.com', 'pw', 'Name');
      expect(fetchMock).toHaveBeenCalledWith(
        '/api/auth/register',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('getMessages calls GET /messages', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ conversations: [] }));
      await PHP.getMessages();
      expect(fetchMock).toHaveBeenCalledWith(
        '/api/messages',
        expect.objectContaining({ method: 'GET' })
      );
    });

    it('sendMessage calls POST /messages', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({} as Message));
      await PHP.sendMessage({ conversationId: 'c1', content: 'Hi' });
      expect(fetchMock).toHaveBeenCalledWith(
        '/api/messages',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({ conversationId: 'c1', content: 'Hi', files: undefined }),
        })
      );
    });

    it('uploadMessageFile uses FormData', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({} as FileItem));
      const file = new File([''], 'f.txt');
      await PHP.uploadMessageFile(file, 'conv123');
      const [url, options] = fetchMock.mock.calls[0];
      expect(url).toBe('/api/messages/upload');
      expect(options.body instanceof FormData).toBe(true);
    });

    it('createGroup calls POST /groups', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({} as Group));
      await PHP.createGroup({ name: 'G' });
      expect(fetchMock).toHaveBeenCalledWith(
        '/api/groups',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('uploadFile uses FormData and includes conversationId when provided', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({} as FileItem));
      const file = new File(['content'], 'doc.pdf');
      await PHP.uploadFile(file, 'conv42');
      const [url, options] = fetchMock.mock.calls[0];
      expect(url).toBe('/api/files/upload');
      const formData = options.body as FormData;
      expect(formData.get('file')).toBe(file);
      expect(formData.get('conversationId')).toBe('conv42');
    });
  });

  describe('Go', () => {
    it('createBooking calls POST /bookings', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await Go.createBooking({ when: 'now' });
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:8080/api/bookings',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('getBookings calls GET /bookings', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse([]));
      await Go.getBookings();
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:8080/api/bookings',
        expect.objectContaining({ method: 'GET' })
      );
    });

    it('calculateRoute sends POST with from/to/mode', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await Go.calculateRoute({ lat: 1, lng: 2 }, { lat: 3, lng: 4 }, 'walking');
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:8080/api/location/route',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({ from: { lat: 1, lng: 2 }, to: { lat: 3, lng: 4 }, mode: 'walking' }),
        })
      );
    });

    it('getNearbyServices sends GET with params', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse([]));
      await Go.getNearbyServices({ latitude: 10, longitude: 20, radius: 10, type: 'medical' });
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:8080/api/location/nearby?latitude=10&longitude=20&radius=10&type=medical',
        expect.objectContaining({ method: 'GET' })
      );
    });
  });

  describe('Python', () => {
    it('getAIChatbotResponse calls /ai/chat', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ response: 'Hi' }));
      await Python.getAIChatbotResponse('Hello');
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:8000/api/ai/chat',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('analyzeSentiment calls /ai/sentiment', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ sentiment: 'positive', score: 0.8 }));
      await Python.analyzeSentiment('Great!');
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:8000/api/ai/sentiment',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('translateText sends source/target', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ translated: 'Bonjour' }));
      await Python.translateText('Hello', 'en', 'fr');
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:8000/api/ai/translate',
        expect.objectContaining({
          body: JSON.stringify({ text: 'Hello', source: 'en', target: 'fr' }),
        })
      );
    });

    it('getProducts can accept optional params', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse([]));
      await Python.getProducts({ category: 'books', limit: 5 });
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:8000/api/ecommerce/products?category=books&limit=5',
        expect.objectContaining({ method: 'GET' })
      );
    });
  });

  describe('Rust', () => {
    it('generateKeyPair calls POST /crypto/generate-keypair', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ publicKey: 'pub', privateKey: 'priv' }));
      await Rust.generateKeyPair();
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:3000/api/crypto/generate-keypair',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('uploadFile sends FormData with encryptionEnabled flag', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      const file = new File(['data'], 'data.bin');
      await Rust.uploadFile(file, true);
      const [url, options] = fetchMock.mock.calls[0];
      expect(url).toBe('http://localhost:3000/api/files/upload');
      const formData = options.body as FormData;
      expect(formData.get('file')).toBe(file);
      expect(formData.get('encryptionEnabled')).toBe('true');
    });
  });

  describe('Zig', () => {
    it('getWebRTCConfig calls GET /webrtc/config', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({ iceServers: [] }));
      await Zig.getWebRTCConfig();
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:4000/api/webrtc/config',
        expect.objectContaining({ method: 'GET' })
      );
    });

    it('moderateImage uses FormData', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      const file = new File(['img'], 'img.jpg');
      await Zig.moderateImage(file);
      const formData = fetchMock.mock.calls[0][1].body as FormData;
      expect(formData.get('image')).toBe(file);
    });
  });

  describe('Elixir', () => {
    it('getStories calls GET /stories', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse([]));
      await Elixir.getStories();
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:5000/api/stories',
        expect.objectContaining({ method: 'GET' })
      );
    });

    it('createStory sends POST /stories', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await Elixir.createStory({ content: 'Hello', type: 'image' });
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:5000/api/stories',
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('transfer sends POST with from/to/amount', async () => {
      fetchMock.mockResolvedValue(mockJsonResponse({}));
      await Elixir.transfer('wallet1', 'wallet2', 100);
      expect(fetchMock).toHaveBeenCalledWith(
        'http://localhost:5000/api/wallet/transfer',
        expect.objectContaining({
          body: JSON.stringify({ from: 'wallet1', to: 'wallet2', amount: 100 }),
        })
      );
    });
  });
});

// Legacy helpers smoke tests
describe('Legacy helper functions', () => {
  it('fetchCalls returns empty array', async () => {
    const result = await fetchCalls();
    expect(result).toEqual([]);
  });

  it('startCall and joinCall are defined', () => {
    expect(typeof startCall).toBe('function');
    expect(typeof joinCall).toBe('function');
  });

  it('subscribeToTopic and connectToMercure log to console', () => {
    const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
    subscribeToTopic('topic');
    expect(logSpy).toHaveBeenCalledWith('Subscribed to:', 'topic');
    connectToMercure();
    expect(logSpy).toHaveBeenCalledWith('Connected to Mercure');
    logSpy.mockRestore();
  });

  it('fetchConversations delegates to PHP.getConversations', async () => {
    const spy = vi.spyOn(PHP, 'getConversations').mockResolvedValue([] as Conversation[]);
    await fetchConversations();
    expect(spy).toHaveBeenCalled();
    spy.mockRestore();
  });

  it('sendMessageAPI delegates to PHP.sendMessage', async () => {
    const spy = vi.spyOn(PHP, 'sendMessage').mockResolvedValue({} as Message);
    await sendMessageAPI('conv', 'text');
    expect(spy).toHaveBeenCalledWith({ conversationId: 'conv', content: 'text' });
    spy.mockRestore();
  });

  it('updateProfile delegates to PHP.updateProfile', async () => {
    const user = {} as User;
    const spy = vi.spyOn(PHP, 'updateProfile').mockResolvedValue(user);
    await updateProfile({ name: 'New' });
    expect(spy).toHaveBeenCalledWith({ name: 'New' });
    spy.mockRestore();
  });

  it('loginAPI and registerAPI delegate correctly', async () => {
    const loginSpy = vi.spyOn(PHP, 'login').mockResolvedValue({ token: 't', user: {} as User });
    await loginAPI({ email: 'e@test.com', password: 'pw' });
    expect(loginSpy).toHaveBeenCalledWith('e@test.com', 'pw');
    loginSpy.mockRestore();

    const regSpy = vi.spyOn(PHP, 'register').mockResolvedValue({ token: 't', user: {} as User });
    await registerAPI({ email: 'e@test.com', password: 'pw', name: 'Test' });
    expect(regSpy).toHaveBeenCalledWith('e@test.com', 'pw', 'Test');
    regSpy.mockRestore();
  });

  it('markMessageRead resolves without error', async () => {
    await expect(markMessageRead('msg1')).resolves.toBeUndefined();
  });

  it('sendSMSVerification forwards to PHP', async () => {
    const spy = vi.spyOn(PHP, 'sendSMSVerification').mockResolvedValue({});
    await sendSMSVerification('+1234567890', 'Display');
    expect(spy).toHaveBeenCalledWith('+1234567890');
    spy.mockRestore();
  });

  it('verifyPhoneCode returns user and token', async () => {
    const result = await verifyPhoneCode('+1234567890', '123456', 'Display');
    expect(result).toEqual({ user: { id: '1', name: 'Display', email: '' }, token: 'demo-token' });
  });

  it('fetchDashboardStats returns empty stats object', async () => {
    const stats = await fetchDashboardStats();
    expect(stats).toEqual({ messages: 0, groups: 0, files: 0, calls: 0 });
  });
});
