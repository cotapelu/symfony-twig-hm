export interface ServicePackage {
  id: string;
  name: string;
  description: string;
  price: number;
  duration: string;
  features: string[];
}

export interface PackagesState {
  packages: ServicePackage[];
  loading: boolean;
  error: string | null;
}
