import { z } from 'zod';

// Common validation schemas for form inputs

export const emailSchema = z.string()
  .email('Invalid email address')
  .min(5, 'Email too short')
  .max(255, 'Email too long');

export const passwordSchema = z.string()
  .min(8, 'Password must be at least 8 characters')
  .regex(/[A-Z]/, 'Password must contain at least one uppercase letter')
  .regex(/[a-z]/, 'Password must contain at least one lowercase letter')
  .regex(/[0-9]/, 'Password must contain at least one digit');

export const phoneSchema = z.string()
  .regex(/^(\+84|0)[0-9]{9,10}$/, 'Phone number must be a valid Vietnamese number')
  .max(20, 'Phone number too long');

export const loginSchema = z.object({
  email: emailSchema,
  password: z.string().min(1, 'Password is required'),
});

export const registerSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters').max(100, 'Name too long'),
  email: emailSchema,
  password: passwordSchema,
  confirmPassword: z.string().min(1, 'Please confirm password'),
}).refine((data) => data.password === data.confirmPassword, {
  message: "Passwords don't match",
  path: ['confirmPassword'],
});

export const appointmentSchema = z.object({
  wardCode: z.string().min(1, 'Ward code is required').max(20, 'Ward code too long'),
  citizenName: z.string().min(1, 'Citizen name is required').max(255, 'Name too long'),
  citizenPhone: phoneSchema,
  appointmentDate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Date must be in YYYY-MM-DD format'),
  appointmentTime: z.string().regex(/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/, 'Time must be HH:MM'),
  purpose: z.string().min(1, 'Purpose is required').max(100, 'Purpose too long'),
  citizenEmail: emailSchema.optional().nullable(),
  citizenCCCD: z.string().max(20).optional().nullable(),
  department: z.string().max(255).optional().nullable(),
  durationMinutes: z.number().int().positive().max(180).optional(),
  notes: z.string().max(2000).optional().nullable(),
  description: z.string().max(2000).optional().nullable(),
});

export const orderSchema = z.object({
  productId: z.string().uuid('Invalid product ID'),
  quantity: z.number().int().positive().min(1).max(1000),
  shippingAddress: z.string().optional().nullable(),
});

// Helper function to validate data and return errors
export function validateSchema<T>(schema: z.ZodType<T>, data: unknown): { valid: true; data: T } | { valid: false; errors: z.ZodError } {
  const result = schema.safeParse(data);
  if (result.success) {
    return { valid: true, data: result.data };
  }
  return { valid: false, errors: result.error };
}
