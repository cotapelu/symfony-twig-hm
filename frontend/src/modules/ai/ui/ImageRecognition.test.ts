import { describe, it, expect, afterEach, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, cleanup, act } from '@testing-library/svelte';
import ImageRecognition from './ImageRecognition.svelte';

describe('ImageRecognition', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });
  afterEach(() => {
    vi.useRealTimers();
    cleanup();
  });

  describe('rendering', () => {
    it('renders the heading', () => {
      render(ImageRecognition);
      expect(screen.getByText(/Nhận diện hình ảnh/)).toBeInTheDocument();
    });

    it('renders an image file input', () => {
      render(ImageRecognition);
      const input = document.querySelector('input[type="file"]') as HTMLInputElement;
      expect(input).toBeInTheDocument();
      expect(input).toHaveAttribute('accept', 'image/*');
    });

    it('hides the analyze button until a file is selected', () => {
      render(ImageRecognition);
      expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('does not render results initially', () => {
      render(ImageRecognition);
      expect(screen.queryByText(/Kết quả/)).not.toBeInTheDocument();
    });
  });

  describe('analyze flow', () => {
    const selectFile = () => {
      const input = document.querySelector('input[type="file"]') as HTMLInputElement;
      const file = new File(['x'], 'photo.png', { type: 'image/png' });
      Object.defineProperty(input, 'files', { value: [file], configurable: true });
      fireEvent.change(input);
    };

    it('shows the analyze button after selecting a file', () => {
      render(ImageRecognition);
      selectFile();
      expect(screen.getByRole('button', { name: 'Phân tích' })).toBeInTheDocument();
    });

    it('shows loading state then results after the timer elapses', async () => {
      render(ImageRecognition);
      selectFile();
      await fireEvent.click(screen.getByRole('button', { name: 'Phân tích' }));
      // loading state, button disabled
      expect(screen.getByRole('button', { name: 'Đang phân tích...' })).toBeDisabled();
      await act(async () => { vi.advanceTimersByTime(1500); });
      expect(screen.getByText(/Kết quả/)).toBeInTheDocument();
      expect(screen.getByText(/person, car/)).toBeInTheDocument();
      expect(screen.getByText(/Sample text from image/)).toBeInTheDocument();
    });

    it('button is disabled during analysis (prevents double submit)', async () => {
      render(ImageRecognition);
      selectFile();
      await fireEvent.click(screen.getByRole('button', { name: 'Phân tích' }));
      expect(screen.getByRole('button', { name: 'Đang phân tích...' })).toBeDisabled();
    });

    it('does not analyze when no file is selected (guard)', () => {
      render(ImageRecognition);
      // No button exists without a file, so analyze cannot be triggered.
      expect(screen.queryByRole('button')).not.toBeInTheDocument();
      expect(screen.queryByText(/Kết quả/)).not.toBeInTheDocument();
    });
  });
});
