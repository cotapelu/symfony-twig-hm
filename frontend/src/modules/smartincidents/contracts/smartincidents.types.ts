// Smart Incident Reporting Module - TypeScript types matching PHP backend DTOs

// ============================================
// Incident Types (Extended from ANTT)
// ============================================
export type IncidentType = 
  | 'theft'
  | 'vandalism'
  | 'public_order'
  | 'traffic_violation'
  | 'noise_complaint'
  | 'environmental'
  | 'safety_hazard'
  | 'fire_hazard'
  | 'missing_person'
  | 'lost_property'
  | 'illegal_construction'
  | 'other';

export type IncidentSeverity = 'low' | 'medium' | 'high' | 'critical';

export type IncidentStatus = 
  | 'reported'
  | 'assigned'
  | 'in_progress'
  | 'awaiting_info'
  | 'resolved'
  | 'escalated'
  | 'closed';

// ============================================
// Incident Entity
// ============================================
export interface SmartIncident {
  id: number;
  incidentNumber: string;
  title: string;
  description: string | null;
  incidentType: IncidentType;
  severity: IncidentSeverity;
  status: IncidentStatus;
  
  // Reporter Info
  reporterName: string | null;
  reporterPhone: string | null;
  reporterAnonymous: boolean;
  
  // Location
  locationAddress: string | null;
  latitude: number | null;
  longitude: number | null;
  wardCode: string | null;
  
  // Assignment
  assignedOfficerId: string | null;
  assignedTeam: string | null;
  
  // Timeline
  reportedAt: string;
  resolvedAt: string | null;
  closedAt: string | null;
  
  // Resolution
  resolutionSummary: string | null;
  
  // Metadata (NEW)
  metadata: IncidentMetadata;
  
  // Attachments (NEW)
  attachments: IncidentAttachment[];
  
  // Chat (NEW)
  messages: IncidentMessage[];
  
  // Statistics
  viewCount: number;
  upvoteCount: number;
  
  createdAt: string;
  updatedAt: string | null;
}

// ============================================
// Incident Metadata (NEW)
// ============================================
export interface IncidentMetadata {
  reportSource: 'web' | 'mobile' | 'phone' | 'walk_in';
  deviceInfo: string | null;
  browserInfo: string | null;
  ip_address: string | null;
  estimatedImpact: number | null;
  keywords: string[];
  tags: string[];
  aiClassification: string | null;
  aiConfidence: number | null;
  duplicateCheck: {
    isDuplicate: boolean;
    similarIncidentIds: number[];
    similarityScore: number;
  } | null;
}

// ============================================
// Incident Attachment (NEW)
// ============================================
export type AttachmentType = 'image' | 'video' | 'audio' | 'document';

export interface IncidentAttachment {
  id: number;
  incidentId: number;
  fileType: AttachmentType;
  fileName: string;
  fileUrl: string;
  fileSize: number;
  mimeType: string | null;
  description: string | null;
  uploadedBy: string | null; // 'reporter' or 'officer'
  createdAt: string;
}

// ============================================
// Incident Message/Chat (NEW)
// ============================================
export type MessageSender = 'reporter' | 'officer' | 'system';

export interface IncidentMessage {
  id: number;
  incidentId: number;
  sender: MessageSender;
  senderId: string | null;
  senderName: string | null;
  content: string;
  attachmentUrl: string | null;
  isRead: boolean;
  createdAt: string;
}

// ============================================
// Create/Update Requests
// ============================================
export interface CreateIncidentRequest {
  title: string;
  description?: string;
  incidentType: IncidentType;
  severity?: IncidentSeverity;
  
  // Reporter
  reporterName?: string;
  reporterPhone?: string;
  reporterAnonymous?: boolean;
  
  // Location
  locationAddress?: string;
  latitude?: number;
  longitude?: number;
  wardCode?: string;
  
  // Attachments
  attachmentFiles?: File[];
  
  // Metadata
  reportSource?: 'web' | 'mobile' | 'phone' | 'walk_in';
  tags?: string[];
}

export interface UpdateIncidentRequest {
  title?: string;
  description?: string;
  severity?: IncidentSeverity;
  status?: IncidentStatus;
  
  // Assignment
  assignedOfficerId?: string;
  assignedTeam?: string;
  
  // Resolution
  resolutionSummary?: string;
  
  // Tags
  tags?: string[];
}

// ============================================
// Incident Filters
// ============================================
export interface IncidentFilters {
  search?: string;
  incidentType?: IncidentType;
  severity?: IncidentSeverity;
  status?: IncidentStatus;
  wardCode?: string;
  assignedOfficerId?: string;
  dateFrom?: string;
  dateTo?: string;
  reporterAnonymous?: boolean;
  hasAttachments?: boolean;
}

// ============================================
// Incident Statistics
// ============================================
export interface IncidentStatistics {
  total: number;
  byStatus: Record<IncidentStatus, number>;
  bySeverity: Record<IncidentSeverity, number>;
  byType: Record<IncidentType, number>;
  byWard: Record<string, number>;
  
  // NEW: Trends
  trends: {
    last7Days: number[];
    last30Days: number[];
    byHour: number[];
    byDayOfWeek: number[];
  };
  
  // NEW: Resolution metrics
  resolutionMetrics: {
    avgResolutionTime: number;
    avgResponseTime: number;
    resolvedWithinSLA: number;
    escalatedCount: number;
  };
  
  // NEW: Reporter stats
  reporterStats: {
    anonymousCount: number;
    identifiedCount: number;
    byReportSource: Record<string, number>;
  };
  
  // NEW: Recent activity
  recentActivity: {
    reportedToday: number;
    inProgress: number;
    pendingAssignment: number;
    overdue: number;
  };
}

// ============================================
// Incident Activity Log
// ============================================
export type ActivityType = 
  | 'created'
  | 'status_changed'
  | 'assigned'
  | 'message_sent'
  | 'attachment_added'
  | 'escalated'
  | 'resolved'
  | 'closed';

export interface IncidentActivity {
  id: number;
  incidentId: number;
  activityType: ActivityType;
  description: string;
  performedBy: string;
  performedByName: string;
  metadata: Record<string, any> | null;
  createdAt: string;
}

// ============================================
// Map Data
// ============================================
export interface MapIncidentMarker {
  id: number;
  latitude: number;
  longitude: number;
  incidentNumber: string;
  title: string;
  severity: IncidentSeverity;
  status: IncidentStatus;
  incidentType: IncidentType;
  reportedAt: string;
}

export interface MapBounds {
  north: number;
  south: number;
  east: number;
  west: number;
}

// ============================================
// API Response Types
// ============================================
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  error?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  page: number;
  perPage: number;
  total?: number;
  hasMore?: boolean;
}

// ============================================
// Incident Heatmap Data (NEW)
// ============================================
export interface HeatmapPoint {
  latitude: number;
  longitude: number;
  intensity: number;
}

export interface IncidentHeatmapData {
  points: HeatmapPoint[];
  gridSize: number;
  maxIntensity: number;
  timeframe: '24h' | '7d' | '30d' | '90d';
}
