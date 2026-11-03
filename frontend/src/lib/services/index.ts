// Re-export all services for convenient single import
// Ambiguous names shared by ecommerce/fooddelivery (CartItem, Order, Product) and
// fooddelivery/restaurants (Restaurant) are pinned explicitly below.
export type { CartItem, Order, Product } from './ecommerce';
export type { Restaurant } from './restaurants';

export * from './auth';
export * from './booking';
export * from './cars';
export * from './citizenService';
export * from './documentWorkflowService';
export * from './ecommerce';
export * from './education';
export * from './events';
export * from './flights';
export * from './fooddelivery';
export * from './government';
export * from './health';
export * from './hotels';
export * from './insurance';
export * from './jobs';
export * from './location';
export * from './loyalty';
export * from './media';
export * from './messages';
export * from './notifications';
export * from './restaurants';
export * from './ride';
export * from './social';
export * from './wallet';
