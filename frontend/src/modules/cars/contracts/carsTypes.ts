export interface Car {
  id: string;
  userId?: string;
  make: string;
  model: string;
  year: number;
  licensePlate: string;
  color?: string;
  vin?: string;
  mileage?: number;
  status: 'available' | 'rented' | 'maintenance' | 'unavailable';
  dailyRate?: number;
  images?: string[];
  features?: string[];
  createdAt: string;
  updatedAt: string;
}

export interface CarRental {
  id: string;
  userId: string;
  carId: string;
  car?: Car;
  pickupLocation: string;
  dropoffLocation: string;
  pickupDate: string;
  dropoffDate: string;
  status: 'pending' | 'confirmed' | 'active' | 'completed' | 'cancelled';
  totalAmount: number;
  currency: string;
  driverName?: string;
  driverLicense?: string;
  additionalDrivers?: string[];
  insurance?: string;
  extras?: string[];
  createdAt: string;
  updatedAt: string;
}

export interface CarState {
  cars: Car[];
  rentals: CarRental[];
  loading: boolean;
  error: string | null;
}

export interface CreateCarRequest {
  make: string;
  model: string;
  year: number;
  licensePlate: string;
  color?: string;
  vin?: string;
  mileage?: number;
  dailyRate?: number;
  images?: string[];
  features?: string[];
}

export interface CreateRentalRequest {
  carId: string;
  pickupLocation: string;
  dropoffLocation: string;
  pickupDate: string;
  dropoffDate: string;
  driverName?: string;
  driverLicense?: string;
  additionalDrivers?: string[];
  insurance?: string;
  extras?: string[];
}
