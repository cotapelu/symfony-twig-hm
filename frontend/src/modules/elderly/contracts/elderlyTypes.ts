export interface ElderlyService {
  id: string;
  name: string;
  category: string;
  description: string;
  eligibility: string[];
}

export interface ElderlyState {
  services: ElderlyService[];
  loading: boolean;
  error: string | null;
}
