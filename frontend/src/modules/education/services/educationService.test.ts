import { describe, it, expect, vi, beforeEach } from 'vitest';
import { EducationService } from './educationService';

global.fetch = vi.fn();

describe('EducationService', () => {
  let service: EducationService;

  beforeEach(() => {
    service = new EducationService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getCourses', () => {
    it('should fetch courses', async () => {
      const courses = [{ id: 'c1', title: 'Math' }];
      (global.fetch as any).mockResolvedValue(mockResponse(courses));
      const result = await service.getCourses();
      expect(result).toEqual(courses);
      expect(global.fetch).toHaveBeenCalledWith('/api/education/courses');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getCourses()).rejects.toThrow('Failed to fetch courses');
    });
  });

  describe('getCourse', () => {
    it('should fetch course by id', async () => {
      const course = { id: 'c1' };
      (global.fetch as any).mockResolvedValue(mockResponse(course));
      const result = await service.getCourse('c1');
      expect(result).toEqual(course);
      expect(global.fetch).toHaveBeenCalledWith('/api/education/courses/c1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getCourse('c1')).rejects.toThrow('Failed to fetch course');
    });
  });

  describe('createCourse', () => {
    it('should create course', async () => {
      const created = { id: 'new', title: 'Physics' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const request = { title: 'Physics', description: 'Basics' };
      const result = await service.createCourse(request);
      expect(result).toEqual(created);
      expect(global.fetch).toHaveBeenCalledWith('/api/education/courses', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createCourse({ title: 'X' })).rejects.toThrow('Failed to create course');
    });
  });

  describe('updateCourse', () => {
    it('should update course', async () => {
      const updated = { id: 'c1', title: 'Advanced' };
      (global.fetch as any).mockResolvedValue(mockResponse(updated));
      const result = await service.updateCourse('c1', { title: 'Advanced' });
      expect(result).toEqual(updated);
      expect(global.fetch).toHaveBeenCalledWith('/api/education/courses/c1', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title: 'Advanced' })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.updateCourse('c1', {})).rejects.toThrow('Failed to update course');
    });
  });

  describe('deleteCourse', () => {
    it('should delete course', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.deleteCourse('c1');
      expect(global.fetch).toHaveBeenCalledWith('/api/education/courses/c1', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.deleteCourse('c1')).rejects.toThrow('Failed to delete course');
    });
  });

  describe('getEnrollments', () => {
    it('should fetch enrollments', async () => {
      const enrollments = [{ id: 'e1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(enrollments));
      const result = await service.getEnrollments();
      expect(result).toEqual(enrollments);
      expect(global.fetch).toHaveBeenCalledWith('/api/education/enrollments');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getEnrollments()).rejects.toThrow('Failed to fetch enrollments');
    });
  });

  describe('enroll', () => {
    it('should enroll in course', async () => {
      const enrollment = { id: 'enr1' };
      (global.fetch as any).mockResolvedValue(mockResponse(enrollment));
      const request = { courseId: 'c1' };
      const result = await service.enroll(request);
      expect(result).toEqual(enrollment);
      expect(global.fetch).toHaveBeenCalledWith('/api/education/enrollments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.enroll({ courseId: 'c1' })).rejects.toThrow('Failed to enroll');
    });
  });

  describe('updateProgress', () => {
    it('should update progress', async () => {
      const enrollment = { id: 'enr1', progress: 50 };
      (global.fetch as any).mockResolvedValue(mockResponse(enrollment));
      const result = await service.updateProgress('enr1', 50);
      expect(result).toEqual(enrollment);
      expect(global.fetch).toHaveBeenCalledWith('/api/education/enrollments/enr1/progress', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ progress: 50 })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.updateProgress('enr1', 50)).rejects.toThrow('Failed to update progress');
    });
  });
});
