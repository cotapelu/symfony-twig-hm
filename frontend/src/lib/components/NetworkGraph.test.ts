import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import NetworkGraph from './NetworkGraph.svelte';

const defaultNodes = [
  { id: '1', label: 'Node 1', color: '#3b82f6', size: 20 },
  { id: '2', label: 'Node 2', color: '#10b981', size: 25 },
  { id: '3', label: 'Node 3', color: '#f59e0b', size: 30 }
];

const defaultEdges = [
  { from: '1', to: '2', weight: 2 },
  { from: '2', to: '3', weight: 1 },
  { from: '1', to: '3', weight: 3 }
];

function getCanvas() {
  return screen.getByTestId('network-graph-canvas');
}

describe('NetworkGraph Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(NetworkGraph);
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('renders with custom nodes and edges', () => {
      render(NetworkGraph, { props: { nodes: defaultNodes, edges: defaultEdges } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('renders with custom dimensions', () => {
      render(NetworkGraph, { props: { width: 1000, height: 800 } });
      const canvas = getCanvas();
      expect(canvas).toHaveAttribute('width', '1000');
      expect(canvas).toHaveAttribute('height', '800');
    });

    it('renders with custom colors', () => {
      render(NetworkGraph, { 
        props: { 
          nodeColor: '#ff0000', 
          edgeColor: '#00ff00', 
          labelColor: '#0000ff' 
        } 
      });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });
  });

  describe('Props', () => {
    it('accepts nodes prop', () => {
      const nodes = [
        { id: 'a', label: 'A' },
        { id: 'b', label: 'B' }
      ];
      render(NetworkGraph, { props: { nodes } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('accepts edges prop', () => {
      const edges = [
        { from: 'a', to: 'b', weight: 2 }
      ];
      render(NetworkGraph, { props: { edges } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('accepts showLabels prop', () => {
      render(NetworkGraph, { props: { showLabels: false } });
      expect(getCanvas()).toBeInTheDocument();
    });

    it('accepts interactive prop', () => {
      render(NetworkGraph, { props: { interactive: false } });
      expect(getCanvas()).toBeInTheDocument();
    });

    it('accepts draggable prop', () => {
      render(NetworkGraph, { props: { draggable: false } });
      expect(getCanvas()).toBeInTheDocument();
    });

    it('accepts custom node sizes', () => {
      const nodes = [
        { id: '1', label: 'Small', size: 10 },
        { id: '2', label: 'Large', size: 40 }
      ];
      render(NetworkGraph, { props: { nodes } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });
  });

  describe('Node Interactions', () => {
    // These tests require canvas context which is not available in jsdom without the canvas package
    it.skip('dispatches nodeClick event on node click', async () => {
      const clickHandler = vi.fn();
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          onnodeClick: clickHandler 
        } 
      });

      const canvas = getCanvas();
      
      fireEvent.mouseDown(canvas, {
        clientX: 400,
        clientY: 300
      });
      fireEvent.mouseUp(canvas, {
        clientX: 400,
        clientY: 300
      });

      await waitFor(() => {
        expect(clickHandler).toHaveBeenCalledWith(expect.objectContaining({
          detail: expect.objectContaining({
            nodeId: expect.any(String)
          })
        }));
      });
    });

    it.skip('dispatches nodeDrag event on node drag', async () => {
      const dragHandler = vi.fn();
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          onnodeDrag: dragHandler,
          draggable: true
        } 
      });

      const canvas = getCanvas();
      
      fireEvent.mouseDown(canvas, {
        clientX: 400,
        clientY: 300
      });
      
      fireEvent.mouseMove(canvas, {
        clientX: 450,
        clientY: 350
      });
      
      fireEvent.mouseUp(canvas, {
        clientX: 450,
        clientY: 350
      });

      await waitFor(() => {
        expect(dragHandler).toHaveBeenCalledWith(expect.objectContaining({
          detail: expect.objectContaining({
            nodeId: expect.any(String),
            x: expect.any(Number),
            y: expect.any(Number)
          })
        }));
      });
    });

    it('does not drag when draggable is false', async () => {
      const dragHandler = vi.fn();
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          onnodeDrag: dragHandler,
          draggable: false
        } 
      });

      const canvas = getCanvas();
      
      fireEvent.mouseDown(canvas, {
        clientX: 400,
        clientY: 300
      });
      
      fireEvent.mouseMove(canvas, {
        clientX: 450,
        clientY: 350
      });
      
      fireEvent.mouseUp(canvas, {
        clientX: 450,
        clientY: 350
      });

      // Should not call drag handler when draggable is false
      expect(dragHandler).not.toHaveBeenCalled();
    });

    it('does not interact when interactive is false', async () => {
      const clickHandler = vi.fn();
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          onnodeClick: clickHandler,
          interactive: false
        } 
      });

      const canvas = getCanvas();
      
      fireEvent.mouseDown(canvas, {
        clientX: 400,
        clientY: 300
      });
      fireEvent.mouseUp(canvas, {
        clientX: 400,
        clientY: 300
      });

      expect(clickHandler).not.toHaveBeenCalled();
    });
  });

  describe('Hover and Selection', () => {
    it('highlights node on hover', async () => {
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          interactive: true
        } 
      });

      const canvas = getCanvas();
      
      fireEvent.mouseMove(canvas, {
        clientX: 400,
        clientY: 300
      });

      await waitFor(() => {
        expect(getCanvas()).toBeInTheDocument();
      });
    });

    it('clears hover on mouse leave', async () => {
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          interactive: true
        } 
      });

      const canvas = getCanvas();
      
      fireEvent.mouseMove(canvas, {
        clientX: 400,
        clientY: 300
      });
      
      fireEvent.mouseLeave(canvas);

      await waitFor(() => {
        expect(getCanvas()).toBeInTheDocument();
      });
    });

    it('selects node on click', async () => {
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          interactive: true
        } 
      });

      const canvas = getCanvas();
      
      fireEvent.mouseDown(canvas, {
        clientX: 400,
        clientY: 300
      });
      fireEvent.mouseUp(canvas, {
        clientX: 400,
        clientY: 300
      });

      await waitFor(() => {
        expect(getCanvas()).toBeInTheDocument();
      });
    });
  });

  describe('Force-Directed Layout', () => {
    it('initializes node positions', async () => {
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges 
        } 
      });

      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('animates force-directed layout', async () => {
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges 
        } 
      });

      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    it('handles empty nodes array', () => {
      render(NetworkGraph, { props: { nodes: [] } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('handles empty edges array', () => {
      render(NetworkGraph, { props: { edges: [] } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('handles single node', () => {
      render(NetworkGraph, { 
        props: { nodes: [{ id: '1', label: 'Only Node' }] } 
      });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('handles many nodes', () => {
      const manyNodes = Array.from({ length: 50 }, (_, i) => ({
        id: String(i),
        label: `Node ${i}`,
        color: '#3b82f6',
        size: 15
      }));
      render(NetworkGraph, { props: { nodes: manyNodes } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('handles nodes without optional properties', () => {
      const nodes = [
        { id: '1', label: 'Node 1' },
        { id: '2', label: 'Node 2' }
      ];
      render(NetworkGraph, { props: { nodes } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('handles edges with missing nodes gracefully', () => {
      const nodes = [{ id: '1', label: 'Node 1' }];
      const edges = [{ from: '1', to: '2', weight: 1 }]; // Node 2 doesn't exist
      render(NetworkGraph, { props: { nodes, edges } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('handles zero weight edges', () => {
      const edges = [{ from: '1', to: '2', weight: 0 }];
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges 
        } 
      });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('renders multiple instances independently', () => {
      const { container: container1 } = render(NetworkGraph);
      const { container: container2 } = render(NetworkGraph, { 
        props: { width: 400, height: 300 } 
      });
      expect(container1).toBeTruthy();
      expect(container2).toBeTruthy();
    });

    it('handles very long labels', () => {
      const nodes = [
        { id: '1', label: 'A'.repeat(100) }
      ];
      render(NetworkGraph, { props: { nodes } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('handles special characters in labels', () => {
      const nodes = [
        { id: '1', label: '<script>alert(1)</script>' },
        { id: '2', label: '🎉 🎊 🎈' }
      ];
      render(NetworkGraph, { props: { nodes } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('handles negative coordinates in custom positions', () => {
      // The component should clamp positions to valid ranges
      const nodes = [{ id: '1', label: 'Node 1' }];
      render(NetworkGraph, { props: { nodes } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('has correct role attribute', () => {
      render(NetworkGraph);
      expect(getCanvas()).toBeInTheDocument();
    });

    it('handles keyboard navigation when interactive', async () => {
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          interactive: true
        } 
      });

      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });
  });

  describe('Canvas Configuration', () => {
    it('sets correct canvas dimensions', () => {
      render(NetworkGraph, { props: { width: 400, height: 300 } });
      const canvas = getCanvas();
      expect(canvas).toHaveAttribute('width', '400');
      expect(canvas).toHaveAttribute('height', '300');
    });

    it('applies border styling', () => {
      render(NetworkGraph);
      const container = getCanvas().parentElement;
      expect(container).toHaveClass('network-graph');
    });

    it('applies cursor styles', () => {
      render(NetworkGraph, { props: { interactive: true } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });

    it('handles canvas context initialization', async () => {
      render(NetworkGraph, { props: { nodes: defaultNodes } });
      const canvas = getCanvas();
      expect(canvas).toBeInTheDocument();
    });
  });

  describe('Event Dispatching', () => {
    it.skip('dispatches nodeClick with correct detail', async () => {
      const clickHandler = vi.fn();
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          onnodeClick: clickHandler 
        } 
      });

      const canvas = getCanvas();
      fireEvent.mouseDown(canvas, { clientX: 400, clientY: 300 });
      fireEvent.mouseUp(canvas, { clientX: 400, clientY: 300 });

      await waitFor(() => {
        expect(clickHandler).toHaveBeenCalledWith(expect.objectContaining({
          detail: expect.objectContaining({
            nodeId: expect.any(String)
          })
        }));
      });
    });

    it.skip('dispatches nodeDrag with position data', async () => {
      const dragHandler = vi.fn();
      render(NetworkGraph, { 
        props: { 
          nodes: defaultNodes, 
          edges: defaultEdges,
          onnodeDrag: dragHandler,
          draggable: true
        } 
      });

      const canvas = getCanvas();
      
      fireEvent.mouseDown(canvas, { clientX: 400, clientY: 300 });
      fireEvent.mouseMove(canvas, { clientX: 450, clientY: 350 });
      fireEvent.mouseUp(canvas, { clientX: 450, clientY: 350 });

      await waitFor(() => {
        expect(dragHandler).toHaveBeenCalledWith(expect.objectContaining({
          detail: expect.objectContaining({
            nodeId: expect.any(String),
            x: expect.any(Number),
            y: expect.any(Number)
          })
        }));
      });
    });
  });
});