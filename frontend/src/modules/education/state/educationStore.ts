import { writable, derived } from 'svelte/store';
import type { Course, Enrollment, EducationState, CreateCourseRequest, EnrollCourseRequest } from '../contracts/educationTypes';
import { educationService } from '../services/educationService';

const initialState: EducationState = {
  courses: [],
  enrollments: [],
  loading: false,
  error: null
};

function createEducationStore() {
  const { subscribe, set, update } = writable<EducationState>(initialState);

  return {
    subscribe,

    async loadCourses() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const courses = await educationService.getCourses();
        update(s => ({ ...s, courses, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadCourse(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const course = await educationService.getCourse(id);
        update(s => {
          const exists = s.courses.find(c => c.id === id);
          return {
            ...s,
            courses: exists ? s.courses.map(c => c.id === id ? course : c) : [...s.courses, course],
            loading: false
          };
        });
        return course;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async createCourse(request: CreateCourseRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const course = await educationService.createCourse(request);
        update(s => ({ ...s, courses: [...s.courses, course], loading: false }));
        return course;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadEnrollments() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const enrollments = await educationService.getEnrollments();
        update(s => ({ ...s, enrollments, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async enroll(request: EnrollCourseRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const enrollment = await educationService.enroll(request);
        update(s => ({ ...s, enrollments: [...s.enrollments, enrollment], loading: false }));
        return enrollment;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async updateProgress(enrollmentId: string, progress: number) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const enrollment = await educationService.updateProgress(enrollmentId, progress);
        update(s => ({
          ...s,
          enrollments: s.enrollments.map(e => e.id === enrollmentId ? enrollment : e),
          loading: false
        }));
        return enrollment;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const educationStore = createEducationStore();

export const courses = derived(educationStore, $s => $s.courses);
export const enrollments = derived(educationStore, $s => $s.enrollments);
export const educationLoading = derived(educationStore, $s => $s.loading);
export const educationError = derived(educationStore, $s => $s.error);

export const publishedCourses = derived(educationStore, $s => $s.courses.filter(c => c.isPublished));
export const myCourses = derived(educationStore, $s => $s.enrollments.map(e => e.course).filter(Boolean));
