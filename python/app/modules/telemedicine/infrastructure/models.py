"""SQLAlchemy models for Telemedicine module."""
import uuid
from datetime import datetime, date
import sqlalchemy as sa
from sqlalchemy.orm import relationship
from app.shared.models.unified import Base


class DoctorModel(Base):
    """Doctor database model."""
    __tablename__ = 'telemedicine_doctors'

    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    full_name = sa.Column(sa.String(255), nullable=False)
    specialty = sa.Column(sa.String(255), nullable=False, index=True)
    qualifications = sa.Column(sa.Text)  # JSON array
    experience_years = sa.Column(sa.Integer, nullable=False, default=0)
    rating = sa.Column(sa.Float, nullable=False, default=0.0, index=True)
    review_count = sa.Column(sa.Integer, nullable=False, default=0)
    consultation_fee = sa.Column(sa.Numeric(10, 0), nullable=False, default=0)
    available_languages = sa.Column(sa.Text)  # JSON array
    profile_image = sa.Column(sa.String(500))
    is_available = sa.Column(sa.Boolean, default=True, index=True)
    working_hours = sa.Column(sa.Text)  # JSON object
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, nullable=False, index=True)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)

    # Relationships
    appointments = relationship("AppointmentModel", back_populates="doctor", cascade="all, delete-orphan")
    reviews = relationship("ReviewModel", back_populates="doctor", cascade="all, delete-orphan")

    __table_args__ = (
        sa.UniqueConstraint('user_id', name='uq_telemedicine_doctor_user_id'),
    )


class AppointmentModel(Base):
    """Appointment database model."""
    __tablename__ = 'telemedicine_appointments'

    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    doctor_id = sa.Column(sa.String(36), sa.ForeignKey('telemedicine_doctors.id', ondelete='CASCADE'), nullable=False, index=True)
    patient_id = sa.Column(sa.String(36), sa.ForeignKey('users.id', ondelete='CASCADE'), nullable=False, index=True)
    scheduled_time = sa.Column(sa.DateTime, nullable=False, index=True)
    duration_minutes = sa.Column(sa.Integer, nullable=False, default=30)
    consultation_type = sa.Column(sa.String(50), nullable=False)  # video, audio, chat
    status = sa.Column(sa.String(50), nullable=False, default='pending', index=True)
    symptoms = sa.Column(sa.Text)
    diagnosis = sa.Column(sa.Text)
    prescription = sa.Column(sa.Text)
    notes = sa.Column(sa.Text)
    meeting_link = sa.Column(sa.String(500))
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, nullable=False, index=True)
    completed_at = sa.Column(sa.DateTime, nullable=True)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)

    # Relationships
    doctor = relationship("DoctorModel", back_populates="appointments")
    review = relationship("ReviewModel", back_populates="appointment", uselist=False, cascade="all, delete-orphan")

    __table_args__ = (
        sa.UniqueConstraint('doctor_id', 'scheduled_time', name='uq_telemedicine_appointment_doctor_time'),
    )


class ReviewModel(Base):
    """Review database model."""
    __tablename__ = 'telemedicine_reviews'

    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    appointment_id = sa.Column(sa.String(36), sa.ForeignKey('telemedicine_appointments.id', ondelete='CASCADE'), nullable=False, unique=True, index=True)
    doctor_id = sa.Column(sa.String(36), sa.ForeignKey('telemedicine_doctors.id', ondelete='CASCADE'), nullable=False, index=True)
    patient_id = sa.Column(sa.String(36), sa.ForeignKey('users.id', ondelete='CASCADE'), nullable=False, index=True)
    rating = sa.Column(sa.Integer, nullable=False)  # 1-5
    comment = sa.Column(sa.Text)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, nullable=False, index=True)

    # Relationships
    appointment = relationship("AppointmentModel", back_populates="review")
    doctor = relationship("DoctorModel", back_populates="reviews")

    __table_args__ = (
        sa.Index('ix_telemedicine_reviews_rating', 'rating'),
    )
