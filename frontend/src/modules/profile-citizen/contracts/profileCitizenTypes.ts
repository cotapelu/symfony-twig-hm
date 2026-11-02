export interface CitizenProfile {
  id: string;
  userId: string;
  fullName: string;
  dateOfBirth: string;
  gender: string;
  address: string;
  phone: string;
  email: string;
  citizenId: string;
}

export interface ProfileCitizenState {
  profile: CitizenProfile | null;
  loading: boolean;
  error: string | null;
}
