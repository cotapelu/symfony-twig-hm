// Backend API Configuration
// Federation Architecture - Single Entry Point via PHP API Gateway

export const API_CONFIG = {
  // API Gateway - Single Entry Point for ALL backends
  // Runs on PHP Symfony (port 8080)
  gateway: 'http://localhost:8080',
  
  // Direct Backend Access (Development fallback - NOT recommended)
  direct: {
    // Go Backend - All modules on single server (port 8081)
    go: 'http://localhost:8081',
    // PHP Backend - All modules on single server (port 8080)
    php: 'http://localhost:8080',
    // Python Backend - Separate services
    python: {
      telemedicine: 'http://localhost:8000',
      chatbot: 'http://localhost:8001', 
      ai: 'http://localhost:8002',
    },
    // Kotlin Backend - Separate services
    kotlin: {
      flights: 'http://localhost:9000',
      hotels: 'http://localhost:9001',
      booking: 'http://localhost:9002',
    },
    // Zig Backend - System/Performance modules (port 4000)
    zig: 'http://localhost:4000',
  },
} as const;

// API Gateway Routes Map
// All requests go through PHP API Gateway on port 8080
export const GATEWAY_ROUTES = {
  // Go Backend Services
  '/api/auth': 'go',
  '/api/wallet': 'go',
  '/api/banking': 'go',
  '/api/qrpayment': 'go',
  '/api/bills': 'go',
  '/api/location': 'go',
  '/api/sync': 'go',
  '/api/events': 'go',
  '/api/marketplace': 'go',
  '/api/coreservices': 'go',
  '/api/delivery': 'go',
  '/api/tracking': 'go',
  '/api/notifications': 'go',
  '/api/courier': 'go',
  '/api/credit': 'go',
  '/api/fraud': 'go',
  '/api/vc': 'go',
  '/api/key': 'go',
  '/api/ca': 'go',
  
  // PHP Backend Services
  '/api/gov': 'php',
  '/api/user': 'php',
  '/api/message': 'php',
  '/api/conversation': 'php',
  '/api/social': 'php',
  '/api/food': 'php',
  '/api/ecommerce': 'php',
  '/api/health': 'php',
  '/api/education': 'php',
  '/api/ride': 'php',
  '/api/insurance': 'php',
  '/api/loyalty': 'php',
  '/api/rewards': 'php',
  '/api/media': 'php',
  '/api/search': 'php',
  '/api/jobs': 'php',
  '/api/maps': 'php',
  '/api/citizen': 'php',
  '/api/officer': 'php',
  '/api/thhc': 'php',
  
  // Python Backend Services
  '/api/telemedicine': 'python',
  '/api/chatbot': 'python',
  '/api/ai': 'python',
  
  // Kotlin Backend Services
  '/api/flights': 'kotlin',
  '/api/hotels': 'kotlin',
  '/api/booking': 'kotlin',
  
  // Zig Backend Services
  '/api/calls': 'zig',
  '/api/voicemail': 'zig',
  '/api/webrtc': 'zig',
  '/api/moderation': 'zig',
  '/api/streaming': 'zig',
  '/api/storage': 'zig',
  '/api/secure': 'zig',
} as const;

export type BackendService = keyof typeof API_CONFIG.direct.go | keyof typeof API_CONFIG.direct.php | keyof typeof API_CONFIG.direct.python | keyof typeof API_CONFIG.direct.kotlin;

export function getApiUrl(backend: string, path: string): string {
  // Use PHP API Gateway as single entry point
  return `${API_CONFIG.gateway}${path}`;
}

export default API_CONFIG;
