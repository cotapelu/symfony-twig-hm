// Polls Types
export interface PollOption {
  id: string;
  text: string;
  votes: number;
}

export interface Poll {
  id: string;
  question: string;
  options: PollOption[];
  totalVotes: number;
  status: 'active' | 'closed';
  createdAt: Date;
  expiresAt?: Date;
  allowMultiple: boolean;
  showResults: boolean;
}

export interface PollVote {
  pollId: string;
  optionIds: string[];
  votedAt: Date;
}

export interface PollsState {
  polls: Poll[];
  userVotes: Map<string, string[]>; // pollId -> optionIds
  loading: boolean;
  error: string | null;
}

export interface CreatePollRequest {
  question: string;
  options: string[];
  allowMultiple?: boolean;
  showResults?: boolean;
  expiresAt?: Date;
}

export interface VoteRequest {
  pollId: string;
  optionIds: string[];
}