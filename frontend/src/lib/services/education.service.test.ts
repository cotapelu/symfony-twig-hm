import { describe, it, expect, vi, beforeEach } from 'vitest';
import { educationService } from './education';
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

describe('EducationService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getCourses', () => {
    it('fetches all courses', async () => {
      const courses = [{ id: 'c1', title: 'Math', price: 100, duration: 10, enrolledCount: 5, rating: 4 }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ courses }) });

      const result = await educationService.getCourses();

      expect(getApiUrl).toHaveBeenCalledWith('education', '/api/education/courses');
      expect(result).toEqual(courses);
    });

    it('returns empty array if no courses', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await educationService.getCourses();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(educationService.getCourses()).rejects.toThrow('Failed to fetch courses');
    });
  });

  describe('getCourse', () => {
    it('fetches a specific course', async () => {
      const course = { id: 'c1', title: 'Physics', price: 200, duration: 20, enrolledCount: 10, rating: 5 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => course });

      const result = await educationService.getCourse('c1');

      expect(getApiUrl).toHaveBeenCalledWith('education', '/api/education/courses/c1');
      expect(result).toEqual(course);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(educationService.getCourse('c1')).rejects.toThrow('Failed to fetch course');
    });
  });

  describe('getTutors', () => {
    it('fetches all tutors', async () => {
      const tutors = [{ id: 't1', name: 'Dr. Smith', expertise: ['Math'], rating: 5, studentsCount: 100, bio: 'Experienced' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ tutors }) });

      const result = await educationService.getTutors();

      expect(getApiUrl).toHaveBeenCalledWith('education', '/api/education/tutors');
      expect(result).toEqual(tutors);
    });

    it('returns empty array if no tutors', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await educationService.getTutors();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(educationService.getTutors()).rejects.toThrow('Failed to fetch tutors');
    });
  });

  describe('enrollCourse', () => {
    it('enrolls in a course', async () => {
      const enrollment = { id: 'e1', courseId: 'c1', progress: 0, status: 'active', enrolledAt: '2024-01-01' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => enrollment });

      const result = await educationService.enrollCourse('c1');

      expect(getApiUrl).toHaveBeenCalledWith('education', '/api/education/enroll');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ courseId: 'c1' }),
      });
      expect(result).toEqual(enrollment);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(educationService.enrollCourse('c1')).rejects.toThrow('Failed to enroll');
    });
  });

  describe('getMyEnrollments', () => {
    it('fetches user enrollments', async () => {
      const enrollments = [{ id: 'e1', courseId: 'c1', progress: 50, status: 'active', enrolledAt: '2024-01-01' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ enrollments }) });

      const result = await educationService.getMyEnrollments();

      expect(getApiUrl).toHaveBeenCalledWith('education', '/api/education/enrollments/my');
      expect(result).toEqual(enrollments);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await educationService.getMyEnrollments();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(educationService.getMyEnrollments()).rejects.toThrow('Failed to fetch enrollments');
    });
  });

  describe('getCourseLessons', () => {
    it('fetches lessons for a course', async () => {
      const lessons = [{ id: 'l1', courseId: 'c1', title: 'Intro', content: '...', duration: 60, order: 1 }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ lessons }) });

      const result = await educationService.getCourseLessons('c1');

      expect(getApiUrl).toHaveBeenCalledWith('education', '/api/education/courses/c1/lessons');
      expect(result).toEqual(lessons);
    });

    it('returns empty array if no lessons', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await educationService.getCourseLessons('c1');
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(educationService.getCourseLessons('c1')).rejects.toThrow('Failed to fetch lessons');
    });
  });
});
