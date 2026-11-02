export interface Delivery {
  id: string;
  userId: string;
  orderId?: string;
  type: 'food' | 'package' | 'document' | 'groceries' | 'custom';
  status: 'pending' | 'assigned' | 'picked_up' | 'in_transit' | 'delivered' | 'cancelled' | 'failed';
  pickupAddress: {
    street: string;
    city: string;
    state: string;
    zipCode: string;
    country: string;
    coordinates?: { lat: number; lng: number };
  };
  deliveryAddress: {
    street: string;
    city: string;
    state: string;
    zipCode: string;
    country: string;
    coordinates?: { lat: number; lng: number };
  };
  package?: {
    description: string;
    weight?: number;
    dimensions?: { length: number; width: number; height: number };
    value?: number;
  };
  courier?: {
    id: string;
    name: string;
    phone: string;
    vehicle?: string;
  };
  estimatedDelivery?: string;
  actualDelivery?: string;
  fee: number;
  currency: string;
  tips?: number;
  createdAt: string;
  updatedAt: string;
}

export interface DeliveryState {
  deliveries: Delivery[];
  currentDelivery: Delivery | null;
  loading: boolean;
  error: string | null;
}

export interface CreateDeliveryRequest {
  type: Delivery['type'];
  pickupAddress: Delivery['pickupAddress'];
  deliveryAddress: Delivery['deliveryAddress'];
  package?: Delivery['package'];
  orderId?: string;
}

export interface UpdateDeliveryStatusRequest {
  status: Delivery['status'];
  courierId?: string;
}
