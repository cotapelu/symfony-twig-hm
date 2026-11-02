// App configuration - Updated for multi-service architecture
export const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:80';
export const GO_API_BASE = import.meta.env.VITE_GO_API_BASE || 'http://localhost:8080';
export const PYTHON_API_BASE = import.meta.env.VITE_PYTHON_API_BASE || 'http://localhost:8000';
export const RUST_API_BASE = import.meta.env.VITE_RUST_API_BASE || 'http://localhost:9000';
export const ZIG_API_BASE = import.meta.env.VITE_ZIG_API_BASE || 'http://localhost:9001';
export const ELIXIR_API_BASE = import.meta.env.VITE_ELIXIR_API_BASE || 'http://localhost:9002';
export const MERCURE_HUB_URL = import.meta.env.VITE_MERCURE_HUB_URL || 'http://localhost:3000/.well-known/mercure';

export const APP_NAME = 'CommUnity';
export const APP_VERSION = '1.0.0';

type FeatureFlags = {
  AI_ASSISTANT: boolean;
  WEBRTC_CALLS: boolean;
  FILE_SHARING: boolean;
  PUSH_NOTIFICATIONS: boolean;
  DARK_MODE: boolean;
  MULTI_LANGUAGE: boolean;
  PWA: boolean;
  PAYMENTS: boolean;
  SERVICES: boolean;
  LOCATION: boolean;
  BROADCAST: boolean;
  VIDEO_CONFERENCE: boolean;
  INTEGRATION: boolean;
  ENCRYPTION: boolean;
  MARKETPLACE: boolean;
  VOICE_MAIL: boolean;
  CONTENT_MODERATION: boolean;
  EVENTS: boolean;
  ECOSYSTEM_WALLET: boolean;
};

type Endpoints = {
  AUTH: {
    LOGIN: string;
    REGISTER: string;
    LOGOUT: string;
    REFRESH: string;
    VALIDATE: string;
  };
  MESSAGES: {
    LIST: string;
    SEND: string;
    REACTIONS: string;
    READ: string;
    UPLOAD: string;
  };
  CONVERSATIONS: {
    LIST: string;
    CREATE: string;
  };
  USERS: {
    PROFILE: string;
    SETTINGS: string;
  };
  AI: {
    SMART_REPLIES: string;
    MODERATE: string;
    TRANSLATE: string;
  };
};

type GoEndpoints = {
  PAYMENTS: {
    WALLETS: string;
    TRANSACTIONS: string;
  };
  SERVICES: {
    BOOKINGS: string;
    NEARBY: string;
    ROUTES: string;
  };
  LOCATION: {
    UPDATE: string;
    NEARBY: string;
    SHARING: string;
    GEOFENCE: string;
  };
  BROADCAST: {
    SEND: string;
    STATUS: string;
  };
  VIDEOCONFERENCE: {
    ROOMS: string;
  };
  INTEGRATION: {
    WEBHOOKS: string;
  };
};

type PythonEndpoints = {
  AI: {
    SMART_REPLIES: string;
    SENTIMENT: string;
    TRANSLATE: string;
    MODERATE: string;
    SUMMARY: string;
  };
  ECOMMERCE: {
    RECOMMENDATIONS: string;
    TRENDING: string;
    SEARCH: string;
    PRODUCTS: string;
    CART: string;
  };
  SEARCH: {
    SEARCH: string;
    SUGGESTIONS: string;
  };
  BOTS: {
    BOTS: string;
    CHAT: string;
  };
  ANALYTICS: {
    USERS: string;
    CONVERSATIONS: string;
    ENGAGEMENT: string;
    RETENTION: string;
    CHURN_PREDICTION: string;
  };
};

type RustEndpoints = {
  ENCRYPTION: {
    KEYS: string;
    ENCRYPT: string;
    DECRYPT: string;
  };
  FILESHARING: {
    UPLOAD: string;
    DOWNLOAD: string;
    SHARE: string;
    SHARED: string;
  };
  MARKETPLACE: {
    PRODUCTS: string;
    BID: string;
    BUY: string;
  };
};

type ZigEndpoints = {
  VOICEMAIL: {
    RECORD: string;
    TRANSCRIBE: string;
    LIST: string;
    DELETE: string;
  };
  MODERATION: {
    TEXT: string;
    IMAGE: string;
    VIDEO: string;
  };
};

type ElixirEndpoints = {
  EVENTS: {
    EVENTS: string;
    CALENDAR: string;
    RSVP: string;
  };
  WALLET: {
    BALANCE: string;
    TRANSFER: string;
    TRANSACTIONS: string;
  };
};

// Feature flags
export const FEATURES: FeatureFlags = {
  AI_ASSISTANT: true,
  WEBRTC_CALLS: true,
  FILE_SHARING: true,
  PUSH_NOTIFICATIONS: true,
  DARK_MODE: true,
  MULTI_LANGUAGE: true,
  PWA: true,
  PAYMENTS: true,
  SERVICES: true,
  LOCATION: true,
  BROADCAST: true,
  VIDEO_CONFERENCE: true,
  INTEGRATION: true,
  ENCRYPTION: true,
  MARKETPLACE: true,
  VOICE_MAIL: true,
  CONTENT_MODERATION: true,
  EVENTS: true,
  ECOSYSTEM_WALLET: true,
};

// API endpoints - PHP Main Backend
export const ENDPOINTS: Endpoints = {
  AUTH: {
    LOGIN: '/api/login',
    REGISTER: '/api/register',
    LOGOUT: '/api/logout',
    REFRESH: '/api/auth/refresh',
    VALIDATE: '/api/auth/validate',
  },
  MESSAGES: {
    LIST: '/api/messages',
    SEND: '/api/messages',
    REACTIONS: '/api/messages/reaction',
    READ: '/api/messages/read',
    UPLOAD: '/api/messages/upload',
  },
  CONVERSATIONS: {
    LIST: '/api/conversations',
    CREATE: '/api/conversations',
  },
  USERS: {
    PROFILE: '/api/user/profile',
    SETTINGS: '/api/user/settings',
  },
  AI: {
    SMART_REPLIES: '/api/ai/smart-replies',
    MODERATE: '/api/ai/moderate',
    TRANSLATE: '/api/ai/translate',
  },
};

// Go API endpoints
export const GO_ENDPOINTS: GoEndpoints = {
  PAYMENTS: {
    WALLETS: '/api/payments/wallets',
    TRANSACTIONS: '/api/payments/transactions',
  },
  SERVICES: {
    BOOKINGS: '/api/services/bookings',
    NEARBY: '/api/services/nearby',
    ROUTES: '/api/services/directions',
  },
  LOCATION: {
    UPDATE: '/api/location/update',
    NEARBY: '/api/location/nearby',
    SHARING: '/api/location/sharing',
    GEOFENCE: '/api/location/geofence',
  },
  BROADCAST: {
    SEND: '/api/broadcast/send',
    STATUS: '/api/broadcast/status',
  },
  VIDEOCONFERENCE: {
    ROOMS: '/api/videoconference/rooms',
  },
  INTEGRATION: {
    WEBHOOKS: '/api/integration/webhooks',
  },
};

// Python API endpoints
export const PYTHON_ENDPOINTS: PythonEndpoints = {
  AI: {
    SMART_REPLIES: '/api/ai/smart-replies',
    SENTIMENT: '/api/ai/sentiment',
    TRANSLATE: '/api/ai/translate',
    MODERATE: '/api/ai/moderate',
    SUMMARY: '/api/ai/summary',
  },
  ECOMMERCE: {
    RECOMMENDATIONS: '/api/ecommerce/recommendations',
    TRENDING: '/api/ecommerce/trending',
    SEARCH: '/api/ecommerce/search',
    PRODUCTS: '/api/ecommerce/products',
    CART: '/api/ecommerce/cart',
  },
  SEARCH: {
    SEARCH: '/api/search',
    SUGGESTIONS: '/api/search/suggestions',
  },
  BOTS: {
    BOTS: '/api/bots',
    CHAT: '/api/bots',
  },
  ANALYTICS: {
    USERS: '/api/analytics/users',
    CONVERSATIONS: '/api/analytics/conversations',
    ENGAGEMENT: '/api/analytics/engagement',
    RETENTION: '/api/analytics/retention',
    CHURN_PREDICTION: '/api/analytics/churn-prediction',
  },
};

// Rust API endpoints
export const RUST_ENDPOINTS: RustEndpoints = {
  ENCRYPTION: {
    KEYS: '/api/encryption/keys',
    ENCRYPT: '/api/encryption/encrypt',
    DECRYPT: '/api/encryption/decrypt',
  },
  FILESHARING: {
    UPLOAD: '/api/filesharing/upload',
    DOWNLOAD: '/api/filesharing/download',
    SHARE: '/api/filesharing/share',
    SHARED: '/api/filesharing/shared',
  },
  MARKETPLACE: {
    PRODUCTS: '/api/marketplace/products',
    BID: '/api/marketplace/products/bid',
    BUY: '/api/marketplace/products/buy',
  },
};

// Zig API endpoints
export const ZIG_ENDPOINTS: ZigEndpoints = {
  VOICEMAIL: {
    RECORD: '/api/voicemail/record',
    TRANSCRIBE: '/api/voicemail/transcribe',
    LIST: '/api/voicemail',
    DELETE: '/api/voicemail',
  },
  MODERATION: {
    TEXT: '/api/moderation/text',
    IMAGE: '/api/moderation/image',
    VIDEO: '/api/moderation/video',
  },
};

// Elixir API endpoints
export const ELIXIR_ENDPOINTS: ElixirEndpoints = {
  EVENTS: {
    EVENTS: '/api/events',
    CALENDAR: '/api/events/calendar',
    RSVP: '/api/events/rsvp',
  },
  WALLET: {
    BALANCE: '/api/wallets/balance',
    TRANSFER: '/api/wallets/transfer',
    TRANSACTIONS: '/api/wallets/transactions',
  },
};

// Environment
export const isProduction = import.meta.env.MODE === 'production';
export const isDevelopment = import.meta.env.MODE === 'development';
