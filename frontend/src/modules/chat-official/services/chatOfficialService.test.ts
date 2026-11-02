import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ChatOfficialService } from './chatOfficialService';

global.fetch = vi.fn();

describe('ChatOfficialService', () => {
  let service: ChatOfficialService;

  beforeEach(() => {
    service = new ChatOfficialService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getChats', () => {
    it('should fetch official chats', async () => {
      const chats = [{ id: 'c1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(chats));
      const result = await service.getChats();
      expect(result).toEqual(chats);
      expect(fetch).toHaveBeenCalledWith('/api/chat-official');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.getChats()).rejects.toThrow('Failed to fetch chats');
    });
  });

  describe('getChat', () => {
    it('should fetch chat by id', async () => {
      const chat = { id: 'c1' };
      (global.fetch as any).mockResolvedValue(mockResponse(chat));
      const result = await service.getChat('c1');
      expect(result).toEqual(chat);
      expect(fetch).toHaveBeenCalledWith('/api/chat-official/c1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.getChat('c1')).rejects.toThrow('Failed to fetch chat');
    });
  });

  describe('createChat', () => {
    it('should create a chat with departmentId', async () => {
      const deptId = 'dept1';
      const chat = { id: 'new', departmentId: deptId };
      (global.fetch as any).mockResolvedValue(mockResponse(chat));
      const result = await service.createChat(deptId);
      expect(result).toEqual(chat);
      expect(fetch).toHaveBeenCalledWith('/api/chat-official', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ departmentId: deptId })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.createChat('dept1')).rejects.toThrow('Failed to create chat');
    });
  });

  describe('sendMessage', () => {
    it('should send a message', async () => {
      const request = { chatId: 'c1', content: 'Hello' };
      const message = { id: 'm1', ...request };
      (global.fetch as any).mockResolvedValue(mockResponse(message));
      const result = await service.sendMessage(request as any);
      expect(result).toEqual(message);
      expect(fetch).toHaveBeenCalledWith('/api/chat-official/messages', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.sendMessage({ chatId: 'c1', content: 'Hi' } as any)).rejects.toThrow('Failed to send message');
    });
  });

  describe('closeChat', () => {
    it('should close a chat', async () => {
      const chat = { id: 'c1', closed: true };
      (global.fetch as any).mockResolvedValue(mockResponse(chat));
      const result = await service.closeChat('c1');
      expect(result).toEqual(chat);
      expect(fetch).toHaveBeenCalledWith('/api/chat-official/c1/close', { method: 'PUT' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.closeChat('c1')).rejects.toThrow('Failed to close chat');
    });
  });
});
