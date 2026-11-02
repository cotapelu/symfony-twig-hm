import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import FileCard from './FileCard.svelte';

describe('FileCard', () => {
  const mockFile = {
    id: '1',
    name: 'test.txt',
    type: 'text/plain',
    size: 1024,
    uploaded: '2024-01-15T12:00:00Z'
  };

  const renderFileCard = (props = {}) => render(FileCard, { props });

  it('renders nothing when file is null', () => {
    const { container } = renderFileCard({ file: null });
    expect(container.querySelector('div')).toBeNull();
  });

  it('renders nothing when file is undefined', () => {
    const { container } = renderFileCard({ file: undefined });
    expect(container.querySelector('div')).toBeNull();
  });

  it('displays correct icon for image file', () => {
    const imageFile = { ...mockFile, type: 'image/png' };
    renderFileCard({ file: imageFile });
    expect(screen.getByText('📷')).toBeInTheDocument();
  });

  it('displays correct icon for video file', () => {
    const videoFile = { ...mockFile, type: 'video/mp4' };
    renderFileCard({ file: videoFile });
    expect(screen.getByText('🎥')).toBeInTheDocument();
  });

  it('displays correct icon for audio file', () => {
    const audioFile = { ...mockFile, type: 'audio/mpeg' };
    renderFileCard({ file: audioFile });
    expect(screen.getByText('🎵')).toBeInTheDocument();
  });

  it('displays correct icon for generic file', () => {
    renderFileCard({ file: mockFile });
    expect(screen.getByText('📄')).toBeInTheDocument();
  });

  it('displays file name', () => {
    renderFileCard({ file: mockFile });
    expect(screen.getByText('test.txt')).toBeInTheDocument();
  });

  it('displays file size formatted', () => {
    renderFileCard({ file: mockFile });
    // 1024 bytes -> "1 KB"
    expect(screen.getByText('1 KB')).toBeInTheDocument();
  });

  it('displays file date formatted', () => {
    renderFileCard({ file: mockFile });
    // Assuming en-US locale: "1/15/2024"
    expect(screen.getByText('1/15/2024')).toBeInTheDocument();
  });

  it('download button exists and has aria-label', () => {
    const onDownload = vi.fn();
    renderFileCard({ file: mockFile, onDownload });
    const downloadBtn = screen.getByRole('button', { name: /download test.txt/i });
    expect(downloadBtn).toBeInTheDocument();
    expect(downloadBtn).toHaveTextContent('⬇️');
  });

  it('delete button absent when onDelete not provided', () => {
    renderFileCard({ file: mockFile });
    expect(screen.queryByRole('button', { name: /delete test.txt/i })).not.toBeInTheDocument();
  });

  it('delete button present when onDelete provided', () => {
    const onDelete = vi.fn();
    renderFileCard({ file: mockFile, onDelete });
    const deleteBtn = screen.getByRole('button', { name: /delete test.txt/i });
    expect(deleteBtn).toBeInTheDocument();
    expect(deleteBtn).toHaveTextContent('🗑️');
    expect(deleteBtn).toHaveClass('delete-btn');
  });

  it('clicking download calls onDownload with file', async () => {
    const onDownload = vi.fn();
    renderFileCard({ file: mockFile, onDownload });
    const downloadBtn = screen.getByRole('button', { name: /download test.txt/i });
    await fireEvent.click(downloadBtn);
    await tick();
    expect(onDownload).toHaveBeenCalledTimes(1);
    expect(onDownload).toHaveBeenCalledWith(mockFile);
  });

  it('clicking delete calls onDelete with file', async () => {
    const onDelete = vi.fn();
    renderFileCard({ file: mockFile, onDelete });
    const deleteBtn = screen.getByRole('button', { name: /delete test.txt/i });
    await fireEvent.click(deleteBtn);
    await tick();
    expect(onDelete).toHaveBeenCalledTimes(1);
    expect(onDelete).toHaveBeenCalledWith(mockFile);
  });
});
