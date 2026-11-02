import { describe, it, expect } from 'vitest';
import {
  emailSchema,
  passwordSchema,
  phoneSchema,
  loginSchema,
  registerSchema,
  appointmentSchema,
  orderSchema,
  validateSchema,
} from './validation';

describe('Validation Schemas', () => {
  describe('emailSchema', () => {
    it('accepts valid email', () => {
      const result = validateSchema(emailSchema, 'test@example.com');
      expect(result.valid).toBe(true);
    });

    it('rejects invalid email format', () => {
      const result = validateSchema(emailSchema, 'testexample.com');
      expect(result.valid).toBe(false);
    });

    it('rejects email too short', () => {
      const result = validateSchema(emailSchema, 'a@b'); // 4 chars
      expect(result.valid).toBe(false);
    });

    it('rejects email too long', () => {
      const longEmail = 'a'.repeat(250) + '@example.com';
      const result = validateSchema(emailSchema, longEmail);
      expect(result.valid).toBe(false);
    });
  });

  describe('passwordSchema', () => {
    it('accepts valid password', () => {
      const result = validateSchema(passwordSchema, 'Abcdefg1'); // 8 chars
      expect(result.valid).toBe(true);
    });

    it('rejects password too short', () => {
      const result = validateSchema(passwordSchema, 'Abc1'); // short
      expect(result.valid).toBe(false);
    });

    it('rejects password missing uppercase', () => {
      const result = validateSchema(passwordSchema, 'abcdefg1');
      expect(result.valid).toBe(false);
    });

    it('rejects password missing lowercase', () => {
      const result = validateSchema(passwordSchema, 'ABCDEFG1');
      expect(result.valid).toBe(false);
    });

    it('rejects password missing digit', () => {
      const result = validateSchema(passwordSchema, 'Abcdefgh');
      expect(result.valid).toBe(false);
    });
  });

  describe('phoneSchema', () => {
    it('accepts valid Vietnamese phone numbers', () => {
      const validPhones = ['0912345678', '0987654321', '+84912345678', '01234567890'];
      for (const phone of validPhones) {
        const result = validateSchema(phoneSchema, phone);
        expect(result.valid).toBe(true);
      }
    });

    it('rejects invalid phone format', () => {
      const result = validateSchema(phoneSchema, '123456789');
      expect(result.valid).toBe(false);
    });
  });

  describe('loginSchema', () => {
    it('accepts valid login credentials', () => {
      const data = { email: 'user@example.com', password: 'Abcdefg1' };
      const result = validateSchema(loginSchema, data);
      expect(result.valid).toBe(true);
    });

    it('rejects missing password', () => {
      const result = validateSchema(loginSchema, { email: 'user@example.com', password: '' });
      expect(result.valid).toBe(false);
    });
  });

  describe('registerSchema', () => {
    it('accepts valid registration data', () => {
      const data = {
        name: 'John Doe',
        email: 'john@example.com',
        password: 'Abcdefg1',
        confirmPassword: 'Abcdefg1',
      };
      const result = validateSchema(registerSchema, data);
      expect(result.valid).toBe(true);
    });

    it('rejects mismatched passwords', () => {
      const data = {
        name: 'John',
        email: 'john@example.com',
        password: 'Abcdefg1',
        confirmPassword: 'Abcdefg2',
      };
      const result = validateSchema(registerSchema, data);
      expect(result.valid).toBe(false);
    });

    it('rejects short name', () => {
      const data = {
        name: 'J',
        email: 'john@example.com',
        password: 'Abcdefg1',
        confirmPassword: 'Abcdefg1',
      };
      const result = validateSchema(registerSchema, data);
      expect(result.valid).toBe(false);
    });
  });

  describe('appointmentSchema', () => {
    it('accepts valid appointment data', () => {
      const data = {
        wardCode: 'W001',
        citizenName: 'Nguyen Van A',
        citizenPhone: '0912345678',
        appointmentDate: '2024-12-01',
        appointmentTime: '14:30',
        purpose: 'Checkup',
      };
      const result = validateSchema(appointmentSchema, data);
      expect(result.valid).toBe(true);
    });

    it('rejects invalid date format', () => {
      const data = {
        wardCode: 'W001',
        citizenName: 'A',
        citizenPhone: '0912345678',
        appointmentDate: '01/12/2024',
        appointmentTime: '14:30',
        purpose: 'Checkup',
      };
      const result = validateSchema(appointmentSchema, data);
      expect(result.valid).toBe(false);
    });

    it('rejects invalid time format', () => {
      const data = {
        wardCode: 'W001',
        citizenName: 'A',
        citizenPhone: '0912345678',
        appointmentDate: '2024-12-01',
        appointmentTime: '2:30 PM',
        purpose: 'Checkup',
      };
      const result = validateSchema(appointmentSchema, data);
      expect(result.valid).toBe(false);
    });
  });

  describe('orderSchema', () => {
    it('accepts valid order', () => {
      const data = {
        productId: '123e4567-e89b-12d3-a456-426614174000',
        quantity: 2,
      };
      const result = validateSchema(orderSchema, data);
      expect(result.valid).toBe(true);
    });

    it('rejects invalid UUID productId', () => {
      const result = validateSchema(orderSchema, { productId: 'not-a-uuid', quantity: 1 });
      expect(result.valid).toBe(false);
    });

    it('rejects quantity zero', () => {
      const result = validateSchema(orderSchema, {
        productId: '123e4567-e89b-12d3-a456-426614174000',
        quantity: 0,
      });
      expect(result.valid).toBe(false);
    });

    it('rejects negative quantity', () => {
      const result = validateSchema(orderSchema, {
        productId: '123e4567-e89b-12d3-a456-426614174000',
        quantity: -1,
      });
      expect(result.valid).toBe(false);
    });
  });
});
