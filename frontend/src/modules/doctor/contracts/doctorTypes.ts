export interface Doctor {
  id: string;
  userId: string;
  name: string;
  title: string;
  specialty: string;
  hospital?: string;
  clinic?: string;
  rating: number;
  reviewCount: number;
  experience: number;
  education: string[];
  certifications: string[];
  languages: string[];
  bio?: string;
  avatar?: string;
  consultationFee: number;
  available: boolean;
}

export interface Appointment {
  id: string;
  patientId: string;
  doctorId: string;
  doctorName: string;
  type: 'consultation' | 'followup' | 'emergency';
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled';
  scheduledAt: string;
  duration: number;
  symptoms?: string;
  notes?: string;
  prescription?: string;
  createdAt: string;
  completedAt?: string;
}

export interface MedicalRecord {
  id: string;
  patientId: string;
  appointmentId?: string;
  doctorId: string;
  diagnosis: string;
  prescription?: string;
  notes?: string;
  attachments?: string[];
  createdAt: string;
}

export interface TelemedicineSession {
  id: string;
  appointmentId: string;
  patientId: string;
  doctorId: string;
  status: 'waiting' | 'in_progress' | 'completed' | 'failed';
  startedAt?: string;
  endedAt?: string;
  roomUrl?: string;
}

export interface DoctorState {
  doctors: Doctor[];
  appointments: Appointment[];
  medicalRecords: MedicalRecord[];
  currentSession: TelemedicineSession | null;
  loading: boolean;
  error: string | null;
  currentUserId: string | null;
  pagination: {
    page: number;
    pageSize: number;
    total: number;
    totalPages: number;
  };
}

export interface BookAppointmentRequest {
  doctorId: string;
  type: Appointment['type'];
  scheduledAt: string;
  symptoms?: string;
}

export interface StartTelemedicineRequest {
  appointmentId: string;
}
