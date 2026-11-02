export interface DeliveryCreatedEvent {
  type: 'delivery.created';
  payload: { deliveryId: string; userId: string; type: string };
}

export interface DeliveryAssignedEvent {
  type: 'delivery.assigned';
  payload: { deliveryId: string; courierId: string; courierName: string };
}

export interface DeliveryPickedUpEvent {
  type: 'delivery.picked_up';
  payload: { deliveryId: string; courierId: string };
}

export interface DeliveryInTransitEvent {
  type: 'delivery.in_transit';
  payload: { deliveryId: string; courierId: string };
}

export interface DeliveryDeliveredEvent {
  type: 'delivery.delivered';
  payload: { deliveryId: string; courierId: string };
}

export interface DeliveryCancelledEvent {
  type: 'delivery.cancelled';
  payload: { deliveryId: string; reason?: string };
}

export type DeliveryEvent = DeliveryCreatedEvent | DeliveryAssignedEvent | DeliveryPickedUpEvent | DeliveryInTransitEvent | DeliveryDeliveredEvent | DeliveryCancelledEvent;

export const deliveryEvents = {
  deliveryCreated: (deliveryId: string, userId: string, type: string): DeliveryCreatedEvent => ({
    type: 'delivery.created',
    payload: { deliveryId, userId, type }
  }),
  deliveryAssigned: (deliveryId: string, courierId: string, courierName: string): DeliveryAssignedEvent => ({
    type: 'delivery.assigned',
    payload: { deliveryId, courierId, courierName }
  }),
  deliveryPickedUp: (deliveryId: string, courierId: string): DeliveryPickedUpEvent => ({
    type: 'delivery.picked_up',
    payload: { deliveryId, courierId }
  }),
  deliveryInTransit: (deliveryId: string, courierId: string): DeliveryInTransitEvent => ({
    type: 'delivery.in_transit',
    payload: { deliveryId, courierId }
  }),
  deliveryDelivered: (deliveryId: string, courierId: string): DeliveryDeliveredEvent => ({
    type: 'delivery.delivered',
    payload: { deliveryId, courierId }
  }),
  deliveryCancelled: (deliveryId: string, reason?: string): DeliveryCancelledEvent => ({
    type: 'delivery.cancelled',
    payload: { deliveryId, reason }
  })
};
