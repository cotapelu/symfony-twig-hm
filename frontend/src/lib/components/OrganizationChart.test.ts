import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import OrganizationChart from './OrganizationChart.svelte';

const sampleData = {
  name: 'CEO',
  title: 'Chief Executive Officer',
  children: [
    {
      name: 'CTO',
      title: 'Chief Technology Officer',
      children: [
        { name: 'Eng Manager', title: 'Engineering Manager' },
        { name: 'Dev', title: 'Developer' },
      ],
    },
    {
      name: 'CFO',
      title: 'Chief Financial Officer',
    },
  ],
};

describe('OrganizationChart Component', () => {
  it('renders wrapper with organization-chart class', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    expect(container.querySelector('.organization-chart')).not.toBeNull();
  });

  it('renders chart-placeholder when data is null', async () => {
    const { container } = render(OrganizationChart, { props: { data: null } });
    await tick();
    expect(container.querySelector('.chart-placeholder')).not.toBeNull();
    expect(screen.getByText('No organization data provided')).not.toBeNull();
  });

  it('does not render chart-placeholder when data is provided', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    expect(container.querySelector('.chart-placeholder')).toBeNull();
  });

  it('renders org-node for root node', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    const nodes = container.querySelectorAll('.org-node');
    expect(nodes.length).toBeGreaterThanOrEqual(1);
  });

  it('renders org-node for each nested child (recursive)', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    // CEO + CTO + CFO + Eng Manager + Dev = 5 nodes
    const nodes = container.querySelectorAll('.org-node');
    expect(nodes.length).toBe(5);
  });

  it('renders node-content for each node', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    const contents = container.querySelectorAll('.node-content');
    expect(contents.length).toBe(5);
  });

  it('renders node-name text from data', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    // Traversal order is depth-first: CEO, CTO, Eng Manager, Dev, CFO
    const names = container.querySelectorAll('.node-name');
    expect(names[0]?.textContent?.trim()).toBe('CEO');
    expect(names[1]?.textContent?.trim()).toBe('CTO');
    expect(names[2]?.textContent?.trim()).toBe('Eng Manager');
    expect(names[3]?.textContent?.trim()).toBe('Dev');
    expect(names[4]?.textContent?.trim()).toBe('CFO');
  });

  it('renders node-title when provided', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    const titles = container.querySelectorAll('.node-title');
    expect(titles[0]?.textContent?.trim()).toBe('Chief Executive Officer');
  });

  it('renders node-avatar with first letter of name', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    const avatars = container.querySelectorAll('.node-avatar');
    expect(avatars[0]?.textContent?.trim()).toBe('C');
    expect(avatars[1]?.textContent?.trim()).toBe('C');
  });

  it('renders node-children for nodes with children', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    const childrenContainers = container.querySelectorAll('.node-children');
    expect(childrenContainers.length).toBeGreaterThanOrEqual(1);
  });

  it('applies horizontal class when orientation=horizontal', async () => {
    const { container } = render(OrganizationChart, {
      props: { data: sampleData, orientation: 'horizontal' },
    });
    await tick();
    expect(container.querySelector('.organization-chart.horizontal')).not.toBeNull();
  });

  it('does not apply horizontal class by default (orientation=vertical)', async () => {
    const { container } = render(OrganizationChart, { props: { data: sampleData } });
    await tick();
    expect(container.querySelector('.organization-chart.horizontal')).toBeNull();
  });

  it('renders node with no children (leaf node)', async () => {
    const leafData = { name: 'Solo', title: 'No team' };
    const { container } = render(OrganizationChart, { props: { data: leafData } });
    await tick();
    expect(container.querySelector('.org-node')).not.toBeNull();
    expect(container.querySelector('.node-children')).toBeNull();
  });

  it('renders node without title (title undefined)', async () => {
    const noTitleData = { name: 'NoTitle' };
    const { container } = render(OrganizationChart, { props: { data: noTitleData } });
    await tick();
    expect(container.querySelector('.node-name')?.textContent?.trim()).toBe('NoTitle');
    expect(container.querySelector('.node-title')).toBeNull();
  });

  it('uses nodeTemplate when provided (allowHtml=false still sanitizes)', async () => {
    const customTemplate = (n: any) => `<span class="custom-node" data-id="${n.name}">CUSTOM-${n.name}</span>`;
    const { container } = render(OrganizationChart, {
      props: { data: sampleData, nodeTemplate: customTemplate, allowHtml: false },
    });
    await tick();
    const customNodes = container.querySelectorAll('.custom-node');
    expect(customNodes.length).toBe(5);
    expect(customNodes[0]?.textContent).toBe('CUSTOM-CEO');
  });

  it('uses nodeTemplate when provided with allowHtml=true (raw HTML)', async () => {
    const customTemplate = (n: any) => `<span class="custom-node" data-id="${n.name}">RAW-${n.name}</span>`;
    const { container } = render(OrganizationChart, {
      props: { data: sampleData, nodeTemplate: customTemplate, allowHtml: true },
    });
    await tick();
    const customNodes = container.querySelectorAll('.custom-node');
    expect(customNodes.length).toBe(5);
    expect(customNodes[0]?.textContent).toBe('RAW-CEO');
  });

  it('renders deeply nested org chart (3 levels)', async () => {
    const deep = {
      name: 'L1',
      children: [
        {
          name: 'L2',
          children: [
            { name: 'L3', children: [{ name: 'L4' }] },
          ],
        },
      ],
    };
    const { container } = render(OrganizationChart, { props: { data: deep } });
    await tick();
    const nodes = container.querySelectorAll('.org-node');
    expect(nodes.length).toBe(4);
  });

  it('does not render node-children when children array is empty', async () => {
    const emptyChildren = { name: 'Root', children: [] };
    const { container } = render(OrganizationChart, { props: { data: emptyChildren } });
    await tick();
    expect(container.querySelector('.node-children')).toBeNull();
  });

  it('renders node-content with custom class for nodeTemplate output', async () => {
    const tpl = (n: any) => `<span>${n.name}</span>`;
    const { container } = render(OrganizationChart, {
      props: { data: { name: 'X' }, nodeTemplate: tpl },
    });
    await tick();
    const nodeContent = container.querySelector('.node-content');
    expect(nodeContent?.textContent?.trim()).toBe('X');
  });
});
