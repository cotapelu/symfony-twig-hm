export interface Product {
  id: string;
  name: string;
  description: string;
  price: number;
  currency: string;
  category: string;
  image?: string;
  images?: string[];
  rating?: number;
  reviews?: number;
  stock: number;
  discount?: number;
  sku?: string;
  tags?: string[];
  attributes?: Record<string, string>;
  createdAt: string;
}

export interface CartItem {
  id: string;
  productId: string;
  name: string;
  price: number;
  quantity: number;
  image?: string;
  currency: string;
}

export interface OrderItem {
  id: string;
  productId: string;
  name: string;
  price: number;
  quantity: number;
  image?: string;
}

export interface Order {
  id: string;
  userId: string;
  items: OrderItem[];
  total: number;
  currency: string;
  status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled' | 'refunded';
  shippingAddress?: Address;
  billingAddress?: Address;
  paymentMethod?: string;
  trackingNumber?: string;
  date: string;
  updatedAt: string;
}

export interface Address {
  id?: string;
  name: string;
  line1: string;
  line2?: string;
  city: string;
  state: string;
  postalCode: string;
  country: string;
  phone?: string;
  isDefault?: boolean;
}

export interface Review {
  id: string;
  productId: string;
  userId: string;
  userName: string;
  rating: number;
  comment: string;
  images?: string[];
  helpful: number;
  verified: boolean;
  createdAt: string;
}

export interface EcommerceState {
  products: Product[];
  cart: CartItem[];
  orders: Order[];
  selectedProduct: Product | null;
  loading: boolean;
  error: string | null;
}

export interface AddToCartRequest {
  productId: string;
  quantity: number;
}

export interface CheckoutRequest {
  items: CartItem[];
  shippingAddress: Address;
  billingAddress?: Address;
  paymentMethodId: string;
}
