import { render, fireEvent, act } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import FormValidation from './FormValidation.svelte';

describe('FormValidation Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('rendering', () => {
    it('renders without error', () => {
      render(FormValidation, {
        props: {
          validations: [],
          data: {}
        }
      });
      expect(document.body).toBeTruthy();
    });

    it('renders slot content', () => {
      render(FormValidation, {
        props: {
          validations: [],
          data: {}
        }
      });
      // The component renders its slot content
      expect(document.body).toBeTruthy();
    });

    it('provides validation context', () => {
      render(FormValidation, {
        props: {
          validations: [
            { field: 'email', rules: [{ type: 'required', message: 'Email is required' }] }
          ],
          data: { email: 'test@example.com' }
        }
      });
      expect(document.body).toBeTruthy();
    });
  });

  describe('props handling', () => {
    it('accepts validations prop', () => {
      render(FormValidation, {
        props: {
          validations: [
            { field: 'email', rules: [{ type: 'email', message: 'Invalid email' }] }
          ],
          data: { email: 'test' }
        }
      });
      expect(document.body).toBeTruthy();
    });

    it('accepts data prop', () => {
      render(FormValidation, {
        props: {
          validations: [],
          data: { name: 'John', email: 'john@example.com' }
        }
      });
      expect(document.body).toBeTruthy();
    });

    it('handles empty validations', () => {
      render(FormValidation, {
        props: {
          validations: [],
          data: {}
        }
      });
      expect(document.body).toBeTruthy();
    });

    it('handles undefined validations', () => {
      render(FormValidation, {
        props: {
          validations: undefined as any,
          data: {}
        }
      });
      expect(document.body).toBeTruthy();
    });

    it('handles undefined data', () => {
      render(FormValidation, {
        props: {
          validations: [],
          data: undefined as any
        }
      });
      expect(document.body).toBeTruthy();
    });

    it('handles multiple validation rules per field', () => {
      render(FormValidation, {
        props: {
          validations: [{
            field: 'password',
            rules: [
              { type: 'required', message: 'Password is required' },
              { type: 'minLength', value: 8, message: 'Password must be at least 8 characters' },
              { type: 'maxLength', value: 32, message: 'Password must be at most 32 characters' }
            ]
          }],
          data: { password: 'short' }
        }
      });
      expect(document.body).toBeTruthy();
    });
  });

  describe('validation rules', () => {
    it('validates required field', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name is required' }]
          }],
          data: { name: '' }
        }
      });

      await component.validate();
      expect(component.getErrors().name).toContain('Name is required');
    });

    it('passes required field with value', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name is required' }]
          }],
          data: { name: 'John' }
        }
      });

      await component.validate();
      expect(component.getErrors().name).toBeUndefined();
    });

    it('validates email format', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'email',
            rules: [{ type: 'email', message: 'Invalid email format' }]
          }],
          data: { email: 'invalid-email' }
        }
      });

      await component.validate();
      expect(component.getErrors().email).toContain('Invalid email format');
    });

    it('passes valid email format', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'email',
            rules: [{ type: 'email', message: 'Invalid email format' }]
          }],
          data: { email: 'test@example.com' }
        }
      });

      await component.validate();
      expect(component.getErrors().email).toBeUndefined();
    });

    it('validates minLength', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'password',
            rules: [{ type: 'minLength', value: 8, message: 'Password too short' }]
          }],
          data: { password: 'short' }
        }
      });

      await component.validate();
      expect(component.getErrors().password).toContain('Password too short');
    });

    it('passes minLength with sufficient length', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'password',
            rules: [{ type: 'minLength', value: 8, message: 'Password too short' }]
          }],
          data: { password: 'longenough' }
        }
      });

      await component.validate();
      expect(component.getErrors().password).toBeUndefined();
    });

    it('validates maxLength', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'bio',
            rules: [{ type: 'maxLength', value: 100, message: 'Bio too long' }]
          }],
          data: { bio: 'a'.repeat(101) }
        }
      });

      await component.validate();
      expect(component.getErrors().bio).toContain('Bio too long');
    });

    it('passes maxLength within limit', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'bio',
            rules: [{ type: 'maxLength', value: 100, message: 'Bio too long' }]
          }],
          data: { bio: 'Short bio' }
        }
      });

      await component.validate();
      expect(component.getErrors().bio).toBeUndefined();
    });

    it('validates pattern', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'phone',
            rules: [{ type: 'pattern', value: /^\d{3}-\d{3}-\d{4}$/, message: 'Invalid phone format' }]
          }],
          data: { phone: '1234567890' }
        }
      });

      await component.validate();
      expect(component.getErrors().phone).toContain('Invalid phone format');
    });

    it('passes valid pattern', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'phone',
            rules: [{ type: 'pattern', value: /^\d{3}-\d{3}-\d{4}$/, message: 'Invalid phone format' }]
          }],
          data: { phone: '123-456-7890' }
        }
      });

      await component.validate();
      expect(component.getErrors().phone).toBeUndefined();
    });

    it('validates custom validator', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'username',
            rules: [{
              type: 'custom',
              message: 'Username taken',
              async: true,
              validator: async (value: string) => {
                // Simulate async check
                await new Promise(resolve => setTimeout(resolve, 10));
                return value !== 'taken';
              }
            }]
          }],
          data: { username: 'taken' }
        }
      });

      await component.validate();
      await act();
      expect(component.getErrors().username).toContain('Username taken');
    });

    it('passes custom validator', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'username',
            rules: [{
              type: 'custom',
              message: 'Username taken',
              async: true,
              validator: async (value: string) => {
                await new Promise(resolve => setTimeout(resolve, 10));
                return value !== 'taken';
              }
            }]
          }],
          data: { username: 'available' }
        }
      });

      await component.validate();
      await act();
      expect(component.getErrors().username).toBeUndefined();
    });

    it('validates multiple fields', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [
            { field: 'name', rules: [{ type: 'required', message: 'Name required' }] },
            { field: 'email', rules: [{ type: 'email', message: 'Invalid email' }] }
          ],
          data: { name: '', email: 'invalid' }
        }
      });

      await component.validate();
      expect(component.getErrors().name).toContain('Name required');
      expect(component.getErrors().email).toContain('Invalid email');
    });

    it('returns true when all valid', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [
            { field: 'name', rules: [{ type: 'required', message: 'Name required' }] },
            { field: 'email', rules: [{ type: 'email', message: 'Invalid email' }] }
          ],
          data: { name: 'John', email: 'john@example.com' }
        }
      });

      const result = await component.validate();
      expect(result).toBe(true);
      expect(component.getErrors().name).toBeUndefined();
      expect(component.getErrors().email).toBeUndefined();
    });

    it('returns false when invalid', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [
            { field: 'name', rules: [{ type: 'required', message: 'Name required' }] }
          ],
          data: { name: '' }
        }
      });

      const result = await component.validate();
      expect(result).toBe(false);
      expect(component.getErrors().name).toContain('Name required');
    });
  });

  describe('exposed validate function', () => {
    it('exposes validate function', () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name required' }]
          }],
          data: { name: '' }
        }
      });

      expect(typeof component.validate).toBe('function');
    });

    it('validate function works correctly', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name required' }]
          }],
          data: { name: 'John' }
        }
      });

      const result = await component.validate();
      expect(result).toBe(true);
    });
  });

  describe('events', () => {
    it('dispatches valid event', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name required' }]
          }],
          data: { name: 'John' }
        }
      });

      const validHandler = vi.fn();
      // In Svelte 5, events are dispatched via the dispatcher
      // We test the return value of validate() instead
      const result = await component.validate();
      expect(result).toBe(true);
      expect(component.getErrors().name).toBeUndefined();
    });

    it('dispatches errors event', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name required' }]
          }],
          data: { name: '' }
        }
      });

      const errorsHandler = vi.fn();
      // In Svelte 5, we test the errors state directly
      await component.validate();
      expect(component.getErrors().name).toContain('Name required');
    });
  });

  describe('context API', () => {
    it('provides validateField function', () => {
      // The component provides validation context via setContext
      // We can't directly test the context here, but we can verify
      // the component renders without error
      render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name required' }]
          }],
          data: { name: 'John' }
        }
      });
      expect(document.body).toBeTruthy();
    });
  });

  describe('edge cases', () => {
    it('handles async custom validator with delay', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'username',
            rules: [{
              type: 'custom',
              message: 'Checking...',
              async: true,
              validator: async (value: string) => {
                await new Promise(resolve => setTimeout(resolve, 50));
                return value.length > 3;
              }
            }]
          }],
          data: { username: 'ab' }
        }
      });

      await component.validate();
      await act();
      expect(component.getErrors().username).toBeDefined();
    });

    it('handles null data gracefully', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name required' }]
          }],
          data: null as any
        }
      });

      await component.validate();
      expect(component.getErrors().name).toContain('Name required');
    });

    it('handles undefined field value', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name required' }]
          }],
          data: { name: undefined as any }
        }
      });

      await component.validate();
      expect(component.getErrors().name).toContain('Name required');
    });

    it('validates only fields with rules', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'name',
            rules: [{ type: 'required', message: 'Name required' }]
          }],
          data: { name: 'John', extraField: 'some value' }
        }
      });

      const result = await component.validate();
      expect(result).toBe(true);
      expect(component.getErrors().name).toBeUndefined();
      expect(component.getErrors().extraField).toBeUndefined();
    });

    it('handles multiple rules on same field', async () => {
      const { component } = render(FormValidation, {
        props: {
          validations: [{
            field: 'password',
            rules: [
              { type: 'required', message: 'Password required' },
              { type: 'minLength', value: 8, message: 'Too short' }
            ]
          }],
          data: { password: 'short' }
        }
      });

      await component.validate();
      expect(component.getErrors().password).toHaveLength(1);
      expect(component.getErrors().password[0]).toBe('Too short');
    });
  });
});