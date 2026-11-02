import "@testing-library/jest-dom";

// Polyfill Svelte 5 $state rune for non-Svelte environments (tests)
if (typeof globalThis !== 'undefined' && !globalThis.$state) {
  globalThis.$state = (initial) => (typeof initial === 'function' ? initial() : initial);
}

// Ensure window exists
if (typeof window === 'undefined') {
  // @ts-ignore
  globalThis.window = globalThis;
}

// Polyfill IntersectionObserver for tests
if (typeof window !== 'undefined' && !window.IntersectionObserver) {
  window.IntersectionObserver = class IntersectionObserver {
    constructor(callback) {
      this.callback = callback;
      this.elements = new Set();
    }
    observe(element) {
      this.elements.add(element);
      setTimeout(() => this.callback([{ target: element, isIntersecting: true, intersectionRatio: 1 }]), 0);
    }
    unobserve(element) {
      this.elements.delete(element);
    }
    disconnect() {
      this.elements.clear();
    }
    takeRecords() {
      return [];
    }
  };
}

// Create a context map for setContext/getContext
const contextMap = new Map();

function getOrCreateContext() {
  // Use a stable component ID for tests
  const componentId = 'test-component';
  if (!contextMap.has(componentId)) {
    contextMap.set(componentId, new Map());
  }
  return contextMap.get(componentId);
}

// Don't mock Svelte lifecycle hooks - they need to work properly in tests
// if (typeof vi !== 'undefined') {
//   vi.mock('svelte', async () => {
//     const actual = await vi.importActual('svelte');
//     return {
//       ...actual,
//       onMount: vi.fn(),
//       onDestroy: vi.fn(),
//       // Don't mock createEventDispatcher - let the real one work with testing library
//       tick: vi.fn(),
//       setContext: (key, value) => {
//         const context = getOrCreateContext();
//         context.set(key, value);
//       },
//       getContext: (key) => {
//         const context = getOrCreateContext();
//         return context.get(key);
//       },
//     };
//   });
// }

// Create mutable backing objects with default implementations
const navigatorData = { ...(typeof window !== 'undefined' && window.navigator ? window.navigator : {}), onLine: true };
const locationData = { ...(typeof window !== 'undefined' ? window.location : {}) };

// Simple in-memory storage that mimics the Storage API
const createStorage = () => {
  const store = {};
  return {
    getItem: (key) => (key in store ? store[key] : null),
    setItem: (key, value) => { store[key] = String(value); },
    removeItem: (key) => { delete store[key]; },
    clear: () => { Object.keys(store).forEach(k => delete store[k]); },
    get length() { return Object.keys(store).length; },
    key: (i) => Object.keys(store)[i] || null
  };
};

const localStorageData = createStorage();
const sessionStorageData = createStorage();

// Directly define writable properties to allow test overrides without merging.
// This avoids errors when test mocks have read-only properties like `length`.
function defineWritable(obj, prop, value) {
  try {
    Object.defineProperty(obj, prop, {
      value,
      writable: true,
      configurable: true,
      enumerable: true
    });
  } catch (e) {
    try { obj[prop] = value; } catch (e2) {}
  }
}

// Apply to window
if (typeof window !== 'undefined') {
  defineWritable(window, 'navigator', navigatorData);
  defineWritable(window, 'location', locationData);
  defineWritable(window, 'localStorage', localStorageData);
  defineWritable(window, 'sessionStorage', sessionStorageData);
}

// Also expose via global
if (typeof global !== 'undefined') {
  try { global.window = window; } catch (e) {}
  defineWritable(global, 'navigator', navigatorData);
  defineWritable(global, 'location', locationData);
  defineWritable(global, 'localStorage', localStorageData);
  defineWritable(global, 'sessionStorage', sessionStorageData);
}

// Stub matchMedia for environments lacking it
if (!window.matchMedia) {
  window.matchMedia = () => ({
    matches: false,
    media: '',
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  });
}

// Mock Web Animations API to avoid errors with Svelte transitions
if (typeof window !== 'undefined' && !window.Element.prototype.animate) {
  window.Element.prototype.animate = function(keyframes, options) {
    let onfinishHandler;
    const finishedPromise = Promise.resolve().then(() => {
      if (onfinishHandler) onfinishHandler();
    });
    return {
      cancel: () => {},
      finish: () => {},
      pause: () => {},
      play: () => {},
      reverse: () => {},
      addEventListener: () => {},
      removeEventListener: () => {},
      persist: () => {},
      get finished() { return finishedPromise; },
      set onfinish(cb) { onfinishHandler = cb; },
      get onfinish() { return onfinishHandler; },
    };
  };
}