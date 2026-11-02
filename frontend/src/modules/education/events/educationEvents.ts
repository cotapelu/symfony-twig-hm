export interface CourseCreatedEvent {
  type: 'education.course.created';
  payload: { courseId: string; instructorId: string; title: string };
}

export interface CourseEnrolledEvent {
  type: 'education.course.enrolled';
  payload: { enrollmentId: string; userId: string; courseId: string };
}

export interface CourseCompletedEvent {
  type: 'education.course.completed';
  payload: { enrollmentId: string; userId: string; courseId: string };
}

export interface ProgressUpdatedEvent {
  type: 'education.progress.updated';
  payload: { enrollmentId: string; progress: number };
}

export type EducationEvent = CourseCreatedEvent | CourseEnrolledEvent | CourseCompletedEvent | ProgressUpdatedEvent;

export const educationEvents = {
  courseCreated: (courseId: string, instructorId: string, title: string): CourseCreatedEvent => ({
    type: 'education.course.created',
    payload: { courseId, instructorId, title }
  }),
  courseEnrolled: (enrollmentId: string, userId: string, courseId: string): CourseEnrolledEvent => ({
    type: 'education.course.enrolled',
    payload: { enrollmentId, userId, courseId }
  }),
  courseCompleted: (enrollmentId: string, userId: string, courseId: string): CourseCompletedEvent => ({
    type: 'education.course.completed',
    payload: { enrollmentId, userId, courseId }
  }),
  progressUpdated: (enrollmentId: string, progress: number): ProgressUpdatedEvent => ({
    type: 'education.progress.updated',
    payload: { enrollmentId, progress }
  })
};
