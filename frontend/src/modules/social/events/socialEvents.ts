export interface SocialEvent {
  type: 'POST_CREATED' | 'POST_LIKED' | 'POST_UNLIKED' | 'COMMENT_ADDED' | 'POST_SHARED' | 'POST_DELETED' | 'COMMENT_DELETED';
  payload: any;
  timestamp: Date;
}

export interface PostCreatedEvent {
  type: 'POST_CREATED';
  payload: {
    postId: string;
    authorId: string;
    content: string;
    privacy: 'public' | 'friends' | 'private';
  };
  timestamp: Date;
}

export interface PostLikedEvent {
  type: 'POST_LIKED';
  payload: {
    postId: string;
    userId: string;
    postAuthorId: string;
  };
  timestamp: Date;
}

export interface PostUnlikedEvent {
  type: 'POST_UNLIKED';
  payload: {
    postId: string;
    userId: string;
    postAuthorId: string;
  };
  timestamp: Date;
}

export interface CommentAddedEvent {
  type: 'COMMENT_ADDED';
  payload: {
    postId: string;
    commentId: string;
    authorId: string;
    content: string;
    postAuthorId: string;
  };
  timestamp: Date;
}

export interface PostSharedEvent {
  type: 'POST_SHARED';
  payload: {
    originalPostId: string;
    newPostId: string;
    userId: string;
    content?: string;
  };
  timestamp: Date;
}

export interface PostDeletedEvent {
  type: 'POST_DELETED';
  payload: {
    postId: string;
    authorId: string;
  };
  timestamp: Date;
}

export interface CommentDeletedEvent {
  type: 'COMMENT_DELETED';
  payload: {
    postId: string;
    commentId: string;
    authorId: string;
  };
  timestamp: Date;
}