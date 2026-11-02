import { writable, derived } from 'svelte/store';
import type { Job, JobApplication, JobsState, CreateJobRequest, ApplyJobRequest } from '../contracts/jobsTypes';
import { jobsService } from '../services/jobsService';

const initialState: JobsState = { jobs: [], applications: [], loading: false, error: null };

function createJobsStore() {
  const { subscribe, set, update } = writable<JobsState>(initialState);
  return {
    subscribe,
    async loadJobs() { update(s => ({ ...s, loading: true, error: null })); try { const jobs = await jobsService.getJobs(); update(s => ({ ...s, jobs, loading: false })); } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); } },
    async loadJob(id: string) { update(s => ({ ...s, loading: true, error: null })); try { const job = await jobsService.getJob(id); update(s => ({ ...s, jobs: [...s.jobs.filter(j => j.id !== id), job], loading: false })); return job; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    async createJob(request: CreateJobRequest) { update(s => ({ ...s, loading: true, error: null })); try { const job = await jobsService.createJob(request); update(s => ({ ...s, jobs: [...s.jobs, job], loading: false })); return job; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    async loadApplications() { update(s => ({ ...s, loading: true, error: null })); try { const apps = await jobsService.getApplications(); update(s => ({ ...s, applications: apps, loading: false })); } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); } },
    async apply(request: ApplyJobRequest) { update(s => ({ ...s, loading: true, error: null })); try { const app = await jobsService.apply(request); update(s => ({ ...s, applications: [...s.applications, app], loading: false })); return app; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const jobsStore = createJobsStore();
export const jobs = derived(jobsStore, $s => $s.jobs);
export const applications = derived(jobsStore, $s => $s.applications);
export const jobsLoading = derived(jobsStore, $s => $s.loading);
export const jobsError = derived(jobsStore, $s => $s.error);
export const activeJobs = derived(jobsStore, $s => $s.jobs.filter(j => j.status === 'active'));
