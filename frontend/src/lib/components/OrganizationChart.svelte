<script lang="ts">
  import DOMPurify from 'dompurify';

  interface OrgNode {
    name: string;
    title?: string;
    children?: OrgNode[];
    [key: string]: any;
  }

  export let data: OrgNode | null = null;
  export let orientation: 'vertical' | 'horizontal' = 'vertical';
  export let nodeTemplate: ((node: OrgNode) => string) | null = null;
  export let allowHtml = false;

  function renderNode(node: OrgNode, level = 0): string {
    return `
      <div class="org-node" style="--level: ${level}">
        <div class="node-content">
          ${nodeTemplate ? nodeTemplate(node) : `
            <div class="node-avatar">${node.name.charAt(0)}</div>
            <div class="node-info">
              <div class="node-name">${node.name}</div>
              ${node.title ? `<div class="node-title">${node.title}</div>` : ''}
            </div>
          `}
        </div>
        ${node.children && node.children.length ? `
          <div class="node-children">
            ${node.children.map((child: OrgNode) => renderNode(child, level + 1)).join('')}
          </div>
        ` : ''}
      </div>
    `;
  }
</script>

<div class="organization-chart" class:horizontal={orientation === 'horizontal'}>
  {#if data}
    {#if allowHtml === true}
      {@html renderNode(data)}
    {:else}
      {@html DOMPurify.sanitize(renderNode(data))}
    {/if}
  {:else}
    <div class="chart-placeholder">
      <p>No organization data provided</p>
    </div>
  {/if}
</div>

<style>
  .organization-chart {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem;
    background-color: var(--bg-primary, #ffffff);
    border-radius: 8px;
  }

  .organization-chart.horizontal {
    flex-direction: row;
    overflow-x: auto;
  }

  /* svelte-ignore css_unused_selector */
  .org-node {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 1rem;
  }

  /* svelte-ignore css_unused_selector */
  .organization-chart.horizontal .org-node {
    flex-direction: row;
    margin: 0 1rem;
  }

  /* svelte-ignore css_unused_selector */
  .node-content {
    background-color: var(--primary-500, #3b82f6);
    color: white;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    min-width: 120px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .node-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--primary-600, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 auto 0.5rem;
  }

  .node-name {
    font-weight: 600;
    font-size: 0.875rem;
  }

  .node-title {
    font-size: 0.75rem;
    opacity: 0.9;
  }

  .node-children {
    display: flex;
    margin-top: 2rem;
    position: relative;
  }

  .organization-chart.horizontal .node-children {
    flex-direction: column;
    margin-top: 0;
    margin-left: 2rem;
  }

  .node-children::before {
    content: '';
    position: absolute;
    top: -1rem;
    left: 50%;
    width: 2px;
    height: 1rem;
    background-color: var(--border-color, #e5e7eb);
    transform: translateX(-50%);
  }

  .organization-chart.horizontal .node-children::before {
    top: 50%;
    left: -1rem;
    width: 1rem;
    height: 2px;
    transform: translateY(-50%);
  }

  .chart-placeholder {
    color: var(--text-secondary, #6b7280);
    text-align: center;
  }
</style>