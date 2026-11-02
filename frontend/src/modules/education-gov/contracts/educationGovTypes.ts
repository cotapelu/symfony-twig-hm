export interface EducationGovService {
  id: string;
  name: string;
  category: string;
  description: string;
  requirements: string[];
}

export interface EducationGovState {
  services: EducationGovService[];
  loading: boolean;
  error: string | null;
}
