export interface Job {
  id: string;
  employerId: string;
  title: string;
  description: string;
  requirements: string[];
  responsibilities: string[];
  location: { city: string; country: string; remote?: boolean };
  type: 'full_time' | 'part_time' | 'contract' | 'internship' | 'freelance';
  category: string;
  salary?: { min: number; max: number; currency: string };
  experienceLevel: 'entry' | 'mid' | 'senior' | 'lead';
  benefits?: string[];
  postedAt: string;
  expiresAt: string;
  status: 'active' | 'closed' | 'draft';
}

export interface JobApplication {
  id: string;
  jobId: string;
  userId: string;
  resume?: string;
  coverLetter?: string;
  status: 'pending' | 'reviewing' | 'interview' | 'rejected' | 'accepted';
  appliedAt: string;
  updatedAt: string;
}

export interface JobsState {
  jobs: Job[];
  applications: JobApplication[];
  loading: boolean;
  error: string | null;
}

export interface CreateJobRequest {
  title: string;
  description: string;
  requirements: string[];
  responsibilities: string[];
  location: Job['location'];
  type: Job['type'];
  category: string;
  salary?: Job['salary'];
  experienceLevel: Job['experienceLevel'];
  benefits?: string[];
}

export interface ApplyJobRequest {
  jobId: string;
  resume?: string;
  coverLetter?: string;
}
