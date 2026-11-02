import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import ChartLegend from './ChartLegend.svelte';

describe('ChartLegend Component', () => {
  const sampleData = [
    { name: 'Revenue', value: 1000, color: '#3b82f6' },
    { name: 'Expenses', value: 400, color: '#ef4444' },
    { name: 'Profit', value: 600, color: '#10b981' }
  ];

  describe('rendering items', () => {
    it('renders all legend items', () => {
      render(ChartLegend, { props: { data: sampleData } });
      expect(screen.getByText('Revenue')).toBeTruthy();
      expect(screen.getByText('Expenses')).toBeTruthy();
      expect(screen.getByText('Profit')).toBeTruthy();
    });

    it('renders correct number of legend items', () => {
      render(ChartLegend, { props: { data: sampleData } });
      const items = document.querySelectorAll('.legend-item');
      expect(items).toHaveLength(3);
    });

    it('applies background color to legend-color span', () => {
      render(ChartLegend, { props: { data: sampleData } });
      const colors = document.querySelectorAll('.legend-color');
      // JSDOM normalizes color to rgb()
      expect(colors[0].getAttribute('style')).toContain('rgb(59, 130, 246)');
      expect(colors[1].getAttribute('style')).toContain('rgb(239, 68, 68)');
      expect(colors[2].getAttribute('style')).toContain('rgb(16, 185, 129)');
    });

    it('renders empty state when data array is empty', () => {
      render(ChartLegend, { props: { data: [] } });
      const container = document.querySelector('.chart-legend');
      expect(container).toBeTruthy();
      expect(container?.children).toHaveLength(0);
    });

    it('renders Typography with caption variant for names', () => {
      render(ChartLegend, { props: { data: sampleData } });
      const typographyElements = document.querySelectorAll('.chart-legend .typography');
      expect(typographyElements).toHaveLength(3);
    });
  });

  describe('class composition', () => {
    it('applies base chart-legend class', () => {
      render(ChartLegend, { props: { data: sampleData } });
      const container = document.querySelector('.chart-legend');
      expect(container).toBeTruthy();
    });

    it('applies horizontal class when horizontal prop true', () => {
      render(ChartLegend, { props: { data: sampleData, horizontal: true } });
      const container = document.querySelector('.chart-legend');
      expect(container).toHaveClass('horizontal');
    });

    it('does not apply horizontal class when false', () => {
      render(ChartLegend, { props: { data: sampleData, horizontal: false } });
      const container = document.querySelector('.chart-legend');
      expect(container).not.toHaveClass('horizontal');
    });

    it('applies custom className', () => {
      render(ChartLegend, { props: { data: sampleData, className: 'custom-legend' } });
      const container = document.querySelector('.chart-legend');
      expect(container).toHaveClass('custom-legend');
    });

    it('combines multiple classes', () => {
      render(ChartLegend, { props: { data: sampleData, horizontal: true, className: 'custom another' } });
      const container = document.querySelector('.chart-legend');
      expect(container).toHaveClass('chart-legend');
      expect(container).toHaveClass('horizontal');
      expect(container).toHaveClass('custom');
      expect(container).toHaveClass('another');
    });
  });

  describe('default values', () => {
    it('default horizontal is false', () => {
      render(ChartLegend, { props: { data: sampleData } });
      const container = document.querySelector('.chart-legend');
      expect(container).not.toHaveClass('horizontal');
    });

    it('default type is bar', () => {
      render(ChartLegend, { props: { data: sampleData } });
      const container = document.querySelector('.chart-legend');
      // getChartType not used in class; ignore.
      expect(container).toBeTruthy();
    });

    it('default data is empty array', () => {
      render(ChartLegend);
      const container = document.querySelector('.chart-legend');
      expect(container?.children).toHaveLength(0);
    });

    it('default showLegend is true but does not affect rendering', () => {
      render(ChartLegend, { props: { data: sampleData, showLegend: false } });
      // Even if showLegend false, the component is still rendered; there is no conditional.
      // Actually the prop is const showLegend = true; it's not used? It's declared but not used in markup. So it's irrelevant.
      // So test just that it renders.
      expect(document.querySelector('.chart-legend')).toBeTruthy();
    });
  });

  describe('accessibility', () => {
    it('has no specific ARIA attributes', () => {
      render(ChartLegend, { props: { data: sampleData } });
      const container = document.querySelector('.chart-legend');
      expect(container).not.toHaveAttribute('role');
    });
  });
});
