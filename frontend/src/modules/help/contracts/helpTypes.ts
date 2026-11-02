export interface HelpArticle {
  id: string;
  title: string;
  category: string;
  content: string;
}

export interface HelpState {
  articles: HelpArticle[];
  loading: boolean;
  error: string | null;
}
