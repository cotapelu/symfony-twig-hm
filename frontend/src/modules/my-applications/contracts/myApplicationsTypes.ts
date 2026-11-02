export interface MyApplication {
  id: string;
  userId: string;
  name: string;
  category: string;
  status: 'active' | 'inactive';
  installedAt: string;
}

export interface MyApplicationsState {
  applications: MyApplication[];
  loading: boolean;
  error: string | null;
}
