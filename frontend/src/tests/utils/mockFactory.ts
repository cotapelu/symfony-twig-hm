/** Test data factories for frontend */
export function randomUser(overrides: Partial<User> = {}): User {
  return {
    id: `user-${Math.random().toString(36).substr(2, 9)}`,
    username: `user${Math.floor(Math.random() * 1000)}`,
    email: `test@example.com`,
    roles: ['user'],
    ...overrides,
  };
}

export function randomCitizen(overrides: Partial<Citizen> = {}): Citizen {
  return {
    id: `cit-${Math.random().toString(36).substr(2, 9)}`,
    name: 'Nguyen Van A',
    citizenId: `ID${Math.random().toString(36).substr(2, 9)}`,
    ...overrides,
  };
}

export function randomConversation(overrides: Partial<Conversation> = {}): Conversation {
  return {
    id: `conv-${Math.random().toString(36).substr(2, 9)}`,
    participants: [randomUser(), randomUser()],
    type: 'direct',
    createdAt: new Date().toISOString(),
    ...overrides,
  };
}

export function randomMessage(overrides: Partial<Message> = {}): Message {
  return {
    id: `msg-${Math.random().toString(36).substr(2, 9)}`,
    conversationId: 'conv-1',
    senderId: 'user1',
    content: 'Hello world',
    createdAt: new Date().toISOString(),
    ...overrides,
  };
}

// Types (simplified for tests)
export interface User {
  id: string;
  username: string;
  email: string;
  roles: string[];
}

export interface Citizen {
  id: string;
  name: string;
  citizenId: string;
}

export interface Conversation {
  id: string;
  participants: User[];
  type: string;
  createdAt: string;
}

export interface Message {
  id: string;
  conversationId: string;
  senderId: string;
  content: string;
  createdAt: string;
}
