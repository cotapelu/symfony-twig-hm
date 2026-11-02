export interface Household {
  id: string;
  userId: string;
  householdCode: string;
  address: string;
  members: number;
  headOfHousehold: string;
}

export interface HouseholdState {
  households: Household[];
  loading: boolean;
  error: string | null;
}
