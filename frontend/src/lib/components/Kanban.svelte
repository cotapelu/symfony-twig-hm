<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import DOMPurify from 'dompurify';

  interface KanbanCard {
    title: string;
    description?: string;
    [key: string]: any;
  }

  interface KanbanColumn {
    id: string;
    title: string;
    cards: KanbanCard[];
  }

  export let columns: KanbanColumn[] = [];
  export let cardTemplate: ((card: KanbanCard) => string) | null = null;
  export let allowHtml = false;

  let draggedCard: KanbanCard | null = null;
  let draggedColumn: KanbanColumn | null = null;

  const dispatch = createEventDispatcher();

  function handleDragStart(card: KanbanCard, column: KanbanColumn): void {
    draggedCard = card;
    draggedColumn = column;
    dispatch('dragstart', { card, column });
  }

  function handleDrop(targetColumn: KanbanColumn): void {
    if (draggedCard && draggedColumn && targetColumn !== draggedColumn) {
      dispatch('move', {
        card: draggedCard,
        fromColumn: draggedColumn,
        toColumn: targetColumn
      });
    }
    draggedCard = null;
    draggedColumn = null;
  }

  function handleDragOver(event: DragEvent): void {
    event.preventDefault();
  }
</script>

<div class="kanban-board">
  {#each columns as column}
    <div
      class="kanban-column"
      role="list"
      tabindex="0"
      ondrop={() => handleDrop(column)}
      ondragover={handleDragOver}
      onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { handleDrop(column); e.preventDefault(); } }}
    >
      <h3 class="column-title">{column.title}</h3>
      <div class="column-cards" role="listbox">
        {#each column.cards as card}
          <div
            class="kanban-card"
            draggable="true"
            role="option"
            aria-selected="false"
            tabindex="0"
            ondragstart={() => handleDragStart(card, column)}
            onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { handleDragStart(card, column); e.preventDefault(); } }}
          >
            {#if cardTemplate}
              {#if allowHtml === true}
                {@html cardTemplate(card)}
              {:else}
                {@html DOMPurify.sanitize(cardTemplate(card))}
              {/if}
            {:else}
              <div class="card-content">
                <h4>{card.title}</h4>
                <p>{card.description}</p>
              </div>
            {/if}
          </div>
        {/each}
      </div>
    </div>
  {/each}
</div>

<style>
  .kanban-board {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding: 1rem;
    background-color: var(--bg-secondary, #f9fafb);
    border-radius: 8px;
  }

  .kanban-column {
    min-width: 300px;
    background-color: var(--bg-primary, #ffffff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 8px;
    padding: 1rem;
  }

  .column-title {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary, #374151);
  }

  .column-cards {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .kanban-card {
    background-color: var(--bg-primary, #ffffff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 6px;
    padding: 1rem;
    cursor: move;
    transition: all 0.2s ease;
  }

  .kanban-card:hover {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  /* svelte-ignore css_unused_selector */
  .kanban-card.dragging {
    opacity: 0.5;
  }

  .card-content h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 500;
  }

  .card-content p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
  }
</style>