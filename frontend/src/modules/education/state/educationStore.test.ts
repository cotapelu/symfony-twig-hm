import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  educationStore,
  courses,
  enrollments,
  educationLoading,
  educationError,
  publishedCourses,
  myCourses
} from './educationStore';
import { EducationService } from '../services/educationService';
import type { Course, Enrollment } from '../contracts/educationTypes';

function getCurrentState() {
  let current: any;
  const unsub = educationStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockCourse = (overrides: Partial<Course> = {}): Course => ({
  id: `course-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Course Title',
  description: 'Description',
  price: 100,
  isPublished: true,
  instructorId: 'inst-1',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockEnrollment = (overrides: Partial<Enrollment> = {}): Enrollment => ({
  id: `enroll-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  course: mockCourse({ id: 'course-1' }),
  progress: 0,
  enrolledAt: now,
  ...overrides
});

describe('EducationStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    educationStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.courses).toEqual([]);
      expect(state.enrollments).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let c: Course[] = [], e: Enrollment[] = [], l = false, err: string | null = null;
      const unsubC = courses.subscribe(s => { c = s; });
      const unsubE = enrollments.subscribe(s => { e = s; });
      const unsubL = educationLoading.subscribe(s => { l = s; });
      const unsubErr = educationError.subscribe(s => { err = s; });
      expect(c).toEqual([]);
      expect(e).toEqual([]);
      expect(l).toBe(false);
      expect(err).toBeNull();
      unsubC(); unsubE(); unsubL(); unsubErr();
    });
  });

  describe('loadCourses', () => {
    it('loads courses', async () => {
      const crs = [mockCourse(), mockCourse()];
      vi.spyOn(EducationService.prototype, 'getCourses').mockResolvedValue(crs);
      await educationStore.loadCourses();
      const state = getCurrentState();
      expect(state.courses).toEqual(crs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(EducationService.prototype, 'getCourses').mockReturnValue(p as any);
      const loadPromise = educationStore.loadCourses();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockCourse()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates courses derived on success', async () => {
      const crs = [mockCourse()];
      vi.spyOn(EducationService.prototype, 'getCourses').mockResolvedValue(crs);
      await educationStore.loadCourses();
      let c: Course[] = [];
      const unsub = courses.subscribe(s => { c = s; });
      expect(c).toEqual(crs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(EducationService.prototype, 'getCourses').mockResolvedValue([]);
      await educationStore.loadCourses();
      expect(getCurrentState().courses).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(EducationService.prototype, 'getCourses').mockRejectedValue(new Error('Courses failed'));
      await educationStore.loadCourses();
      const state = getCurrentState();
      expect(state.error).toBe('Courses failed');
      expect(state.loading).toBe(false);
      expect(state.courses).toEqual([]);
    });
  });

  describe('loadCourse', () => {
    it('adds course if not exists', async () => {
      const crs = mockCourse({ id: 'c1' });
      vi.spyOn(EducationService.prototype, 'getCourse').mockResolvedValue(crs);
      await educationStore.loadCourse('c1');
      const state = getCurrentState();
      expect(state.courses).toContainEqual(crs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('replaces existing course', async () => {
      const original = mockCourse({ id: 'c1', title: 'Original' });
      const updated = mockCourse({ id: 'c1', title: 'Updated' });
      // Load original
      vi.spyOn(EducationService.prototype, 'getCourse').mockResolvedValueOnce(original);
      await educationStore.loadCourse('c1');
      let state = getCurrentState();
      expect(state.courses[0].title).toBe('Original');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      // Update
      vi.spyOn(EducationService.prototype, 'getCourse').mockResolvedValueOnce(updated);
      await educationStore.loadCourse('c1');
      state = getCurrentState();
      expect(state.courses).toHaveLength(1);
      expect(state.courses[0].title).toBe('Updated');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(EducationService.prototype, 'getCourse').mockReturnValue(p as any);
      const loadPromise = educationStore.loadCourse('c1');
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise(mockCourse({ id: 'c1' }));
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(EducationService.prototype, 'getCourse').mockRejectedValue(new Error('Not found'));
      try {
        await educationStore.loadCourse('missing');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
    });
  });

  describe('createCourse', () => {
    it('creates course and adds to courses', async () => {
      const newCourse = mockCourse({ id: 'c1', title: 'New' });
      vi.spyOn(EducationService.prototype, 'createCourse').mockResolvedValue(newCourse);
      const result = await educationStore.createCourse({ title: 'New', description: 'Desc', price: 100 });
      expect(result).toEqual(newCourse);
      const state = getCurrentState();
      expect(state.courses).toContainEqual(newCourse);
    });

    it('propagates error', async () => {
      vi.spyOn(EducationService.prototype, 'createCourse').mockRejectedValue(new Error('Create error'));
      try {
        await educationStore.createCourse({ title: 'New', description: 'Desc', price: 100 });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Create error');
    });
  });

  describe('loadEnrollments', () => {
    it('loads enrollments', async () => {
      const enrolls = [mockEnrollment(), mockEnrollment()];
      vi.spyOn(EducationService.prototype, 'getEnrollments').mockResolvedValue(enrolls);
      await educationStore.loadEnrollments();
      const state = getCurrentState();
      expect(state.enrollments).toEqual(enrolls);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(EducationService.prototype, 'getEnrollments').mockReturnValue(p as any);
      const loadPromise = educationStore.loadEnrollments();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockEnrollment()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates enrollments derived on success', async () => {
      const enrolls = [mockEnrollment()];
      vi.spyOn(EducationService.prototype, 'getEnrollments').mockResolvedValue(enrolls);
      await educationStore.loadEnrollments();
      let e: Enrollment[] = [];
      const unsub = enrollments.subscribe(s => { e = s; });
      expect(e).toEqual(enrolls);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(EducationService.prototype, 'getEnrollments').mockResolvedValue([]);
      await educationStore.loadEnrollments();
      expect(getCurrentState().enrollments).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(EducationService.prototype, 'getEnrollments').mockRejectedValue(new Error('Enrollments failed'));
      await educationStore.loadEnrollments();
      const state = getCurrentState();
      expect(state.error).toBe('Enrollments failed');
      expect(state.loading).toBe(false);
      expect(state.enrollments).toEqual([]);
    });
  });

  describe('enroll', () => {
    it('enrolls user and adds to enrollments', async () => {
      const enrollment = mockEnrollment({ id: 'e1' });
      vi.spyOn(EducationService.prototype, 'enroll').mockResolvedValue(enrollment);
      const result = await educationStore.enroll({ courseId: 'c1', userId: 'u1' });
      expect(result).toEqual(enrollment);
      const state = getCurrentState();
      expect(state.enrollments).toContainEqual(enrollment);
    });

    it('handles error', async () => {
      vi.spyOn(EducationService.prototype, 'enroll').mockRejectedValue(new Error('Enroll failed'));
      try {
        await educationStore.enroll({ courseId: 'c1', userId: 'u1' });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Enroll failed');
    });
  });

  describe('updateProgress', () => {
    it('updates enrollment progress', async () => {
      const enrollment = mockEnrollment({ id: 'e1', progress: 10 });
      // Prepopulate enrollments via loadEnrollments
      vi.spyOn(EducationService.prototype, 'getEnrollments').mockResolvedValue([enrollment]);
      await educationStore.loadEnrollments();
      // Update progress
      const updated = mockEnrollment({ id: 'e1', progress: 50 });
      vi.spyOn(EducationService.prototype, 'updateProgress').mockResolvedValue(updated);
      const result = await educationStore.updateProgress('e1', 50);
      expect(result).toEqual(updated);
      let state = getCurrentState();
      expect(state.enrollments[0].progress).toBe(50);
    });

    it('handles error', async () => {
      vi.spyOn(EducationService.prototype, 'updateProgress').mockRejectedValue(new Error('Update failed'));
      try {
        await educationStore.updateProgress('e1', 50);
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Update failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(EducationService.prototype, 'getCourses').mockRejectedValue(new Error('err'));
      await educationStore.loadCourses();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      educationStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(EducationService.prototype, 'getCourses').mockResolvedValue([mockCourse()]);
      await educationStore.loadCourses();
      let state = getCurrentState();
      expect(state.courses.length).toBeGreaterThan(0);
      educationStore.reset();
      state = getCurrentState();
      expect(state.courses).toEqual([]);
      expect(state.enrollments).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('publishedCourses derived', () => {
    it('filters only published courses', async () => {
      const published = mockCourse({ id: 'p1', isPublished: true });
      const draft = mockCourse({ id: 'd1', isPublished: false });
      vi.spyOn(EducationService.prototype, 'getCourses').mockResolvedValue([published, draft]);
      await educationStore.loadCourses();
      let pub: Course[] = [];
      const unsub = publishedCourses.subscribe(s => { pub = s; });
      expect(pub).toEqual([published]);
      unsub();
    });
  });

  describe('myCourses derived', () => {
    it('maps enrollments to courses', async () => {
      const courseA = mockCourse({ id: 'cA' });
      const courseB = mockCourse({ id: 'cB' });
      const enrollA = mockEnrollment({ id: 'e1', course: courseA });
      const enrollB = mockEnrollment({ id: 'e2', course: courseB });
      vi.spyOn(EducationService.prototype, 'getEnrollments').mockResolvedValue([enrollA, enrollB]);
      await educationStore.loadEnrollments();
      let myC: Course[] = [];
      const unsub = myCourses.subscribe(s => { myC = s; });
      expect(myC).toContainEqual(courseA);
      expect(myC).toContainEqual(courseB);
      unsub();
    });
  });
});
