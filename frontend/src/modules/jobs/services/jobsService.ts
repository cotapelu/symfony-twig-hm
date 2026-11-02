import type { Job, JobApplication, JobsState, CreateJobRequest, ApplyJobRequest } from '../contracts/jobsTypes';

const API_BASE = '/api/jobs';

export class JobsService {
  async getJobs(): Promise<Job[]> { const r = await fetch(API_BASE); if (!r.ok) throw new Error('Failed to fetch jobs'); return r.json(); }
  async getJob(id: string): Promise<Job> { const r = await fetch(`${API_BASE}/${id}`); if (!r.ok) throw new Error('Failed to fetch job'); return r.json(); }
  async createJob(request: CreateJobRequest): Promise<Job> { const r = await fetch(API_BASE, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(request) }); if (!r.ok) throw new Error('Failed to create job'); return r.json(); }
  async getApplications(): Promise<JobApplication[]> { const r = await fetch(`${API_BASE}/applications`); if (!r.ok) throw new Error('Failed to fetch applications'); return r.json(); }
  async apply(request: ApplyJobRequest): Promise<JobApplication> { const r = await fetch(`${API_BASE}/applications`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(request) }); if (!r.ok) throw new Error('Failed to apply'); return r.json(); }
}

export const jobsService = new JobsService();
export default jobsService;
