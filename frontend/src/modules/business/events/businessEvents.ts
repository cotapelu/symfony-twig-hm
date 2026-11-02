export interface BusinessCreatedEvent {
  type: 'business.created';
  payload: {
    businessId: string;
    ownerId: string;
    name: string;
    category: string;
  };
}

export interface BusinessVerifiedEvent {
  type: 'business.verified';
  payload: {
    businessId: string;
  };
}

export interface BusinessUpdatedEvent {
  type: 'business.updated';
  payload: {
    businessId: string;
    updatedFields: string[];
  };
}

export interface ReviewCreatedEvent {
  type: 'business.review.created';
  payload: {
    reviewId: string;
    businessId: string;
    userId: string;
    rating: number;
  };
}

export interface ReviewDeletedEvent {
  type: 'business.review.deleted';
  payload: {
    reviewId: string;
    businessId: string;
  };
}

export type BusinessEvent =
  | BusinessCreatedEvent
  | BusinessVerifiedEvent
  | BusinessUpdatedEvent
  | ReviewCreatedEvent
  | ReviewDeletedEvent;

export const businessEvents = {
  businessCreated: (businessId: string, ownerId: string, name: string, category: string): BusinessCreatedEvent => ({
    type: 'business.created',
    payload: { businessId, ownerId, name, category }
  }),

  businessVerified: (businessId: string): BusinessVerifiedEvent => ({
    type: 'business.verified',
    payload: { businessId }
  }),

  businessUpdated: (businessId: string, updatedFields: string[]): BusinessUpdatedEvent => ({
    type: 'business.updated',
    payload: { businessId, updatedFields }
  }),

  reviewCreated: (reviewId: string, businessId: string, userId: string, rating: number): ReviewCreatedEvent => ({
    type: 'business.review.created',
    payload: { reviewId, businessId, userId, rating }
  }),

  reviewDeleted: (reviewId: string, businessId: string): ReviewDeletedEvent => ({
    type: 'business.review.deleted',
    payload: { reviewId, businessId }
  })
};
