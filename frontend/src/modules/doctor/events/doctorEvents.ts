export interface AppointmentBookedEvent {
  type: 'doctor.appointment.booked';
  payload: {
    appointmentId: string;
    doctorId: string;
    patientId: string;
    scheduledAt: string;
  };
}

export interface AppointmentCompletedEvent {
  type: 'doctor.appointment.completed';
  payload: {
    appointmentId: string;
    doctorId: string;
    patientId: string;
  };
}

export interface AppointmentCancelledEvent {
  type: 'doctor.appointment.cancelled';
  payload: {
    appointmentId: string;
    doctorId: string;
    patientId: string;
    reason?: string;
  };
}

export interface TelemedicineStartedEvent {
  type: 'doctor.telemedicine.started';
  payload: {
    sessionId: string;
    appointmentId: string;
    roomUrl: string;
  };
}

export interface TelemedicineEndedEvent {
  type: 'doctor.telemedicine.ended';
  payload: {
    sessionId: string;
    appointmentId: string;
    duration: number;
  };
}

export type DoctorEvent = 
  | AppointmentBookedEvent 
  | AppointmentCompletedEvent 
  | AppointmentCancelledEvent 
  | TelemedicineStartedEvent 
  | TelemedicineEndedEvent;

export const doctorEvents = {
  appointmentBooked: (appointmentId: string, doctorId: string, patientId: string, scheduledAt: string): AppointmentBookedEvent => ({
    type: 'doctor.appointment.booked',
    payload: { appointmentId, doctorId, patientId, scheduledAt }
  }),

  appointmentCompleted: (appointmentId: string, doctorId: string, patientId: string): AppointmentCompletedEvent => ({
    type: 'doctor.appointment.completed',
    payload: { appointmentId, doctorId, patientId }
  }),

  appointmentCancelled: (appointmentId: string, doctorId: string, patientId: string, reason?: string): AppointmentCancelledEvent => ({
    type: 'doctor.appointment.cancelled',
    payload: { appointmentId, doctorId, patientId, reason }
  }),

  telemedicineStarted: (sessionId: string, appointmentId: string, roomUrl: string): TelemedicineStartedEvent => ({
    type: 'doctor.telemedicine.started',
    payload: { sessionId, appointmentId, roomUrl }
  }),

  telemedicineEnded: (sessionId: string, appointmentId: string, duration: number): TelemedicineEndedEvent => ({
    type: 'doctor.telemedicine.ended',
    payload: { sessionId, appointmentId, duration }
  })
};
