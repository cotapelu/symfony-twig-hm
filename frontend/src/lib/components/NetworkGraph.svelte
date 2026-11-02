<script lang="ts">
  import { createEventDispatcher, onMount } from 'svelte';
  import Icon from './atoms/Icon.svelte';

  export let nodes: Array<{ id: string; label: string; color?: string; size?: number }> = [];
  export let edges: Array<{ from: string; to: string; weight?: number }> = [];
  export let width: number = 800;
  export let height: number = 600;
  export let nodeColor: string = '#3b82f6';
  export let edgeColor: string = '#94a3b8';
  export let labelColor: string = '#1f2937';
  export let showLabels: boolean = true;
  export let interactive: boolean = true;
  export let draggable: boolean = true;

  const dispatch = createEventDispatcher();

  let canvas: HTMLCanvasElement;
  let ctx: CanvasRenderingContext2D | null = null;
  let selectedNode: string | null = null;
  let hoveredNode: string | null = null;
  let draggingNode: string | null = null;
  let dragOffset: { x: number; y: number } | null = null;

  let nodePositions: Map<string, { x: number; y: number; vx: number; vy: number }> = new Map();

  onMount(() => {
    if (!canvas) return;
    ctx = canvas.getContext('2d');
    if (!ctx) return;

    initializePositions();
    animate();
  });

  function initializePositions() {
    nodePositions.clear();
    nodes.forEach(node => {
      nodePositions.set(node.id, {
        x: width / 2 + (Math.random() - 0.5) * 200,
        y: height / 2 + (Math.random() - 0.5) * 200,
        vx: 0,
        vy: 0
      });
    });
  }

  function animate() {
    if (!ctx || !canvas) return;

    // Force-directed layout
    simulateForces();

    // Clear and draw
    ctx.clearRect(0, 0, width, height);
    drawEdges();
    drawNodes();

    requestAnimationFrame(animate);
  }

  function simulateForces() {
    const k = 1000;
    const repulsion = 10000;

    nodePositions.forEach((pos, nodeId) => {
      let fx = 0, fy = 0;

      nodePositions.forEach((otherPos, otherId) => {
        if (nodeId === otherId) return;

        const dx = pos.x - otherPos.x;
        const dy = pos.y - otherPos.y;
        const dist = Math.sqrt(dx * dx + dy * dy) || 1;

        if (dist < 300) {
          const force = repulsion / (dist * dist);
          fx += (dx / dist) * force;
          fy += (dy / dist) * force;
        }
      });

      nodePositions.set(nodeId, {
        ...pos,
        vx: (pos.vx + fx) * 0.1,
        vy: (pos.vy + fy) * 0.1,
        x: Math.max(50, Math.min(width - 50, pos.x + pos.vx * 0.1)),
        y: Math.max(50, Math.min(height - 50, pos.y + pos.vy * 0.1))
      });
    });

    edges.forEach(edge => {
      const from = nodePositions.get(edge.from);
      const to = nodePositions.get(edge.to);
      if (!from || !to) return;

      const dx = to.x - from.x;
      const dy = to.y - from.y;
      const dist = Math.sqrt(dx * dx + dy * dy) || 1;
      const force = (dist - 100) * 0.01;

      const fx = (dx / dist) * force;
      const fy = (dy / dist) * force;

      nodePositions.set(edge.from, {
        ...from,
        vx: from.vx + fx,
        vy: from.vy + fy
      });

      nodePositions.set(edge.to, {
        ...to,
        vx: to.vx - fx,
        vy: to.vy - fy
      });
    });
  }

  function drawEdges() {
    if (!ctx) return;
    const drawingCtx = ctx;

    edges.forEach(edge => {
      const from = nodePositions.get(edge.from);
      const to = nodePositions.get(edge.to);
      if (!from || !to) return;

      drawingCtx.beginPath();
      drawingCtx.moveTo(from.x, from.y);
      drawingCtx.lineTo(to.x, to.y);
      drawingCtx.strokeStyle = edgeColor;
      drawingCtx.lineWidth = edge.weight || 1;
      drawingCtx.stroke();
    });
  }

  function drawNodes() {
    if (!ctx) return;
    const drawingCtx = ctx;

    nodes.forEach(node => {
      const pos = nodePositions.get(node.id);
      if (!pos) return;

      const size = node.size || 20;
      const isSelected = selectedNode === node.id;
      const isHovered = hoveredNode === node.id;

      drawingCtx.beginPath();
      drawingCtx.arc(pos.x, pos.y, size + (isSelected || isHovered ? 4 : 0), 0, 2 * Math.PI);
      drawingCtx.fillStyle = node.color || nodeColor;
      drawingCtx.fill();

      if (isSelected) {
        drawingCtx.strokeStyle = '#1f2937';
        drawingCtx.lineWidth = 3;
        drawingCtx.stroke();
      }

      if (isHovered && interactive) {
        drawingCtx.strokeStyle = '#60a5fa';
        drawingCtx.lineWidth = 2;
        drawingCtx.stroke();
      }

      if (showLabels) {
        drawingCtx.fillStyle = labelColor;
        drawingCtx.font = '12px sans-serif';
        drawingCtx.textAlign = 'center';
        drawingCtx.fillText(node.label, pos.x, pos.y + size + 15);
      }
    });
  }

  function getMousePos(e: MouseEvent): { x: number; y: number } {
    const rect = canvas.getBoundingClientRect();
    return {
      x: e.clientX - rect.left,
      y: e.clientY - rect.top
    };
  }

  function findNodeAt(pos: { x: number; y: number }): string | null {
    for (const [nodeId, nodePos] of nodePositions) {
      const node = nodes.find(n => n.id === nodeId);
      if (!node) continue;

      const size = node?.size || 20;
      const dx = pos.x - nodePos.x;
      const dy = pos.y - nodePos.y;
      const dist = Math.sqrt(dx * dx + dy * dy);

      if (dist <= size) {
        return nodeId;
      }
    }
    return null;
  }

  function handleMouseDown(e: MouseEvent) {
    if (!interactive) return;

    const pos = getMousePos(e);
    const nodeId = findNodeAt(pos);

    if (nodeId) {
      selectedNode = nodeId;
      if (draggable) {
        draggingNode = nodeId;
        dragOffset = {
          x: pos.x - nodePositions.get(nodeId)!.x,
          y: pos.y - nodePositions.get(nodeId)!.y
        };
      }
      dispatch('nodeClick', { nodeId });
    }
  }

  function handleMouseMove(e: MouseEvent) {
    if (!interactive) return;

    const pos = getMousePos(e);

    if (draggingNode && dragOffset) {
      const newPos = nodePositions.get(draggingNode)!;
      nodePositions.set(draggingNode, {
        ...newPos,
        x: pos.x - dragOffset.x,
        y: pos.y - dragOffset.y,
        vx: 0,
        vy: 0
      });
      dispatch('nodeDrag', { nodeId: draggingNode, x: newPos.x, y: newPos.y });
    } else {
      hoveredNode = findNodeAt(pos);
    }
  }

  function handleMouseUp() {
    draggingNode = null;
    dragOffset = null;
  }

  function handleMouseLeave() {
    draggingNode = null;
    dragOffset = null;
    hoveredNode = null;
  }
</script>

<div class="network-graph">
  <canvas
    bind:this={canvas}
    width={width}
    height={height}
    data-testid="network-graph-canvas"
    onmousedown={handleMouseDown}
    onmousemove={handleMouseMove}
    onmouseup={handleMouseUp}
    onmouseleave={handleMouseLeave}
  >
</div>

<style>
  :global(:root) {
    --network-graph-border: #e5e7eb;
  }

  .network-graph {
    border: 1px solid var(--network-graph-border);
    border-radius: 8px;
    overflow: hidden;
  }

  canvas {
    display: block;
    cursor: default;
  }

  canvas:active {
    cursor: grabbing;
  }
</style>
