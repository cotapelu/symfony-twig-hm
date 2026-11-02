<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Icon from './atoms/Icon.svelte';

  interface Node {
    id: string;
    label: string;
    color?: string;
  }

  interface Link {
    from: string;
    to: string;
    value: number;
    color?: string;
  }

  export let nodes: Node[] = [];
  export let links: Link[] = [];
  export let width: number = 800;
  export let height: number = 600;
  export let nodeWidth: number = 20;
  export let nodePadding: number = 10;
  export let colors: string[] = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
  export let showLabels: boolean = true;
  export let showValues: boolean = true;
  export let interactive: boolean = true;
  export let opacity: number = 0.3;

  const dispatch = createEventDispatcher();

  let hoveredLink: SankeyLink | null = null;
  let hoveredNode: Node | null = null;

  interface SankeyNode extends Node {
    x: number;
    y: number;
  }

  interface SankeyLink extends Link {
    fromNode: SankeyNode;
    toNode: SankeyNode;
    path: string;
  }

  let sankeyNodes: SankeyNode[] = [];
  let sankeyLinks: SankeyLink[] = [];

  function calculateLayout() {
    const layers = calculateLayers();
    sankeyNodes = calculateNodePositions(layers);
    sankeyLinks = calculateLinkPaths(sankeyNodes);
  }

  function calculateLayers(): Node[][] {
    const adjacency = new Map<string, Set<string>>();
    const reverseAdj = new Map<string, Set<string>>();
    const inDegree = new Map<string, number>();

    nodes.forEach(node => {
      adjacency.set(node.id, new Set());
      reverseAdj.set(node.id, new Set());
      inDegree.set(node.id, 0);
    });

    links.forEach(link => {
      adjacency.get(link.from)!.add(link.to);
      reverseAdj.get(link.to)!.add(link.from);
      inDegree.set(link.to, (inDegree.get(link.to) || 0) + 1);
    });

    const layers: Node[][] = [];
    const visited = new Set<string>();
    const queue = nodes.filter(n => inDegree.get(n.id) === 0).map(n => n.id);

    while (queue.length > 0) {
      const layer = queue.map(id => nodes.find(n => n.id === id)!);
      layers.push(layer);
      queue.length = 0;

      layer.forEach(node => {
        visited.add(node.id);
        adjacency.get(node.id)!.forEach(neighbor => {
          reverseAdj.get(neighbor)!.forEach(pred => {
            if (!visited.has(pred)) return;
          });
          inDegree.set(neighbor, (inDegree.get(neighbor) || 0) - 1);
          if (inDegree.get(neighbor) === 0 && !visited.has(neighbor)) {
            queue.push(neighbor);
          }
        });
      });
    }

    return layers;
  }

  function calculateNodePositions(layers: Node[][]): SankeyNode[] {
    const layerHeight = (height - 2 * nodePadding) / Math.max(...layers.map(l => l.length));
    const layerWidth = (width - 2 * nodePadding) / (layers.length - 1 || 1);

    return layers.flatMap((layer, layerIndex) =>
      layer.map((node, nodeIndex) => ({
        ...node,
        x: nodePadding + layerIndex * layerWidth,
        y: nodePadding + nodeIndex * layerHeight
      }))
    );
  }

  function calculateLinkPaths(sankeyNodes: SankeyNode[]): SankeyLink[] {
    return links.map(link => {
      const fromNode = sankeyNodes.find(n => n.id === link.from)!;
      const toNode = sankeyNodes.find(n => n.id === link.to)!;

      const midX = (fromNode.x + toNode.x) / 2;
      const path = `M ${fromNode.x + nodeWidth} ${fromNode.y}
                   C ${midX} ${fromNode.y}, ${midX} ${toNode.y}, ${toNode.x} ${toNode.y}`;

      return {
        ...link,
        fromNode,
        toNode,
        path
      };
    });
  }

  $: calculateLayout();

  function getColor(index: number): string {
    return colors[index % colors.length];
  }

  function handleLinkClick(link: SankeyLink) {
    dispatch('linkClick', { link });
  }

  function handleNodeClick(node: SankeyNode) {
    dispatch('nodeClick', { node });
  }
</script>

<div class="sankey-container" data-testid="sankey-container">
  <svg viewBox="0 0 {width} {height}" class="sankey-svg" role="img">
    {#each sankeyLinks as link, i}
      <path
        d={link.path}
        fill="none"
        stroke={link.color || getColor(i)}
        stroke-width={Math.sqrt(link.value)}
        fill-opacity={opacity}
        class:interactive={interactive}
        class:hovered={hoveredLink === link}
        onmouseenter={() => hoveredLink = link}
        onmouseleave={() => hoveredLink = null}
        onclick={() => interactive && handleLinkClick(link)}
        role="graphics-symbol"
        aria-label="{link.fromNode.label} to {link.toNode.label}: {link.value}"
      />
    {/each}

    {#each sankeyNodes as node, i}
      <rect
        x={node.x}
        y={node.y}
        width={nodeWidth}
        height={nodeWidth}
        fill={node.color || getColor(i)}
        class:interactive={interactive}
        class:hovered={hoveredNode === node}
        onmouseenter={() => hoveredNode = node}
        onmouseleave={() => hoveredNode = null}
        onclick={() => interactive && handleNodeClick(node)}
        role="graphics-symbol"
        aria-label={node.label}
      />

      {#if showLabels}
        <text
          x={node.x + nodeWidth / 2}
          y={node.y - 8}
          text-anchor="middle"
          class="sankey-label"
          font-size="12"
          fill="#1f2937"
        >
          {node.label}
        </text>
      {/if}
    {/each}
  </svg>

  {#if interactive && (hoveredLink || hoveredNode)}
    <div class="sankey-tooltip">
      <div class="tooltip-content">
        {#if hoveredLink}
          <strong>{hoveredLink.fromNode.label} → {hoveredLink.toNode.label}</strong>
          {#if showValues}
            <div>Value: {hoveredLink.value}</div>
          {/if}
        {:else if hoveredNode}
          <strong>{hoveredNode.label}</strong>
        {/if}
      </div>
    </div>
  {/if}
</div>

<style>
  :global(:root) {
    --sankey-tooltip-bg: rgba(0, 0, 0, 0.9);
    --sankey-tooltip-text: #ffffff;
  }

  .sankey-container {
    position: relative;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
  }

  .sankey-svg {
    width: 100%;
    height: 100%;
  }

  .sankey-svg path {
    transition: stroke-width 0.15s ease, opacity 0.15s ease;
  }

  .sankey-svg path.interactive {
    cursor: pointer;
  }

  .sankey-svg path.interactive:hover,
  .sankey-svg path.hovered {
    stroke-width: 8;
    opacity: 0.6;
  }

  .sankey-svg rect {
    transition: transform 0.15s ease;
  }

  .sankey-svg rect.interactive {
    cursor: pointer;
  }

  .sankey-svg rect.interactive:hover,
  .sankey-svg rect.hovered {
    transform: scale(1.2);
  }

  .sankey-label {
    font-weight: 600;
  }

  .sankey-tooltip {
    position: absolute;
    pointer-events: none;
    z-index: 1000;
    padding: 8px 12px;
    background: var(--sankey-tooltip-bg);
    color: var(--sankey-tooltip-text);
    border-radius: 6px;
    font-size: 0.875rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  }

  .tooltip-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
</style>
