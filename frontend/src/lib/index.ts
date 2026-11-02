// Index - Export all TypeScript modules
// Unified exports from TypeScript files

// API Client
export { default as api, PHP, Go, Python, Rust, Zig, Elixir } from "./api";
export type { RequestOptions } from "./api";

// Types
export * from "./types";

// i18n
export {
  default as i18n,
  _,
  n,
  formatNumber,
  formatDate,
  formatRelativeTime,
  locale,
  isRTL,
  currentLocaleConfig,
  initI18n,
  setLocale,
  SUPPORTED_LOCALES,
  type Locale,
  type LocaleConfig,
  type TranslationMap,
} from "./i18n";

// Typing Handler
export { typingManager, TypingHandler, TypingManager } from "./typingHandler";
export type {
  TypingHandlerOptions,
  TypingEventData,
  TypingStopData,
  RemoteTypingEvent,
  TypingUser,
  UserInfo,
} from "./typingHandler";

// Config
export * from "./config";
