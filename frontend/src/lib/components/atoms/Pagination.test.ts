import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import Pagination from './Pagination.svelte';

describe('Pagination', () => {
  const renderPagination = (props = {}) => render(Pagination, { props });

  it('renders pagination container', () => {
    const { container } = renderPagination({ total: 100, pageSize: 10 });
    expect(container.querySelector('.pagination')).toBeTruthy();
  });

  it('displays total items text correctly', () => {
    renderPagination({ total: 100, pageSize: 10, current: 2 });
    expect(screen.getByText('11-20 of 100 items')).toBeInTheDocument();
  });

  it('hides total when showTotal is false', () => {
    renderPagination({ total: 100, pageSize: 10, current: 2, showTotal: false });
    expect(screen.queryByText(/items/)).not.toBeInTheDocument();
  });

  it('generates all page numbers when total small', () => {
    const { container } = renderPagination({ total: 5, pageSize: 1 });
    const buttons = container.querySelectorAll('.pagination-page');
    expect(buttons.length).toBe(5);
  });

  it('shows first page as active when current=1', () => {
    const { container } = renderPagination({ total: 10, pageSize: 1, current: 1 });
    const page1 = container.querySelector('.pagination-page') as HTMLElement;
    expect(page1).toHaveAttribute('aria-current', 'page');
    expect(page1).toHaveClass('active');
  });

  it('applies size class to container', () => {
    const { container } = renderPagination({ size: 'sm' });
    expect(container.querySelector('.pagination')).toHaveClass('pagination-sm');
  });

  it('applies simple class when simple is true', () => {
    const { container } = renderPagination({ simple: true });
    expect(container.querySelector('.pagination')).toHaveClass('pagination-simple');
  });

  it('disables prev/next and page buttons when disabled', () => {
    const { container } = renderPagination({ disabled: true, total: 100, pageSize: 10 });
    const pagination = container.querySelector('.pagination');
    expect(pagination).toHaveClass('pagination-disabled');
    expect(container.querySelector('.pagination-prev')).toHaveAttribute('disabled');
    expect(container.querySelector('.pagination-next')).toHaveAttribute('disabled');
    const pages = container.querySelectorAll('.pagination-page');
    pages.forEach((btn) => expect(btn).toHaveAttribute('disabled'));
  });

  it('prev button is disabled on first page', () => {
    const { container } = renderPagination({ current: 1, total: 100, pageSize: 10 });
    expect(container.querySelector('.pagination-prev')).toHaveAttribute('disabled');
  });

  it('next button is disabled on last page', () => {
    const { container } = renderPagination({ current: 10, total: 100, pageSize: 10 });
    expect(container.querySelector('.pagination-next')).toHaveAttribute('disabled');
  });

  it('clicking prev decrements current', async () => {
    const { container } = renderPagination({ current: 3, total: 100, pageSize: 10 });
    const prevBtn = container.querySelector('.pagination-prev')!;
    await fireEvent.click(prevBtn);
    await tick();
    const activeBtn = container.querySelector('.pagination-page[aria-current="page"]');
    expect(activeBtn?.textContent).toBe('2');
  });

  it('clicking next increments current', async () => {
    const { container } = renderPagination({ current: 3, total: 100, pageSize: 10 });
    const nextBtn = container.querySelector('.pagination-next')!;
    await fireEvent.click(nextBtn);
    await tick();
    const activeBtn = container.querySelector('.pagination-page[aria-current="page"]');
    expect(activeBtn?.textContent).toBe('4');
  });

  it('clicking a page button sets current', async () => {
    // Use total=10, current=1 so page 2 exists in list
    const { container } = renderPagination({ total: 10, pageSize: 1, current: 1 });
    const pageButtons = container.querySelectorAll('.pagination-page');
    // Page 2 should be the second button (index 1)
    const page2 = pageButtons[1];
    await fireEvent.click(page2);
    await tick();
    const activeBtn = container.querySelector('.pagination-page[aria-current="page"]');
    expect(activeBtn?.textContent).toBe('2');
  });

  it('showQuickJumper renders input with placeholder', () => {
    const { container } = renderPagination({ total: 100, pageSize: 10, current: 5, showQuickJumper: true });
    const input = container.querySelector('.pagination-jumper-input') as HTMLInputElement;
    expect(input).toBeTruthy();
    expect(input.placeholder).toBe('5');
  });

  it('quick jumper navigates on Enter with valid page', async () => {
    const { container } = renderPagination({ total: 100, pageSize: 10, current: 1, showQuickJumper: true });
    const input = container.querySelector('.pagination-jumper-input') as HTMLInputElement;
    input.value = '5';
    await fireEvent.keyDown(input, { key: 'Enter', code: 'Enter' });
    await tick();
    const activeBtn = container.querySelector('.pagination-page[aria-current="page"]');
    expect(activeBtn?.textContent).toBe('5');
  });

  it('simple mode hides page list and size changer/quick jumper', () => {
    const { container } = renderPagination({
      total: 100,
      pageSize: 10,
      simple: true,
      showSizeChanger: true,
      showQuickJumper: true
    });
    expect(container.querySelector('.pagination-list')).toBeNull();
    expect(container.querySelector('.pagination-size-changer')).toBeNull();
    expect(container.querySelector('.pagination-quick-jumper')).toBeNull();
    expect(container.querySelector('.pagination-simple-text')).toBeTruthy();
  });

  it('simple mode displays current page and total pages', () => {
    renderPagination({ total: 100, pageSize: 10, current: 3, simple: true });
    expect(screen.getByText('3 / 10')).toBeInTheDocument();
  });
});
