export interface Queue {
  id: string;
  name: string;
  wardCode: string;
  departmentName?: string;
  description?: string;
  status: 'active' | 'inactive' | 'closed';
  isOpen: boolean;
  serviceTypes: string[];
  counterCount: number;
  averageServiceTime?: number;
  maxTicketsPerDay?: number;
  operatingHours?: Record<number, { open: string; close: string; breakStart?: string; breakEnd?: string }>;
  waitingTicketCount: number;
  averageWaitTime?: number;
  assignedOfficers: string[];
  createdAt: string;
}

export interface QueueTicket {
  id: string;
  ticketNumber: string;
  queueId: string;
  queueName: string;
  serviceType: string;
  citizenName: string;
  citizenPhone?: string;
  status: 'waiting' | 'called' | 'in_progress' | 'completed' | 'cancelled' | 'skipped';
  priority: number;
  estimatedWaitTime?: number;
  calledAt?: string;
  startedAt?: string;
  completedAt?: string;
  counterNumber?: string;
  servedByOfficer?: string;
  waitingDuration?: number;
  serviceDuration?: number;
  notes?: string;
  createdAt: string;
}

export interface QueueStatistics {
  queueId: string;
  queueName: string;
  isOpen: boolean;
  waitingCount: number;
  inProgressCount: number;
  todayCompleted: number;
  averageWaitTime?: number;
  averageServiceTime?: number;
  operatingHours?: Record<number, { open: string; close: string; breakStart?: string; breakEnd?: string }>;
}

export interface CreateQueueRequest {
  name: string;
  wardCode: string;
  serviceTypes: string[];
  departmentName?: string;
  description?: string;
  counterCount?: number;
  operatingHours?: Record<number, { open: string; close: string; breakStart?: string; breakEnd?: string }>;
}

export interface JoinQueueRequest {
  queueId: string;
  serviceType: string;
  citizenName: string;
  citizenPhone?: string;
  citizenId?: string;
  priority?: number;
}

export interface CallNextTicketRequest {
  queueId: string;
  officerId: string;
  counterNumber: string;
}
