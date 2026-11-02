export interface StreamStartedEvent {
  type: 'livestreaming.started';
  payload: {
    streamId: string;
    userId: string;
    title: string;
  };
}

export interface StreamEndedEvent {
  type: 'livestreaming.ended';
  payload: {
    streamId: string;
    userId: string;
    duration: number;
    totalViewers: number;
  };
}

export interface ViewerJoinedEvent {
  type: 'livestreaming.viewer_joined';
  payload: {
    streamId: string;
    viewerId: string;
    viewerCount: number;
  };
}

export interface ViewerLeftEvent {
  type: 'livestreaming.viewer_left';
  payload: {
    streamId: string;
    viewerId: string;
    duration: number;
    viewerCount: number;
  };
}

export interface CommentPostedEvent {
  type: 'livestreaming.comment_posted';
  payload: {
    streamId: string;
    userId: string;
    username: string;
    message: string;
  };
}

export type LivestreamingEvent = 
  | StreamStartedEvent 
  | StreamEndedEvent 
  | ViewerJoinedEvent 
  | ViewerLeftEvent 
  | CommentPostedEvent;

export const livestreamingEvents = {
  streamStarted: (streamId: string, userId: string, title: string): StreamStartedEvent => ({
    type: 'livestreaming.started',
    payload: { streamId, userId, title }
  }),

  streamEnded: (streamId: string, userId: string, duration: number, totalViewers: number): StreamEndedEvent => ({
    type: 'livestreaming.ended',
    payload: { streamId, userId, duration, totalViewers }
  }),

  viewerJoined: (streamId: string, viewerId: string, viewerCount: number): ViewerJoinedEvent => ({
    type: 'livestreaming.viewer_joined',
    payload: { streamId, viewerId, viewerCount }
  }),

  viewerLeft: (streamId: string, viewerId: string, duration: number, viewerCount: number): ViewerLeftEvent => ({
    type: 'livestreaming.viewer_left',
    payload: { streamId, viewerId, duration, viewerCount }
  }),

  commentPosted: (streamId: string, userId: string, username: string, message: string): CommentPostedEvent => ({
    type: 'livestreaming.comment_posted',
    payload: { streamId, userId, username, message }
  })
};
