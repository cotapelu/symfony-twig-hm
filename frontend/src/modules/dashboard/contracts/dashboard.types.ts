export interface DashboardStats {
  messages: number;
  groups: number;
  files: number;
  calls: number;
}

export interface DashboardResponse {
  success: boolean;
  data?: DashboardStats;
  message?: string;
}