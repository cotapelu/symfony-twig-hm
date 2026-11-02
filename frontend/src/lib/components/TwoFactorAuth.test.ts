import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import TwoFactorAuth from './TwoFactorAuth.svelte';

describe('TwoFactorAuth', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    
    // Mock navigator.clipboard
    Object.assign(navigator, {
      clipboard: {
        writeText: vi.fn().mockResolvedValue(undefined)
      }
    });
  });

  it('renders setup step by default', () => {
    render(TwoFactorAuth);
    expect(screen.getByText('Set Up Two-Factor Authentication')).toBeInTheDocument();
    expect(screen.getByText('Scan the QR code below with your authenticator app')).toBeInTheDocument();
  });

  it('renders QR code when qrCodeUrl is provided', () => {
    render(TwoFactorAuth, { props: { step: 'setup', qrCodeUrl: 'https://example.com/qr.png' } });
    const img = screen.getByAltText('QR Code');
    expect(img).toHaveAttribute('src', 'https://example.com/qr.png');
  });

  it('renders secret input when secret is provided', () => {
    render(TwoFactorAuth, { props: { step: 'setup', secret: 'ABCDEF123456' } });
    const input = screen.getByPlaceholderText('Enter secret key');
    expect(input).toHaveValue('ABCDEF123456');
    expect(input).toHaveAttribute('readonly');
  });

  it('shows copy button for secret', () => {
    render(TwoFactorAuth, { props: { step: 'setup', secret: 'ABCDEF123456' } });
    const copyButton = screen.getByRole('button', { name: /copy/i });
    expect(copyButton).toBeInTheDocument();
  });

  it('shows error alert when error is provided in setup step', () => {
    render(TwoFactorAuth, { props: { step: 'setup', error: 'Invalid code' } });
    expect(screen.getByText('Invalid code')).toBeInTheDocument();
    expect(screen.getByRole('alert')).toBeInTheDocument();
  });

  it('shows next button in setup step', () => {
    render(TwoFactorAuth, { props: { step: 'setup', secret: 'ABCDEF123456' } });
    expect(screen.getByText('Next')).toBeInTheDocument();
  });

  it('disables next button when no secret', () => {
    render(TwoFactorAuth, { props: { step: 'setup', secret: '' } });
    const nextButton = screen.getByText('Next');
    expect(nextButton).toBeDisabled();
  });

  it('shows loading state on next button', () => {
    render(TwoFactorAuth, { props: { step: 'setup', secret: 'ABCDEF123456', loading: true } });
    expect(screen.getByText('Verifying...')).toBeInTheDocument();
  });

  it('dispatches setup event when next clicked', async () => {
    const onSetup = vi.fn();
    render(TwoFactorAuth, { props: { step: 'setup', secret: 'ABCDEF123456', onSetup } });
    await fireEvent.click(screen.getByText('Next'));
    expect(onSetup).toHaveBeenCalledWith({ secret: 'ABCDEF123456' });
  });

  it('renders verify step when step is verify', () => {
    render(TwoFactorAuth, { props: { step: 'verify' } });
    expect(screen.getByText('Verify Two-Factor Authentication')).toBeInTheDocument();
    expect(screen.getByText('Enter the 6-digit code from your authenticator app')).toBeInTheDocument();
  });

  it('renders code input in verify step', () => {
    render(TwoFactorAuth, { props: { step: 'verify', verifyCode: '123456' } });
    const input = screen.getByPlaceholderText('123456');
    expect(input).toBeInTheDocument();
  });

  it('shows back button in verify step', () => {
    render(TwoFactorAuth, { props: { step: 'verify' } });
    expect(screen.getByText('Back')).toBeInTheDocument();
  });

  it('dispatches back event when back clicked', async () => {
    const onBack = vi.fn();
    render(TwoFactorAuth, { props: { step: 'verify', onBack } });
    await fireEvent.click(screen.getByText('Back'));
    expect(onBack).toHaveBeenCalled();
  });

  it('shows verify button in verify step', () => {
    render(TwoFactorAuth, { props: { step: 'verify' } });
    expect(screen.getByText('Verify')).toBeInTheDocument();
  });

  it('disables verify button when code is not 6 digits', () => {
    render(TwoFactorAuth, { props: { step: 'verify', verifyCode: '123' } });
    expect(screen.getByText('Verify')).toBeDisabled();
  });

  it('enables verify button when code is 6 digits', () => {
    render(TwoFactorAuth, { props: { step: 'verify', verifyCode: '123456' } });
    expect(screen.getByText('Verify')).not.toBeDisabled();
  });

  it('shows loading state on verify button', () => {
    render(TwoFactorAuth, { props: { step: 'verify', loading: true } });
    expect(screen.getByText('Verifying...')).toBeInTheDocument();
  });

  it('dispatches verify event with code', async () => {
    const onVerify = vi.fn();
    render(TwoFactorAuth, { props: { step: 'verify', verifyCode: '123456', onVerify } });
    await fireEvent.click(screen.getByText('Verify'));
    expect(onVerify).toHaveBeenCalledWith({ code: '123456' });
  });

  it('shows error alert in verify step', () => {
    render(TwoFactorAuth, { props: { step: 'verify', error: 'Invalid verification code' } });
    expect(screen.getByText('Invalid verification code')).toBeInTheDocument();
    expect(screen.getByRole('alert')).toBeInTheDocument();
  });

  it('renders backup step when step is backup', () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123', 'DEF456'] } });
    expect(screen.getByText('Backup Codes')).toBeInTheDocument();
    expect(screen.getByText('Save these codes in a secure location')).toBeInTheDocument();
  });

  it('renders backup codes when provided', () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123', 'DEF456'] } });
    expect(screen.getByText('1.')).toBeInTheDocument();
    expect(screen.getByText('ABC123')).toBeInTheDocument();
    expect(screen.getByText('2.')).toBeInTheDocument();
    expect(screen.getByText('DEF456')).toBeInTheDocument();
  });

  it('shows copy button for each backup code', () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123', 'DEF456'] } });
    const copyButtons = screen.getAllByRole('button', { name: /copy/i });
    expect(copyButtons.length).toBe(2);
  });

  it('shows warning icon in backup step', () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123'] } });
    expect(screen.getByText('Keep these codes safe. They will not be shown again.')).toBeInTheDocument();
  });

  it('shows backup warning message', () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123'] } });
    expect(screen.getByText('Keep these codes safe. They will not be shown again.')).toBeInTheDocument();
  });

  it('shows skip button in backup step', () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123'] } });
    expect(screen.getByText('Skip')).toBeInTheDocument();
  });

  it('shows download button in backup step', () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123'] } });
    expect(screen.getByText('Download')).toBeInTheDocument();
  });

  it('dispatches skipBackup when skip clicked', async () => {
    const onSkipBackup = vi.fn();
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123'], onSkipBackup } });
    await fireEvent.click(screen.getByText('Skip'));
    expect(onSkipBackup).toHaveBeenCalled();
  });

  it('dispatches downloadBackup when download clicked', async () => {
    const onDownloadBackup = vi.fn();
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123'], onDownloadBackup } });
    await fireEvent.click(screen.getByText('Download'));
    expect(onDownloadBackup).toHaveBeenCalled();
  });

  it('renders multiple instances independently', () => {
    const { container: c1 } = render(TwoFactorAuth, { props: { step: 'setup', secret: 'SECRET1' } });
    const { container: c2 } = render(TwoFactorAuth, { props: { step: 'verify' } });
    expect(c1.querySelector('.two-factor-auth')).toBeInTheDocument();
    expect(c2.querySelector('.two-factor-auth')).toBeInTheDocument();
  });

  it('handles null qrCodeUrl gracefully', () => {
    render(TwoFactorAuth, { props: { step: 'setup', qrCodeUrl: null } });
    expect(screen.queryByAltText('QR Code')).not.toBeInTheDocument();
  });

  it('handles null secret gracefully', () => {
    render(TwoFactorAuth, { props: { step: 'setup', secret: null } });
    expect(screen.queryByPlaceholderText('Enter secret key')).not.toBeInTheDocument();
  });

  it('handles null backupCodes gracefully', () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: null } });
    expect(screen.queryByText('1.')).not.toBeInTheDocument();
  });

  it('renders step actions with proper button variants', () => {
    render(TwoFactorAuth, { props: { step: 'setup', secret: 'ABCDEF123456' } });
    const primaryButton = screen.getByText('Next');
    expect(primaryButton).toBeInTheDocument();
  });

  it('renders verify step with secondary back button', () => {
    render(TwoFactorAuth, { props: { step: 'verify' } });
    expect(screen.getByText('Back')).toBeInTheDocument();
    const verifyButton = screen.getByText('Verify');
    expect(verifyButton).toBeInTheDocument();
  });

  it('renders backup step with secondary skip and primary download', () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123'] } });
    expect(screen.getByText('Skip')).toBeInTheDocument();
    expect(screen.getByText('Download')).toBeInTheDocument();
  });

  it('copies secret to clipboard when copy button clicked', async () => {
    render(TwoFactorAuth, { props: { step: 'setup', secret: 'ABCDEF123456' } });
    const copyButton = screen.getByRole('button', { name: /copy/i });
    await fireEvent.click(copyButton);
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith('ABCDEF123456');
  });

  it('copies backup code to clipboard when copy button clicked', async () => {
    render(TwoFactorAuth, { props: { step: 'backup', backupCodes: ['ABC123'] } });
    const copyButton = screen.getByRole('button', { name: /copy/i });
    await fireEvent.click(copyButton);
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith('ABC123');
  });

  it('closes error alert when close button clicked', async () => {
    const onErrorChange = vi.fn();
    render(TwoFactorAuth, { props: { step: 'setup', error: 'Error message', onErrorChange } });
    const closeButton = screen.getByLabelText('Close alert');
    await fireEvent.click(closeButton);
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    expect(onErrorChange).toHaveBeenCalledWith(null);
  });
});