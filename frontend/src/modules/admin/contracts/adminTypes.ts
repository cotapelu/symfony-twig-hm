export interface AdminUser {
  id: string;
  username: string;
  email: string;
  role: 'superadmin' | 'admin' | 'moderator' | 'support';
  permissions: string[];
  createdAt: string;
  lastLogin?: string;
  status: 'active' | 'inactive' | 'suspended';
}

export interface AdminStats {
  totalUsers: number;
  activeUsers: number;
  totalRevenue: number;
  totalBookings: number;
  pendingApprovals: number;
  openTickets: number;
}

export interface SystemLog {
  id: string;
  userId?: string;
  action: string;
  resource: string;
  resourceId?: string;
  details?: Record<string, unknown>;
  ipAddress?: string;
  userAgent?: string;
  timestamp: string;
}

export interface AdminDocument {
  id: string;
  title: string;
  type: 'certificate' | 'license' | 'permit' | 'registration' | 'other';
  status: 'pending' | 'approved' | 'rejected';
  userId: string;
  userName: string;
  submittedAt: string;
  reviewedAt?: string;
  reviewedBy?: string;
  notes?: string;
}

export interface PopulationRecord {
  id: string;
  citizenId: string;
  fullName: string;
  dateOfBirth: string;
  gender: 'male' | 'female' | 'other';
  address: string;
  ward: string;
  district: string;
  province: string;
  householdId?: string;
  registeredAt: string;
  status: 'active' | 'moved' | 'deceased';
}

export interface AdminState {
  stats: AdminStats | null;
  users: AdminUser[];
  documents: AdminDocument[];
  population: PopulationRecord[];
  logs: SystemLog[];
  loading: boolean;
  error: string | null;
}

export interface UpdateDocumentRequest {
  documentId: string;
  status: 'approved' | 'rejected';
  notes?: string;
}

export interface CreateAdminUserRequest {
  username: string;
  email: string;
  password: string;
  role: AdminUser['role'];
  permissions: string[];
}
