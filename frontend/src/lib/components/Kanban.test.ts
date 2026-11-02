import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import Kanban from './Kanban.svelte';

const sampleColumns = [
  {
    id: 'todo',
    title: 'To Do',
    cards: [
      { title: 'Task 1', description: 'First task' },
      { title: 'Task 2', description: 'Second task' },
    ],
  },
  {
    id: 'inprogress',
    title: 'In Progress',
    cards: [{ title: 'Task 3', description: 'Active task' }],
  },
  {
    id: 'done',
    title: 'Done',
    cards: [],
  },
];

describe('Kanban Component', () => {
  it('renders wrapper with kanban-board class', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    expect(container.querySelector('.kanban-board')).not.toBeNull();
  });

  it('renders one kanban-column per column entry', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    expect(container.querySelectorAll('.kanban-column').length).toBe(3);
  });

  it('renders empty board when no columns', async () => {
    const { container } = render(Kanban, { props: { columns: [] } });
    await tick();
    expect(container.querySelector('.kanban-board')).not.toBeNull();
    expect(container.querySelector('.kanban-column')).toBeNull();
  });

  it('renders column-title per column', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const titles = container.querySelectorAll('.column-title');
    expect(titles.length).toBe(3);
    expect(titles[0]?.textContent?.trim()).toBe('To Do');
    expect(titles[1]?.textContent?.trim()).toBe('In Progress');
    expect(titles[2]?.textContent?.trim()).toBe('Done');
  });

  it('renders column-cards listbox per column', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    expect(container.querySelectorAll('.column-cards').length).toBe(3);
  });

  it('column-cards has role=listbox', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const listbox = container.querySelector('.column-cards');
    expect(listbox?.getAttribute('role')).toBe('listbox');
  });

  it('renders kanban-card per card in each column', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const cards = container.querySelectorAll('.kanban-card');
    // 2 + 1 + 0 = 3 cards
    expect(cards.length).toBe(3);
  });

  it('kanban-card has role=option and aria-selected=false', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const card = container.querySelector('.kanban-card');
    expect(card?.getAttribute('role')).toBe('option');
    expect(card?.getAttribute('aria-selected')).toBe('false');
    expect(card?.getAttribute('tabindex')).toBe('0');
  });

  it('kanban-card has draggable=true', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const card = container.querySelector('.kanban-card');
    expect(card?.getAttribute('draggable')).toBe('true');
  });

  it('renders card-content with title and description by default', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const cardContent = container.querySelector('.card-content');
    expect(cardContent).not.toBeNull();
    expect(cardContent?.querySelector('h4')?.textContent).toBe('Task 1');
    expect(cardContent?.querySelector('p')?.textContent).toBe('First task');
  });

  it('renders empty column correctly (0 cards in Done)', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const doneColumn = container.querySelectorAll('.kanban-column')[2];
    const cardsInDone = doneColumn?.querySelectorAll('.kanban-card');
    expect(cardsInDone?.length).toBe(0);
  });

  it('uses cardTemplate when provided (sanitized by default)', async () => {
    const tpl = (c: any) => `<span class="custom-card">${c.title}-TPL</span>`;
    const { container } = render(Kanban, {
      props: { columns: sampleColumns, cardTemplate: tpl },
    });
    await tick();
    const customCards = container.querySelectorAll('.custom-card');
    expect(customCards.length).toBe(3);
    expect(customCards[0]?.textContent).toBe('Task 1-TPL');
  });

  it('uses cardTemplate with allowHtml=true (raw HTML)', async () => {
    const tpl = (c: any) => `<span class="raw-card">${c.title}-RAW</span>`;
    const { container } = render(Kanban, {
      props: { columns: sampleColumns, cardTemplate: tpl, allowHtml: true },
    });
    await tick();
    const rawCards = container.querySelectorAll('.raw-card');
    expect(rawCards.length).toBe(3);
    expect(rawCards[0]?.textContent).toBe('Task 1-RAW');
  });

  it('kanban-column has role=list and tabindex=0', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const column = container.querySelector('.kanban-column');
    expect(column?.getAttribute('role')).toBe('list');
    expect(column?.getAttribute('tabindex')).toBe('0');
  });

  it('does not render card-content when using cardTemplate', async () => {
    const tpl = (c: any) => `<span>${c.title}</span>`;
    const { container } = render(Kanban, {
      props: { columns: sampleColumns, cardTemplate: tpl },
    });
    await tick();
    expect(container.querySelector('.card-content')).toBeNull();
  });

  it('clicking kanban-card does not throw (no-throw safety)', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const card = container.querySelector('.kanban-card');
    let error: any = null;
    try {
      if (card) await fireEvent.click(card);
    } catch (e) {
      error = e;
    }
    expect(error).toBeNull();
  });

  it('keydown Enter on kanban-card does not throw (no-throw safety)', async () => {
    const { container } = render(Kanban, { props: { columns: sampleColumns } });
    await tick();
    const card = container.querySelector('.kanban-card');
    let error: any = null;
    try {
      if (card) await fireEvent.keyDown(card, { key: 'Enter' });
    } catch (e) {
      error = e;
    }
    expect(error).toBeNull();
  });
});
