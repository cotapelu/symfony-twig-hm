import type { Course, Enrollment, EducationState, CreateCourseRequest, EnrollCourseRequest } from '../contracts/educationTypes';

const API_BASE = '/api/education';

export class EducationService {
  async getCourses(): Promise<Course[]> {
    const response = await fetch(`${API_BASE}/courses`);
    if (!response.ok) throw new Error('Failed to fetch courses');
    return response.json();
  }

  async getCourse(id: string): Promise<Course> {
    const response = await fetch(`${API_BASE}/courses/${id}`);
    if (!response.ok) throw new Error('Failed to fetch course');
    return response.json();
  }

  async createCourse(request: CreateCourseRequest): Promise<Course> {
    const response = await fetch(`${API_BASE}/courses`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create course');
    return response.json();
  }

  async updateCourse(id: string, request: Partial<CreateCourseRequest>): Promise<Course> {
    const response = await fetch(`${API_BASE}/courses/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to update course');
    return response.json();
  }

  async deleteCourse(id: string): Promise<void> {
    const response = await fetch(`${API_BASE}/courses/${id}`, { method: 'DELETE' });
    if (!response.ok) throw new Error('Failed to delete course');
  }

  async getEnrollments(): Promise<Enrollment[]> {
    const response = await fetch(`${API_BASE}/enrollments`);
    if (!response.ok) throw new Error('Failed to fetch enrollments');
    return response.json();
  }

  async enroll(request: EnrollCourseRequest): Promise<Enrollment> {
    const response = await fetch(`${API_BASE}/enrollments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to enroll');
    return response.json();
  }

  async updateProgress(enrollmentId: string, progress: number): Promise<Enrollment> {
    const response = await fetch(`${API_BASE}/enrollments/${enrollmentId}/progress`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ progress })
    });
    if (!response.ok) throw new Error('Failed to update progress');
    return response.json();
  }
}

export const educationService = new EducationService();
export default educationService;
