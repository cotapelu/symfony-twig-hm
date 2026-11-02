export interface CarCreatedEvent {
  type: 'cars.car.created';
  payload: { carId: string; make: string; model: string; year: number };
}

export interface CarUpdatedEvent {
  type: 'cars.car.updated';
  payload: { carId: string; make: string; model: string };
}

export interface CarDeletedEvent {
  type: 'cars.car.deleted';
  payload: { carId: string };
}

export interface RentalCreatedEvent {
  type: 'cars.rental.created';
  payload: { rentalId: string; carId: string; userId: string; pickupDate: string };
}

export interface RentalConfirmedEvent {
  type: 'cars.rental.confirmed';
  payload: { rentalId: string; carId: string };
}

export interface RentalCancelledEvent {
  type: 'cars.rental.cancelled';
  payload: { rentalId: string; carId: string; reason?: string };
}

export type CarsEvent = CarCreatedEvent | CarUpdatedEvent | CarDeletedEvent | RentalCreatedEvent | RentalConfirmedEvent | RentalCancelledEvent;

export const carsEvents = {
  carCreated: (carId: string, make: string, model: string, year: number): CarCreatedEvent => ({
    type: 'cars.car.created',
    payload: { carId, make, model, year }
  }),
  carUpdated: (carId: string, make: string, model: string): CarUpdatedEvent => ({
    type: 'cars.car.updated',
    payload: { carId, make, model }
  }),
  carDeleted: (carId: string): CarDeletedEvent => ({
    type: 'cars.car.deleted',
    payload: { carId }
  }),
  rentalCreated: (rentalId: string, carId: string, userId: string, pickupDate: string): RentalCreatedEvent => ({
    type: 'cars.rental.created',
    payload: { rentalId, carId, userId, pickupDate }
  }),
  rentalConfirmed: (rentalId: string, carId: string): RentalConfirmedEvent => ({
    type: 'cars.rental.confirmed',
    payload: { rentalId, carId }
  }),
  rentalCancelled: (rentalId: string, carId: string, reason?: string): RentalCancelledEvent => ({
    type: 'cars.rental.cancelled',
    payload: { rentalId, carId, reason }
  })
};
