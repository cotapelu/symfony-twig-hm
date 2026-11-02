export interface JobCreatedEvent { type: 'jobs.job.created'; payload: { jobId: string; employerId: string; title: string }; }
export interface JobAppliedEvent { type: 'jobs.applied'; payload: { applicationId: string; jobId: string; userId: string }; }
export type JobsEvent = JobCreatedEvent | JobAppliedEvent;
export const jobsEvents = {
  jobCreated: (jobId: string, employerId: string, title: string): JobCreatedEvent => ({ type: 'jobs.job.created', payload: { jobId, employerId, title } }),
  jobApplied: (applicationId: string, jobId: string, userId: string): JobAppliedEvent => ({ type: 'jobs.applied', payload: { applicationId, jobId, userId } })
};
