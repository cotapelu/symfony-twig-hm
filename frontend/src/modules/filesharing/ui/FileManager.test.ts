import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import FileManager from './FileManager.svelte';

describe('FileManager Component', () => {
  it('renders the file manager header', () => {
    render(FileManager);
    expect(screen.getByText(/Chia sẻ file/i)).toBeTruthy();
  });

  it('displays upload area with text and button', () => {
    render(FileManager);
    expect(screen.getByText('Kéo thả file vào đây hoặc')).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Chọn file' })).toBeTruthy();
  });

  it('renders all three files', () => {
    render(FileManager);
    expect(screen.getByText('document.pdf')).toBeTruthy();
    expect(screen.getByText('image.jpg')).toBeTruthy();
    expect(screen.getByText('data.xlsx')).toBeTruthy();
  });

  it('displays file sizes formatted correctly', () => {
    render(FileManager);
    expect(screen.getByText('2.5 MB • 2024-01-15')).toBeTruthy();
    expect(screen.getByText('1.5 MB • 2024-01-14')).toBeTruthy();
    expect(screen.getByText('500.0 KB • 2024-01-13')).toBeTruthy();
  });

  it('shows correct icons for different file types', () => {
    render(FileManager);
    const fileItems = document.querySelectorAll('.file-item');
    const icons = document.querySelectorAll('.file-icon');
    expect(icons).toHaveLength(3);
    expect(icons[0].textContent).toBe('📄'); // pdf
    expect(icons[1].textContent).toBe('🖼️'); // image
    expect(icons[2].textContent).toBe('📊'); // excel
  });

  it('renders action buttons for each file', () => {
    render(FileManager);
    const fileItems = document.querySelectorAll('.file-item');
    fileItems.forEach(item => {
      const buttons = item.querySelectorAll('.file-actions button');
      expect(buttons).toHaveLength(3); // download, share, delete
    });
  });

  it('displays file dates', () => {
    render(FileManager);
    expect(screen.getByText(/2024-01-15/)).toBeTruthy();
    expect(screen.getByText(/2024-01-14/)).toBeTruthy();
  });
});
