export interface Course {
  id: string;
  instructorId: string;
  title: string;
  description: string;
  category: string;
  level: 'beginner' | 'intermediate' | 'advanced';
  thumbnail?: string;
  duration: number;
  lessonsCount: number;
  studentsCount: number;
  rating?: number;
  price: number;
  currency: string;
  isPublished: boolean;
  tags?: string[];
  prerequisites?: string[];
  outcomes?: string[];
  createdAt: string;
  updatedAt: string;
}

export interface Lesson {
  id: string;
  courseId: string;
  title: string;
  description?: string;
  videoUrl?: string;
  content?: string;
  duration: number;
  order: number;
  isFree: boolean;
}

export interface Enrollment {
  id: string;
  userId: string;
  courseId: string;
  course?: Course;
  progress: number;
  status: 'active' | 'completed' | 'expired';
  enrolledAt: string;
  completedAt?: string;
}

export interface EducationState {
  courses: Course[];
  enrollments: Enrollment[];
  loading: boolean;
  error: string | null;
}

export interface CreateCourseRequest {
  title: string;
  description: string;
  category: string;
  level: Course['level'];
  price: number;
  tags?: string[];
}

export interface EnrollCourseRequest {
  courseId: string;
}
