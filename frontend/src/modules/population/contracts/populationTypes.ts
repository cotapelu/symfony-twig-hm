export interface PopulationRecord {
  id: string;
  area: string;
  totalPopulation: number;
  male: number;
  female: number;
  year: number;
}

export interface PopulationState {
  records: PopulationRecord[];
  loading: boolean;
  error: string | null;
}
