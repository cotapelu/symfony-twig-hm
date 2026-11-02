import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  jobsStore,
  jobs,
  applications,
  jobsLoading,
  jobsError,
  activeJobs
} from './jobsStore';
import { JobsService } from '../services/jobsService';
import type { Job, JobApplication } from '../contracts/jobsTypes';

function getCurrentState() {
  let current: any;
  const unsub = jobsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockJob = (overrides: Partial<Job> = {}): Job => ({
  id: `job-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Job Title',
  description: 'Description',
  location: 'Hanoi',
  salary: 1000,
  status: 'active',
  employerId: 'emp-1',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockApplication = (overrides: Partial<JobApplication> = {}): JobApplication => ({
  id: `app-${Math.random().toString(36).substr(2, 5)}`,
  jobId: 'job-1',
  userId: 'user-1',
  coverLetter: 'Cover letter',
  status: 'pending',
  createdAt: now,
  ...overrides
});

describe('JobsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    jobsStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.jobs).toEqual([]);
      expect(state.applications).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let j: Job[] = [], a: JobApplication[] = [], l = false, e: string | null = null, act: Job[] = [];
      const unsubJ = jobs.subscribe(s => { j = s; });
      const unsubA = applications.subscribe(s => { a = s; });
      const unsubL = jobsLoading.subscribe(s => { l = s; });
      const unsubE = jobsError.subscribe(s => { e = s; });
      const unsubAct = activeJobs.subscribe(s => { act = s; });
      expect(j).toEqual([]);
      expect(a).toEqual([]);
      expect(l).toBe(false);
      expect(e).toBeNull();
      expect(act).toEqual([]);
      unsubJ(); unsubA(); unsubL(); unsubE(); unsubAct();
    });
  });

  describe('loadJobs', () => {
    it('loads jobs', async () => {
      const jobList = [mockJob(), mockJob()];
      vi.spyOn(JobsService.prototype, 'getJobs').mockResolvedValue(jobList);
      await jobsStore.loadJobs();
      const state = getCurrentState();
      expect(state.jobs).toEqual(jobList);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(JobsService.prototype, 'getJobs').mockReturnValue(p as any);
      const loadPromise = jobsStore.loadJobs();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockJob()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates jobs derived on success', async () => {
      const jobList = [mockJob()];
      vi.spyOn(JobsService.prototype, 'getJobs').mockResolvedValue(jobList);
      await jobsStore.loadJobs();
      let j: Job[] = [];
      const unsub = jobs.subscribe(s => { j = s; });
      expect(j).toEqual(jobList);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(JobsService.prototype, 'getJobs').mockResolvedValue([]);
      await jobsStore.loadJobs();
      expect(getCurrentState().jobs).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(JobsService.prototype, 'getJobs').mockRejectedValue(new Error('Jobs failed'));
      await jobsStore.loadJobs();
      const state = getCurrentState();
      expect(state.error).toBe('Jobs failed');
      expect(state.loading).toBe(false);
      expect(state.jobs).toEqual([]);
    });
  });

  describe('loadJob', () => {
    it('adds new job', async () => {
      const job = mockJob({ id: 'j1' });
      vi.spyOn(JobsService.prototype, 'getJob').mockResolvedValue(job);
      await jobsStore.loadJob('j1');
      const state = getCurrentState();
      expect(state.jobs).toContainEqual(job);
    });

    it('replaces existing job', async () => {
      const first = mockJob({ id: 'j1', title: 'First' });
      const second = mockJob({ id: 'j1', title: 'Second' });
      vi.spyOn(JobsService.prototype, 'getJob').mockResolvedValueOnce(first);
      await jobsStore.loadJob('j1');
      let state = getCurrentState();
      expect(state.jobs[0].title).toBe('First');
      vi.spyOn(JobsService.prototype, 'getJob').mockResolvedValueOnce(second);
      await jobsStore.loadJob('j1');
      state = getCurrentState();
      expect(state.jobs).toHaveLength(1);
      expect(state.jobs[0].title).toBe('Second');
    });

    it('handles error', async () => {
      vi.spyOn(JobsService.prototype, 'getJob').mockRejectedValue(new Error('Not found'));
      try {
        await jobsStore.loadJob('j1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
    });
  });

  describe('createJob', () => {
    it('creates job and adds to jobs', async () => {
      const newJob = mockJob({ id: 'jnew', title: 'New Job' });
      vi.spyOn(JobsService.prototype, 'createJob').mockResolvedValue(newJob);
      const result = await jobsStore.createJob({ title: 'New Job', description: '', location: '', salary: 0 });
      expect(result).toEqual(newJob);
      const state = getCurrentState();
      expect(state.jobs).toContainEqual(newJob);
    });

    it('propagates error', async () => {
      vi.spyOn(JobsService.prototype, 'createJob').mockRejectedValue(new Error('Create failed'));
      try {
        await jobsStore.createJob({ title: 'New', description: '', location: '', salary: 0 });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Create failed');
    });
  });

  describe('loadApplications', () => {
    it('loads applications', async () => {
      const apps = [mockApplication(), mockApplication()];
      vi.spyOn(JobsService.prototype, 'getApplications').mockResolvedValue(apps);
      await jobsStore.loadApplications();
      const state = getCurrentState();
      expect(state.applications).toEqual(apps);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(JobsService.prototype, 'getApplications').mockRejectedValue(new Error('Apps failed'));
      await jobsStore.loadApplications();
      const state = getCurrentState();
      expect(state.error).toBe('Apps failed');
    });
  });

  describe('apply', () => {
    it('creates application and adds to applications', async () => {
      const app = mockApplication({ id: 'a1' });
      vi.spyOn(JobsService.prototype, 'apply').mockResolvedValue(app);
      const result = await jobsStore.apply({ jobId: 'j1', coverLetter: 'cover' });
      expect(result).toEqual(app);
      const state = getCurrentState();
      expect(state.applications).toContainEqual(app);
    });

    it('propagates error', async () => {
      vi.spyOn(JobsService.prototype, 'apply').mockRejectedValue(new Error('Apply failed'));
      try {
        await jobsStore.apply({ jobId: 'j1', coverLetter: 'cover' });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Apply failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(JobsService.prototype, 'getJobs').mockRejectedValue(new Error('err'));
      await jobsStore.loadJobs();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      jobsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(JobsService.prototype, 'getJobs').mockResolvedValue([mockJob()]);
      await jobsStore.loadJobs();
      let state = getCurrentState();
      expect(state.jobs.length).toBeGreaterThan(0);
      jobsStore.reset();
      state = getCurrentState();
      expect(state.jobs).toEqual([]);
      expect(state.applications).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('activeJobs derived', () => {
    it('filters jobs with status active', async () => {
      const active = mockJob({ id: 'j1', status: 'active' });
      const closed = mockJob({ id: 'j2', status: 'closed' });
      vi.spyOn(JobsService.prototype, 'getJobs').mockResolvedValue([active, closed]);
      await jobsStore.loadJobs();
      let act: Job[] = [];
      const unsub = activeJobs.subscribe(s => { act = s; });
      expect(act).toEqual([active]);
      unsub();
    });
  });
});
