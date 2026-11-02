export interface ArticlePublishedEvent {
  type: 'news.article.published';
  payload: {
    articleId: string;
    authorId: string;
    title: string;
    category: string;
  };
}

export interface ArticleUpdatedEvent {
  type: 'news.article.updated';
  payload: {
    articleId: string;
    updatedFields: string[];
  };
}

export interface ArticleDeletedEvent {
  type: 'news.article.deleted';
  payload: {
    articleId: string;
  };
}

export interface CommentAddedEvent {
  type: 'news.comment.added';
  payload: {
    commentId: string;
    articleId: string;
    userId: string;
  };
}

export interface ArticleViewedEvent {
  type: 'news.article.viewed';
  payload: {
    articleId: string;
    viewCount: number;
  };
}

export type NewsEvent =
  | ArticlePublishedEvent
  | ArticleUpdatedEvent
  | ArticleDeletedEvent
  | CommentAddedEvent
  | ArticleViewedEvent;

export const newsEvents = {
  articlePublished: (articleId: string, authorId: string, title: string, category: string): ArticlePublishedEvent => ({
    type: 'news.article.published',
    payload: { articleId, authorId, title, category }
  }),

  articleUpdated: (articleId: string, updatedFields: string[]): ArticleUpdatedEvent => ({
    type: 'news.article.updated',
    payload: { articleId, updatedFields }
  }),

  articleDeleted: (articleId: string): ArticleDeletedEvent => ({
    type: 'news.article.deleted',
    payload: { articleId }
  }),

  commentAdded: (commentId: string, articleId: string, userId: string): CommentAddedEvent => ({
    type: 'news.comment.added',
    payload: { commentId, articleId, userId }
  }),

  articleViewed: (articleId: string, viewCount: number): ArticleViewedEvent => ({
    type: 'news.article.viewed',
    payload: { articleId, viewCount }
  })
};
