export interface CreatePostRequest {
  content: string;
  images?: string[];
  privacy: 'public' | 'friends' | 'private';
}

export interface PostResponse {
  id: string;
  authorId: string;
  content: string;
  images?: string[];
  likes: string[];
  comments: CommentResponse[];
  shares: number;
  timestamp: string;
  privacy: 'public' | 'friends' | 'private';
}

export interface CommentResponse {
  id: string;
  postId: string;
  authorId: string;
  content: string;
  likes: string[];
  timestamp: string;
}

export interface AddCommentRequest {
  content: string;
}

export interface SharePostRequest {
  postId: string;
  content?: string;
}