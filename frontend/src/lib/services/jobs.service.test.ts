import { describe, it, expect, vi, beforeEach } from 'vitest';
import { jobsService } from './jobs';
import { getApiUrl } from '../config/api';

vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('JobsService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getJobs', () => {
    it('fetches all jobs', async () => {
      const jobs = [{ id: 'j1', title: 'Dev', company: 'TechCo', location: 'Remote', salary: '100k', type: 'fulltime', description: '', requirements: [], postedAt: '', expiresAt: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ jobs }) });

      const result = await jobsService.getJobs();

      expect(getApiUrl).toHaveBeenCalledWith('jobs', '/api/jobs');
      expect(result).toEqual(jobs);
    });

    it('searches with query', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await jobsService.getJobs('engineer');
      const calledUrl = getApiUrl.mock.calls[0][1];
      expect(calledUrl).toContain('q=engineer');
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await jobsService.getJobs();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(jobsService.getJobs()).rejects.toThrow('Failed to fetch jobs');
    });
  });

  describe('getJob', () => {
    it('fetches a job by id', async () => {
      const job = { id: 'j1', title: 'Position', company: 'Corp', location: 'Office', salary: '50k', type: 'parttime', description: '', requirements: [], postedAt: '', expiresAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => job });

      const result = await jobsService.getJob('j1');

      expect(getApiUrl).toHaveBeenCalledWith('jobs', '/api/jobs/j1');
      expect(result).toEqual(job);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(jobsService.getJob('j1')).rejects.toThrow('Failed to fetch job');
    });
  });

  describe('applyJob', () => {
    it('applies to a job', async () => {
      const application = { id: 'a1', jobId: 'j1', status: 'pending', appliedAt: '2024-01-01' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => application });

      const result = await jobsService.applyJob('j1');

      expect(getApiUrl).toHaveBeenCalledWith('jobs', '/api/jobs/apply');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ jobId: 'j1' }),
      });
      expect(result).toEqual(application);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(jobsService.applyJob('j1')).rejects.toThrow('Failed to apply');
    });
  });

  describe('getMyApplications', () => {
    it('fetches user applications', async () => {
      const applications = [{ id: 'a1', jobId: 'j1', status: 'pending', appliedAt: '2024-01-01' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ applications }) });

      const result = await jobsService.getMyApplications();

      expect(getApiUrl).toHaveBeenCalledWith('jobs', '/api/jobs/applications/my');
      expect(result).toEqual(applications);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await jobsService.getMyApplications();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(jobsService.getMyApplications()).rejects.toThrow('Failed to fetch applications');
    });
  });
});
