// Education Service - Connects to PHP Education module
import { getApiUrl } from '../config/api';

export interface Course {
  id: string;
  title: string;
  description: string;
  image: string;
  tutorId: string;
  tutorName: string;
  category: string;
  price: number;
  duration: number;
  enrolledCount: number;
  rating: number;
}

export interface Tutor {
  id: string;
  name: string;
  image: string;
  expertise: string[];
  rating: number;
  studentsCount: number;
  bio: string;
}

export interface Enrollment {
  id: string;
  courseId: string;
  progress: number;
  status: 'active' | 'completed' | 'dropped';
  enrolledAt: string;
}

export interface Lesson {
  id: string;
  courseId: string;
  title: string;
  content: string;
  videoUrl?: string;
  duration: number;
  order: number;
}

class EducationService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getCourses(): Promise<Course[]> {
    const url = getApiUrl('education', '/api/education/courses');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch courses');
    const data = await response.json();
    return data.courses || [];
  }

  async getCourse(id: string): Promise<Course> {
    const url = getApiUrl('education', `/api/education/courses/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch course');
    return response.json();
  }

  async getTutors(): Promise<Tutor[]> {
    const url = getApiUrl('education', '/api/education/tutors');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch tutors');
    const data = await response.json();
    return data.tutors || [];
  }

  async enrollCourse(courseId: string): Promise<Enrollment> {
    const url = getApiUrl('education', '/api/education/enroll');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ courseId }),
    });
    if (!response.ok) throw new Error('Failed to enroll');
    return response.json();
  }

  async getMyEnrollments(): Promise<Enrollment[]> {
    const url = getApiUrl('education', '/api/education/enrollments/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch enrollments');
    const data = await response.json();
    return data.enrollments || [];
  }

  async getCourseLessons(courseId: string): Promise<Lesson[]> {
    const url = getApiUrl('education', `/api/education/courses/${courseId}/lessons`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch lessons');
    const data = await response.json();
    return data.lessons || [];
  }
}

export const educationService = new EducationService();
