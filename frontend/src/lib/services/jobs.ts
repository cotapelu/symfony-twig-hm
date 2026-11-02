// Jobs Service - Connects to PHP JobFinding module
import { getApiUrl } from '../config/api';

export interface JobPosting {
  id: string;
  title: string;
  company: string;
  location: string;
  salary: string;
  type: 'fulltime' | 'parttime' | 'contract' | 'intern';
  description: string;
  requirements: string[];
  postedAt: string;
  expiresAt: string;
}

export interface JobApplication {
  id: string;
  jobId: string;
  status: 'pending' | 'reviewing' | 'interview' | 'rejected' | 'hired';
  appliedAt: string;
}

class JobsService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getJobs(query?: string): Promise<JobPosting[]> {
    const url = getApiUrl('jobs', `/api/jobs${query ? `?q=${encodeURIComponent(query)}` : ''}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch jobs');
    const data = await response.json();
    return data.jobs || [];
  }

  async getJob(id: string): Promise<JobPosting> {
    const url = getApiUrl('jobs', `/api/jobs/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch job');
    return response.json();
  }

  async applyJob(jobId: string): Promise<JobApplication> {
    const url = getApiUrl('jobs', '/api/jobs/apply');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ jobId }),
    });
    if (!response.ok) throw new Error('Failed to apply');
    return response.json();
  }

  async getMyApplications(): Promise<JobApplication[]> {
    const url = getApiUrl('jobs', '/api/jobs/applications/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch applications');
    const data = await response.json();
    return data.applications || [];
  }
}

export const jobsService = new JobsService();
