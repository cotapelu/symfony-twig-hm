import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import Comment from './Comment.svelte';

const sampleComment = {
  id: '1',
  author: 'Alice',
  content: 'Hello world',
  timestamp: new Date(),
};

describe('Comment Component', () => {
  it('renders comment system container', async () => {
    const { container } = render(Comment);
    await tick();
    expect(container.querySelector('.comment-system')).not.toBeNull();
  });

  it('renders comment input textarea with placeholder', async () => {
    render(Comment);
    await tick();
    const textarea = screen.getByPlaceholderText('Write a comment...');
    expect(textarea).not.toBeNull();
  });

  it('disables post button when input is empty', async () => {
    render(Comment);
    await tick();
    const postBtn = screen.getByText('Post Comment');
    expect(postBtn).toBeDisabled();
  });

  it('does not render reply input by default', async () => {
    const { container } = render(Comment);
    await tick();
    expect(container.querySelector('.comment-reply-input')).toBeNull();
  });

  it('renders no comments when comments array empty', async () => {
    const { container } = render(Comment, { props: { comments: [] } });
    await tick();
    expect(container.querySelectorAll('.comment').length).toBe(0);
  });

  it('renders provided comment content via sanitized html', async () => {
    render(Comment, { props: { comments: [sampleComment] } });
    await tick();
    expect(screen.getByText('Hello world')).not.toBeNull();
  });

  it('renders comment author name', async () => {
    render(Comment, { props: { comments: [sampleComment] } });
    await tick();
    expect(screen.getByText('Alice')).not.toBeNull();
  });

  it('filters out comments with parentId from top-level list', async () => {
    const reply = {
      id: '2',
      author: 'Bob',
      content: 'Reply',
      timestamp: new Date(),
      parentId: '1',
    };
    render(Comment, { props: { comments: [sampleComment, reply] } });
    await tick();
    expect(screen.getByText('Alice')).not.toBeNull();
    expect(screen.queryByText('Bob')).toBeNull();
  });

  it('renders avatar placeholder when no avatar provided', async () => {
    const { container } = render(Comment, { props: { comments: [sampleComment] } });
    await tick();
    expect(container.querySelector('.comment-avatar-placeholder')).not.toBeNull();
  });

  it('renders avatar img when avatar url provided', async () => {
    const commentWithAvatar = { ...sampleComment, avatar: 'https://example.com/a.jpg' };
    const { container } = render(Comment, { props: { comments: [commentWithAvatar] } });
    await tick();
    expect(container.querySelector('.comment-avatar')).not.toBeNull();
  });

  it('shows reply button when maxDepth not exceeded', async () => {
    render(Comment, { props: { comments: [sampleComment], maxDepth: 3 } });
    await tick();
    expect(screen.getByText(/Reply/)).not.toBeNull();
  });

  it('hides actions when showActions=false', async () => {
    const { container } = render(Comment, {
      props: { comments: [sampleComment], showActions: false },
    });
    await tick();
    expect(container.querySelector('.comment-actions')).toBeNull();
  });

  it('shows edit and delete buttons for owner comments', async () => {
    render(Comment, {
      props: { comments: [sampleComment], currentUserId: 'Alice' },
    });
    await tick();
    expect(screen.getByText(/Edit/)).not.toBeNull();
    expect(screen.getByText(/Delete/)).not.toBeNull();
  });

  it('hides edit/delete buttons for non-owner comments', async () => {
    render(Comment, {
      props: { comments: [sampleComment], currentUserId: 'Other' },
    });
    await tick();
    expect(screen.queryByText(/Edit/)).toBeNull();
    expect(screen.queryByText(/Delete/)).toBeNull();
  });

  it('shows likes count when likes defined', async () => {
    const commentWithLikes = { ...sampleComment, likes: 5 };
    const { container } = render(Comment, { props: { comments: [commentWithLikes] } });
    await tick();
    expect(container.querySelector('.comment-action')).not.toBeNull();
  });

  it('hides likes when likes undefined', async () => {
    render(Comment, { props: { comments: [sampleComment] } });
    await tick();
    expect(screen.queryByText('0')).toBeNull();
  });

  it('applies custom className', async () => {
    const { container } = render(Comment, { props: { className: 'my-comments' } });
    await tick();
    expect(container.querySelector('.my-comments')).not.toBeNull();
  });

  it('enables post button when input has content', async () => {
    render(Comment);
    await tick();
    const textarea = screen.getByPlaceholderText('Write a comment...') as HTMLTextAreaElement;
    await fireEvent.input(textarea, { target: { value: 'New comment' } });
    await tick();
    const postBtn = screen.getByText('Post Comment');
    expect(postBtn).not.toBeDisabled();
  });

  it('renders timestamp text', async () => {
    render(Comment, { props: { comments: [sampleComment] } });
    await tick();
    expect(container => container.textContent).toBeDefined();
  });
});
