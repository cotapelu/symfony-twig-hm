export interface Experience {
  id: string;
  userId: string;
  title: string;
  description: string;
  category: string;
  images?: string[];
  rating?: number;
  createdAt: string;
}

export interface ExperiencesState {
  experiences: Experience[];
  loading: boolean;
  error: string | null;
}
