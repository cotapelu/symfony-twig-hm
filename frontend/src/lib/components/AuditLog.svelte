<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Icon from './atoms/Icon.svelte';
  import Typography from './atoms/Typography.svelte';
  import Badge from './atoms/Badge.svelte';

  export let logs: Array<{
    id: string;
    action: string;
    entity: string;
    details: string;
    userId: string;
    userName: string;
    timestamp: Date;
    ipAddress?: string;
    userAgent?: string;
    severity?: 'info' | 'warning' | 'error' | 'critical';
  }> = [];

  export let loading: boolean = false;
  export let filter: { action?: string; entity?: string; severity?: string; userId?: string } = {};
  export let showDetails: boolean = true;
  export let showMetadata: boolean = false;
  export let exportable: boolean = true;
  export let searchable: boolean = true;

  const dispatch = createEventDispatcher();

  let searchQuery: string = '';
  let selectedLogId: string | null = null;
  let currentPage: number = 1;
  let itemsPerPage: number = 10;

  $: filteredLogs = logs.filter(log => {
    const matchesSearch = !searchQuery || 
      log.action.toLowerCase().includes(searchQuery.toLowerCase()) ||
      log.entity.toLowerCase().includes(searchQuery.toLowerCase()) ||
      log.details.toLowerCase().includes(searchQuery.toLowerCase()) ||
      log.userName.toLowerCase().includes(searchQuery.toLowerCase());

    const matchesFilter = (!filter.action || log.action === filter.action) &&
      (!filter.entity || log.entity === filter.entity) &&
      (!filter.severity || log.severity === filter.severity) &&
      (!filter.userId || log.userId === filter.userId);

    return matchesSearch && matchesFilter;
  });

  $: totalPages = Math.ceil(filteredLogs.length / itemsPerPage);
  $: paginatedLogs = filteredLogs.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  function handleSearch(query: string) {
    searchQuery = query;
    currentPage = 1;
  }

  function handleFilter(filter: { [key: string]: string | undefined }) {
    dispatch('filter', filter);
    currentPage = 1;
  }

  function handleExport() {
    dispatch('export', { logs: filteredLogs });
  }

  function handleLogClick(log: typeof logs[0]) {
    selectedLogId = selectedLogId === log.id ? null : log.id;
    dispatch('logClick', { log });
  }

  function getSeverityColor(severity?: string): 'default' | 'outlined' | 'filled' {
    switch (severity) {
      case 'error':
      case 'critical':
        return 'filled';
      case 'warning':
        return 'default';
      default:
        return 'outlined';
    }
  }

  function formatTimestamp(timestamp: Date): string {
    return new Intl.DateTimeFormat('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    }).format(timestamp);
  }

  function getSeverityIcon(severity?: string): string {
    switch (severity) {
      case 'critical':
        return 'alert-circle';
      case 'error':
        return 'x-circle';
      case 'warning':
        return 'alert-triangle';
      default:
        return 'info';
    }
  }
</script>

<div class="audit-log">
  <div class="audit-log-header">
    <Typography variant="h3">Audit Log</Typography>

    <div class="audit-log-controls">
      {#if searchable}
        <div class="search-input">
          <Icon name="search" size="sm" />
          <input
            type="text"
            placeholder="Search logs..."
            bind:value={searchQuery}
            oninput={(e: Event) => {
              const target = e.target as HTMLInputElement;
              handleSearch(target.value);
            }}
          />
        </div>
      {/if}

      {#if exportable}
        <button
          class="export-button"
          onclick={handleExport}
          disabled={filteredLogs.length === 0}
        >
          <Icon name="download" size="sm" />
          Export
        </button>
      {/if}
    </div>
  </div>

  {#if loading}
    <div class="audit-log-loading">
      <Icon name="loader" size="lg" spin />
      <Typography variant="body">Loading logs...</Typography>
    </div>
  {:else if paginatedLogs.length === 0}
    <div class="audit-log-empty">
      <Icon name="file-text" size="xl" />
      <Typography variant="body">No audit logs found</Typography>
    </div>
  {:else}
    <div class="audit-log-list">
      {#each paginatedLogs as log}
        <div
          class="audit-log-item"
          class:expanded={selectedLogId === log.id}
          onclick={() => handleLogClick(log)}
          onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { handleLogClick(log); e.preventDefault(); } }}
          role="button"
          tabindex="0"
        >
          <div class="audit-log-summary">
            <div class="audit-log-main">
              <div class="audit-log-icon">
                <Icon
                  name={getSeverityIcon(log.severity)}
                  color={getSeverityColor(log.severity)}
                />
              </div>

                <div class="audit-log-content">
                <div class="audit-log-title">
                  <Typography variant="body">{log.action}</Typography>
                  {#if log.severity}
                    <Badge variant={getSeverityColor(log.severity)} size="sm">
                      {log.severity}
                    </Badge>
                  {/if}
                </div>

                <Typography variant="body" className="text-muted">
                  {log.entity} by {log.userName}
                </Typography>
              </div>
            </div>

            <div class="audit-log-meta">
              <Typography variant="body" className="text-muted">
                {formatTimestamp(log.timestamp)}
              </Typography>
              <Icon name="chevron-down" />
            </div>
          </div>

          {#if showDetails && selectedLogId === log.id}
            <div class="audit-log-details">
              <div class="detail-row">
                <Typography variant="caption">Details</Typography>
                <Typography variant="body">{log.details}</Typography>
              </div>

              {#if showMetadata}
                <div class="detail-row">
                  <Typography variant="caption">User ID</Typography>
                  <Typography variant="body">{log.userId}</Typography>
                </div>

                {#if log.ipAddress}
                  <div class="detail-row">
                    <Typography variant="caption">IP Address</Typography>
                    <Typography variant="body">{log.ipAddress}</Typography>
                  </div>
                {/if}

                {#if log.userAgent}
                  <div class="detail-row">
                    <Typography variant="caption">User Agent</Typography>
                    <Typography variant="body">{log.userAgent}</Typography>
                  </div>
                {/if}
              {/if}
            </div>
          {/if}
        </div>
      {/each}
    </div>

    {#if totalPages > 1}
      <div class="audit-log-pagination">
        <button
          class="pagination-button"
          disabled={currentPage === 1}
          onclick={() => currentPage--}
        >
          <Icon name="chevron-left" />
        </button>

        <span class="pagination-info">
          Page {currentPage} of {totalPages}
        </span>

        <button
          class="pagination-button"
          disabled={currentPage === totalPages}
          onclick={() => currentPage++}
        >
          <Icon name="chevron-right" />
        </button>
      </div>
    {/if}
  {/if}
</div>

<style>
  :global(:root) {
    --audit-log-bg: #ffffff;
    --audit-log-border: #e5e7eb;
    --audit-log-hover: #f9fafb;
    --audit-log-text: #1f2937;
    --audit-log-muted: #6b7280;
  }

  .audit-log {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    border: 1px solid var(--audit-log-border);
    border-radius: 8px;
    overflow: hidden;
  }

  .audit-log-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--audit-log-border);
  }

  .audit-log-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
  }

  .search-input {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid var(--audit-log-border);
    border-radius: 6px;
  }

  .search-input input {
    border: none;
    outline: none;
    font-size: 0.875rem;
  }

  .export-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border: 1px solid var(--audit-log-border);
    border-radius: 6px;
    color: var(--audit-log-text);
    cursor: pointer;
    transition: background 0.15s ease;
  }

  .export-button:hover:not(:disabled) {
    background: #e5e7eb;
  }

  .export-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .audit-log-loading,
  .audit-log-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 3rem;
    color: var(--audit-log-muted);
  }

  .audit-log-list {
    display: flex;
    flex-direction: column;
  }

  .audit-log-item {
    border-bottom: 1px solid var(--audit-log-border);
    cursor: pointer;
    transition: background 0.15s ease;
  }

  .audit-log-item:hover {
    background: var(--audit-log-hover);
  }

  .audit-log-item:last-child {
    border-bottom: none;
  }

  .audit-log-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
  }

  .audit-log-main {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    flex: 1;
  }

  .audit-log-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
  }

  .audit-log-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .audit-log-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .audit-log-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--audit-log-muted);
  }

  .audit-log-details {
    padding: 0 1.5rem 1rem;
    border-top: 1px solid var(--audit-log-border);
    background: var(--audit-log-hover);
  }

  .detail-row {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-bottom: 1rem;
  }

  .detail-row:last-child {
    margin-bottom: 0;
  }

  .audit-log-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1rem;
    border-top: 1px solid var(--audit-log-border);
  }

  .pagination-button {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    background: transparent;
    border: 1px solid var(--audit-log-border);
    border-radius: 6px;
    color: var(--audit-log-text);
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .pagination-button:hover:not(:disabled) {
    background: var(--audit-log-hover);
  }

  .pagination-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-info {
    font-size: 0.875rem;
    color: var(--audit-log-muted);
  }
</style>
