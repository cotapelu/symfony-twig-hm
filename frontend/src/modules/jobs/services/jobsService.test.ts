import { describe, it, expect, vi, beforeEach } from 'vitest';
import { JobsService } from './jobsService';

global.fetch = vi.fn();

describe('JobsService', () => {
  let service: JobsService;

  beforeEach(() => {
    service = new JobsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getJobs', () => {
    it('should fetch jobs', async () => {
      const jobs = [{ id: 'j1', title: 'Developer' }];
      (global.fetch as any).mockResolvedValue(mockResponse(jobs));
      const result = await service.getJobs();
      expect(result).toEqual(jobs);
      expect(fetch).toHaveBeenCalledWith('/api/jobs');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getJobs()).rejects.toThrow('Failed to fetch jobs');
    });
  });

  describe('getJob', () => {
    it('should fetch job by id', async () => {
      const job = { id: 'j1' };
      (global.fetch as any).mockResolvedValue(mockResponse(job));
      const result = await service.getJob('j1');
      expect(result).toEqual(job);
      expect(fetch).toHaveBeenCalledWith('/api/jobs/j1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getJob('j1')).rejects.toThrow('Failed to fetch job');
    });
  });

  describe('createJob', () => {
    it('should create job', async () => {
      const created = { id: 'new', title: 'New Job' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const request = { title: 'New Job', description: '...' };
      const result = await service.createJob(request);
      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/jobs', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createJob({ title: 'X' })).rejects.toThrow('Failed to create job');
    });
  });

  describe('getApplications', () => {
    it('should fetch applications', async () => {
      const apps = [{ id: 'a1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(apps));
      const result = await service.getApplications();
      expect(result).toEqual(apps);
      expect(fetch).toHaveBeenCalledWith('/api/jobs/applications');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getApplications()).rejects.toThrow('Failed to fetch applications');
    });
  });

  describe('apply', () => {
    it('should apply to job', async () => {
      const app = { id: 'a1' };
      (global.fetch as any).mockResolvedValue(mockResponse(app));
      const request = { jobId: 'j1', coverLetter: '...' };
      const result = await service.apply(request);
      expect(result).toEqual(app);
      expect(fetch).toHaveBeenCalledWith('/api/jobs/applications', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.apply({ jobId: 'j1' })).rejects.toThrow('Failed to apply');
    });
  });
});
