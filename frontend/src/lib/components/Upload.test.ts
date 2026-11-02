import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import Upload from './Upload.svelte';

const sampleFiles = [
  { uid: 1, name: 'doc.pdf', size: 1024, type: 'application/pdf', status: 'done', percent: 100, url: 'https://example.com/doc.pdf' },
  { uid: 2, name: 'photo.png', size: 2048, type: 'image/png', status: 'uploading', percent: 50 },
];

describe('Upload Component', () => {
  it('renders wrapper with upload class', async () => {
    const { container } = render(Upload);
    await tick();
    expect(container.querySelector('.upload')).not.toBeNull();
  });

  it('applies custom className to wrapper', async () => {
    const { container } = render(Upload, { props: { className: 'my-upload' } });
    await tick();
    expect(container.querySelector('.my-upload')).not.toBeNull();
  });

  it('applies upload-disabled class when disabled=true', async () => {
    const { container } = render(Upload, { props: { disabled: true } });
    await tick();
    expect(container.querySelector('.upload-disabled')).not.toBeNull();
  });

  it('applies upload-text class by default (listType=text)', async () => {
    const { container } = render(Upload);
    await tick();
    expect(container.querySelector('.upload-text')).not.toBeNull();
  });

  it('applies upload-picture class when listType=picture', async () => {
    const { container } = render(Upload, { props: { listType: 'picture' } });
    await tick();
    expect(container.querySelector('.upload-picture')).not.toBeNull();
  });

  it('applies upload-picture-card class when listType=picture-card', async () => {
    const { container } = render(Upload, { props: { listType: 'picture-card' } });
    await tick();
    expect(container.querySelector('.upload-picture-card')).not.toBeNull();
  });

  it('renders drag container', async () => {
    const { container } = render(Upload);
    await tick();
    expect(container.querySelector('.upload-drag-container')).not.toBeNull();
  });

  it('renders hidden file input', async () => {
    const { container } = render(Upload);
    await tick();
    const input = container.querySelector('input[type="file"]');
    expect(input).not.toBeNull();
    expect(input?.getAttribute('style')).toContain('display: none');
  });

  it('renders Choose File button by default', async () => {
    render(Upload);
    await tick();
    expect(screen.getByText('Choose File')).not.toBeNull();
  });

  it('renders Choose Files button when multiple=true', async () => {
    render(Upload, { props: { multiple: true } });
    await tick();
    expect(screen.getByText('Choose Files')).not.toBeNull();
  });

  it('file input accepts attribute is set from accept prop', async () => {
    const { container } = render(Upload, { props: { accept: 'image/*' } });
    await tick();
    expect(container.querySelector('input[type="file"]')?.getAttribute('accept')).toBe('image/*');
  });

  it('file input multiple attribute is set when multiple=true', async () => {
    const { container } = render(Upload, { props: { multiple: true } });
    await tick();
    expect(container.querySelector('input[type="file"]')?.hasAttribute('multiple')).toBe(true);
  });

  it('does not show upload list when showUploadList=false', async () => {
    const { container } = render(Upload, {
      props: { fileList: sampleFiles, showUploadList: false },
    });
    await tick();
    expect(container.querySelector('.upload-list')).toBeNull();
  });

  it('shows upload list when showUploadList=true', async () => {
    const { container } = render(Upload, {
      props: { fileList: sampleFiles, showUploadList: true },
    });
    await tick();
    expect(container.querySelector('.upload-list')).not.toBeNull();
  });

  it('renders file names in upload list', async () => {
    render(Upload, { props: { fileList: sampleFiles } });
    await tick();
    expect(screen.getByText('doc.pdf')).not.toBeNull();
    expect(screen.getByText('photo.png')).not.toBeNull();
  });

  it('applies upload-list-item-done class for completed files', async () => {
    const { container } = render(Upload, { props: { fileList: sampleFiles } });
    await tick();
    expect(container.querySelector('.upload-list-item-done')).not.toBeNull();
  });

  it('applies upload-list-item-uploading class for in-progress files', async () => {
    const { container } = render(Upload, { props: { fileList: sampleFiles } });
    await tick();
    expect(container.querySelector('.upload-list-item-uploading')).not.toBeNull();
  });

  it('renders progress bar for uploading files', async () => {
    const { container } = render(Upload, { props: { fileList: sampleFiles } });
    await tick();
    expect(container.querySelector('.upload-progress-bar')).not.toBeNull();
  });

  it('renders error message for failed files', async () => {
    const errorFiles = [
      { uid: 1, name: 'fail.pdf', status: 'error', percent: 0 },
    ];
    render(Upload, { props: { fileList: errorFiles } });
    await tick();
    expect(screen.getByText('Upload failed')).not.toBeNull();
  });

  it('applies upload-list-item-error class for failed files', async () => {
    const errorFiles = [
      { uid: 1, name: 'fail.pdf', status: 'error', percent: 0 },
    ];
    const { container } = render(Upload, { props: { fileList: errorFiles } });
    await tick();
    expect(container.querySelector('.upload-list-item-error')).not.toBeNull();
  });

  it('renders View link for completed files', async () => {
    const { container } = render(Upload, { props: { fileList: sampleFiles } });
    await tick();
    const viewLinks = container.querySelectorAll('.upload-list-item-actions a');
    expect(viewLinks.length).toBe(1);
  });

  it('renders Remove button for each file', async () => {
    render(Upload, { props: { fileList: sampleFiles } });
    await tick();
    const removeButtons = screen.getAllByText('Remove');
    expect(removeButtons.length).toBe(2);
  });

  it('renders picture-card wrapper when listType=picture-card', async () => {
    const { container } = render(Upload, {
      props: { listType: 'picture-card', fileList: sampleFiles },
    });
    await tick();
    expect(container.querySelector('.upload-picture-card-wrapper')).not.toBeNull();
  });

  it('renders picture-card wrapper containing files + add tile', async () => {
    const { container } = render(Upload, {
      props: { listType: 'picture-card', fileList: sampleFiles },
    });
    await tick();
    const wrapper = container.querySelector('.upload-picture-card-wrapper');
    expect(wrapper).not.toBeNull();
    // .upload-picture-card appears in: outer per-file (x2), inner per-file (.upload-picture-card-image, .upload-picture-card-uploading) (x2), and the Add tile (x1)
    const cards = container.querySelectorAll('[class*="upload-picture-card"]');
    expect(cards.length).toBeGreaterThanOrEqual(2);
  });

  it('renders picture-card Add tile when canUpload=true', async () => {
    const { container } = render(Upload, {
      props: { listType: 'picture-card', fileList: [] },
    });
    await tick();
    expect(container.querySelector('.upload-picture-card-add')).not.toBeNull();
  });

  it('renders done image for completed files in picture-card mode', async () => {
    const { container } = render(Upload, {
      props: {
        listType: 'picture-card',
        fileList: [{ uid: 1, name: 'pic.png', status: 'done', url: 'https://example.com/pic.png' }],
      },
    });
    await tick();
    expect(container.querySelector('.upload-picture-card-image')).not.toBeNull();
  });

  it('renders error overlay for failed files in picture-card mode', async () => {
    const { container } = render(Upload, {
      props: {
        listType: 'picture-card',
        fileList: [{ uid: 1, name: 'bad.png', status: 'error' }],
      },
    });
    await tick();
    expect(container.querySelector('.upload-picture-card-error')).not.toBeNull();
  });

  it('renders uploading overlay for in-progress files in picture-card mode', async () => {
    const { container } = render(Upload, {
      props: {
        listType: 'picture-card',
        fileList: [{ uid: 1, name: 'pending.png', status: 'uploading', percent: 30 }],
      },
    });
    await tick();
    expect(container.querySelector('.upload-picture-card-uploading')).not.toBeNull();
  });

  it('renders picture select tile in picture mode', async () => {
    const { container } = render(Upload, { props: { listType: 'picture' } });
    await tick();
    expect(container.querySelector('.upload-picture-select')).not.toBeNull();
  });

  it('disables Choose File button when disabled=true', async () => {
    render(Upload, { props: { disabled: true } });
    await tick();
    const btn = screen.getByText('Choose File').closest('button');
    expect(btn?.hasAttribute('disabled')).toBe(true);
  });
});
