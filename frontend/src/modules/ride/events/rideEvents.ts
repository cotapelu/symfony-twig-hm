export interface RideRequestedEvent { type: 'ride.requested'; payload: { rideId: string; userId: string }; }
export interface RideAcceptedEvent { type: 'ride.accepted'; payload: { rideId: string; driverId: string; driverName: string }; }
export interface RideCompletedEvent { type: 'ride.completed'; payload: { rideId: string; driverId: string }; }
export type RideEvent = RideRequestedEvent | RideAcceptedEvent | RideCompletedEvent;
export const rideEvents = {
  rideRequested: (rideId: string, userId: string): RideRequestedEvent => ({ type: 'ride.requested', payload: { rideId, userId } }),
  rideAccepted: (rideId: string, driverId: string, driverName: string): RideAcceptedEvent => ({ type: 'ride.accepted', payload: { rideId, driverId, driverName } }),
  rideCompleted: (rideId: string, driverId: string): RideCompletedEvent => ({ type: 'ride.completed', payload: { rideId, driverId } })
};
