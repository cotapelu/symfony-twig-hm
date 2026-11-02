// Test utilities for TypeScript
import {
  render,
  screen,
  fireEvent,
  waitFor,
  act,
} from "@testing-library/svelte";
import { describe, it, expect, beforeEach, vi, type Mock } from "vitest";

// Re-export everything from testing library
export * from "@testing-library/svelte";

// Custom test utilities
export function createMockFunction<T extends (...args: any[]) => any>(
  implementation?: (...args: Parameters<T>) => ReturnType<T>,
): Mock<Parameters<T>, ReturnType<T>> {
  return vi.fn(implementation || (() => undefined)) as Mock<
    Parameters<T>,
    ReturnType<T>
  >;
}

export async function waitForElementToBeRemoved(
  selector: string,
  options?: { timeout?: number },
): Promise<void> {
  await waitFor(
    () => {
      const element = document.querySelector(selector);
      if (element) {
        throw new Error(`Element ${selector} is still present`);
      }
    },
    { timeout: options?.timeout ?? 5000 },
  );
}

export function getByTextWithMarkup(
  container: HTMLElement,
  text: string | RegExp,
): Element {
  const elements = Array.from(container.querySelectorAll("*"));
  for (const element of elements) {
    if (element.textContent && text instanceof RegExp) {
      if (text.test(element.textContent)) {
        return element;
      }
    } else if (
      typeof text === "string" &&
      element.textContent?.includes(text)
    ) {
      return element;
    }
  }
  throw new Error(`Unable to find element with text: ${text}`);
}

// Mock API function
export function createMockApiFunction<T>(
  mockData: T,
  delay: number = 0,
): () => Promise<T> {
  return vi.fn(
    () =>
      new Promise((resolve) => {
        setTimeout(() => resolve(mockData), delay);
      }),
  );
}

// Mock store factory
export function createMockStore<T>(initialValue: T) {
  const { subscribe, set, update } = vi.fn((fn) => {
    if (typeof fn === "function") {
      return fn(initialValue);
    }
    return initialValue;
  }) as any;

  return {
    subscribe,
    set: vi.fn(set),
    update: vi.fn(update),
    setValue: (value: T) => {
      initialValue = value;
      set(value);
    },
  };
}

// Custom render options
export interface CustomRenderOptions {
  props?: Record<string, any>;
  store?: Record<string, any>;
  route?: string;
}

export function setupComponent(
  component: any,
  options: CustomRenderOptions = {},
) {
  return {
    component,
    props: options.props,
    store: options.store,
    route: options.route,
  };
}

// Generate unique test IDs
let testIdCounter = 0;
export function generateTestId(): string {
  return `test-id-${++testIdCounter}-${Date.now()}`;
}

// Mock async operation
export function mockAsyncOperation<T>(
  data: T,
  shouldFail: boolean = false,
  errorMessage: string = "Async operation failed",
): Promise<T> {
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      if (shouldFail) {
        reject(new Error(errorMessage));
      } else {
        resolve(data);
      }
    }, 100);
  });
}

// Deep clone utility for tests
export function deepClone<T>(obj: T): T {
  return JSON.parse(JSON.stringify(obj));
}

// Compare objects deeply (for assertions)
export function expectObjectToEqual(actual: any, expected: any): void {
  expect(JSON.stringify(actual)).toEqual(JSON.stringify(expected));
}

// Wait for a condition
export async function waitForCondition(
  condition: () => boolean,
  options?: { timeout?: number; interval?: number },
): Promise<void> {
  const timeout = options?.timeout ?? 5000;
  const interval = options?.interval ?? 50;
  const startTime = Date.now();

  while (Date.now() - startTime < timeout) {
    if (condition()) {
      return;
    }
    await new Promise((resolve) => setTimeout(resolve, interval));
  }

  throw new Error("Condition not met within timeout");
}

// Mock props for Svelte components
export function mockProps<T extends object>(overrides: Partial<T> = {}): T {
  return {
    ...overrides,
  } as T;
}

// Create test user
export function createTestUser(
  overrides: Partial<import("$lib/types").User> = {},
): import("$lib/types").User {
  return {
    id: "test-user-1",
    name: "Test User",
    email: "test@example.com",
    avatar: "https://example.com/avatar.png",
    bio: "Test bio",
    ...overrides,
  };
}

// Create test message
export function createTestMessage(
  overrides: Partial<import("$lib/types").Message> = {},
): import("$lib/types").Message {
  return {
    id: "test-message-1",
    conversationId: "test-conv-1",
    content: "Test message content",
    senderId: "test-user-1",
    type: "text",
    createdAt: new Date().toISOString(),
    ...overrides,
  };
}

// Create test conversation
export function createTestConversation(
  overrides: Partial<import("$lib/types").Conversation> = {},
): import("$lib/types").Conversation {
  return {
    id: "test-conv-1",
    title: "Test Conversation",
    type: "direct",
    participants: ["test-user-1", "test-user-2"],
    unreadCount: 0,
    ...overrides,
  };
}

// Create test group
export function createTestGroup(
  overrides: Partial<import("$lib/types").Group> = {},
): import("$lib/types").Group {
  return {
    id: "test-group-1",
    name: "Test Group",
    description: "Test group description",
    members: ["test-user-1", "test-user-2"],
    memberCount: 2,
    ...overrides,
  };
}
