export interface NewsArticle {
  id: string;
  authorId: string;
  title: string;
  content: string;
  summary?: string;
  category: string;
  tags?: string[];
  image?: string;
  status: 'draft' | 'published' | 'archived';
  publishedAt?: string;
  viewCount: number;
  createdAt: string;
  updatedAt: string;
}

export interface NewsComment {
  id: string;
  articleId: string;
  userId: string;
  content: string;
  createdAt: string;
}

export interface NewsState {
  articles: NewsArticle[];
  selectedArticle: NewsArticle | null;
  comments: NewsComment[];
  loading: boolean;
  error: string | null;
}

export interface CreateArticleRequest {
  title: string;
  content: string;
  summary?: string;
  category: string;
  tags?: string[];
  status?: 'draft' | 'published';
}

export interface UpdateArticleRequest {
  title?: string;
  content?: string;
  summary?: string;
  category?: string;
  tags?: string[];
  status?: 'draft' | 'published' | 'archived';
}
