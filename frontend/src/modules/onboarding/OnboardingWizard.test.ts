import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import OnboardingWizard from './OnboardingWizard.svelte';

describe('OnboardingWizard Component', () => {
  describe('rendering', () => {
    it('renders first step (profile) by default', () => {
      render(OnboardingWizard);
      expect(screen.getByText('Your Profile')).toBeTruthy();
      expect(screen.getByLabelText('Name')).toBeTruthy();
      expect(screen.getByLabelText('Company')).toBeTruthy();
    });

    it('renders continue button on first step', () => {
      render(OnboardingWizard);
      expect(screen.getByRole('button', { name: 'Continue' })).toBeTruthy();
    });

    it('does not render back button on first step', () => {
      render(OnboardingWizard);
      expect(screen.queryByRole('button', { name: 'Back' })).toBeNull();
    });

    it('renders progress indicators', () => {
      render(OnboardingWizard);
      const progress = document.querySelector('.progress');
      expect(progress).toBeTruthy();
      const steps = progress.querySelectorAll('span');
      expect(steps.length).toBe(4);
    });
  });

  describe('navigation', () => {
    it('advances to next step on Continue click', async () => {
      render(OnboardingWizard);
      const continueBtn = screen.getByRole('button', { name: 'Continue' });
      await continueBtn.click();
      expect(screen.getByText('Interests')).toBeTruthy();
    });

    it('shows Back button after first step', async () => {
      render(OnboardingWizard);
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      expect(screen.getByRole('button', { name: 'Back' })).toBeTruthy();
    });

    it('goes back to previous step on Back click', async () => {
      render(OnboardingWizard);
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      await fireEvent.click(screen.getByRole('button', { name: 'Back' }));
      expect(screen.getByText('Your Profile')).toBeTruthy();
    });

    it('navigates through all steps correctly', async () => {
      render(OnboardingWizard);

      // Step 1 -> 2
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      expect(screen.getByText('Interests')).toBeTruthy();

      // Step 2 -> 3
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      expect(screen.getByText('Notifications')).toBeTruthy();

      // Step 3 -> 4
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      expect(screen.getByText('All Set!')).toBeTruthy();
    });

    it('shows only Continue on last step (finish)', async () => {
      render(OnboardingWizard);
      // Navigate to last step
      for (let i = 0; i < 3; i++) {
        await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      }
      expect(screen.getByRole('button', { name: 'Continue' })).toBeTruthy();
      expect(screen.queryByRole('button', { name: 'Back' })).toBeNull();
    });
  });

  describe('profile step', () => {
    it('captures name input', async () => {
      render(OnboardingWizard);
      const nameInput = screen.getByLabelText('Name') as HTMLInputElement;
      const companyInput = screen.getByLabelText('Company') as HTMLInputElement;

      await fireEvent.input(nameInput, { target: { value: 'John Doe' } });
      await fireEvent.input(companyInput, { target: { value: 'Acme Corp' } });

      expect(nameInput.value).toBe('John Doe');
      expect(companyInput.value).toBe('Acme Corp');
    });
  });

  describe('interests step', () => {
    it('renders interest checkboxes', async () => {
      render(OnboardingWizard);
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));

      expect(screen.getByText('Technology')).toBeTruthy();
      expect(screen.getByText('Business')).toBeTruthy();
      expect(screen.getByText('Finance')).toBeTruthy();
    });

    it('toggles interests correctly', async () => {
      render(OnboardingWizard);
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));

      const techCheckbox = screen.getByLabelText('Technology') as HTMLInputElement;
      const businessCheckbox = screen.getByLabelText('Business') as HTMLInputElement;

      // Initially unchecked
      expect(techCheckbox.checked).toBe(false);
      expect(businessCheckbox.checked).toBe(false);

      // Check
      await fireEvent.click(techCheckbox);
      expect(techCheckbox.checked).toBe(true);

      // Uncheck
      await fireEvent.click(techCheckbox);
      expect(techCheckbox.checked).toBe(false);
    });
  });

  describe('notifications step', () => {
    it('renders notification toggles', async () => {
      render(OnboardingWizard);
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));

      expect(screen.getByText('Email updates')).toBeTruthy();
      expect(screen.getByText('Push notifications')).toBeTruthy();
    });

    it('toggles notification preferences', async () => {
      render(OnboardingWizard);
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));

      const emailCheckbox = screen.getByLabelText('Email updates') as HTMLInputElement;
      const pushCheckbox = screen.getByLabelText('Push notifications') as HTMLInputElement;

      // Initially checked (default true)
      expect(emailCheckbox.checked).toBe(true);
      expect(pushCheckbox.checked).toBe(true);

      // Toggle off
      await fireEvent.click(emailCheckbox);
      expect(emailCheckbox.checked).toBe(false);
    });
  });

  describe('finish step', () => {
    it('displays completion message', async () => {
      render(OnboardingWizard);
      // Navigate to finish
      for (let i = 0; i < 3; i++) {
        await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      }
      expect(screen.getByText('All Set!')).toBeTruthy();
      expect(screen.getByText(/Thank you for completing the setup/)).toBeTruthy();
    });

    it('dispatches complete event on Continue in finish step', async () => {
      const onComplete = vi.fn();
      render(OnboardingWizard, { props: { onComplete } });

      // Navigate to finish
      for (let i = 0; i < 3; i++) {
        await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      }

      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));

      expect(onComplete).toHaveBeenCalledTimes(1);
    });
  });

  describe('progress indicators', () => {
    it('highlights current step', async () => {
      render(OnboardingWizard);

      // Initially step 1 active
      const steps = document.querySelectorAll('.progress span');
      expect(steps[0].classList.contains('active')).toBe(true);
      expect(steps[1].classList.contains('active')).toBe(false);

      // Move to step 2
      await fireEvent.click(screen.getByRole('button', { name: 'Continue' }));
      const stepsAfter = document.querySelectorAll('.progress span');
      expect(stepsAfter[0].classList.contains('active')).toBe(false);
      expect(stepsAfter[1].classList.contains('active')).toBe(true);
    });
  });
});
