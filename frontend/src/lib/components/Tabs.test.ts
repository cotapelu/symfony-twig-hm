import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import Tabs from './Tabs.svelte';

describe('Tabs Component', () => {
  const defaultTabs = [
    { label: 'Tab 1' },
    { label: 'Tab 2' },
    { label: 'Tab 3' }
  ];

  it('renders tab list with correct number of tabs', () => {
    render(Tabs, { props: { tabs: defaultTabs } });
    const tabs = screen.getAllByRole('tab');
    expect(tabs).toHaveLength(3);
  });

  it('sets first tab active by default', () => {
    render(Tabs, { props: { tabs: defaultTabs } });
    const firstTab = screen.getByRole('tab', { name: 'Tab 1' });
    expect(firstTab.getAttribute('aria-selected')).toBe('true');
    expect(firstTab.getAttribute('tabindex')).toBe('0');
  });

  it('renders with custom activeTab', () => {
    render(Tabs, { props: { tabs: defaultTabs, activeTab: 1 } });
    const tabs = screen.getAllByRole('tab');
    expect(tabs[1].getAttribute('aria-selected')).toBe('true');
    expect(tabs[0].getAttribute('aria-selected')).toBe('false');
  });

  it('clicking a tab updates active class', async () => {
    render(Tabs, { props: { tabs: defaultTabs } });
    const secondTab = screen.getByRole('tab', { name: 'Tab 2' });
    await fireEvent.click(secondTab);
    expect(secondTab).toHaveClass('active');
    const firstTab = screen.getByRole('tab', { name: 'Tab 1' });
    expect(firstTab).not.toHaveClass('active');
  });

  it('disables tab when tab.disabled is true', () => {
    const tabsWithDisabled = [
      { label: 'Enabled' },
      { label: 'Disabled', disabled: true }
    ];
    render(Tabs, { props: { tabs: tabsWithDisabled } });
    const disabledTab = screen.getByRole('tab', { name: 'Disabled' });
    expect(disabledTab).toHaveClass('disabled');
    expect(disabledTab).toHaveAttribute('disabled');
  });

  it('disables all tabs when disabled prop is true', () => {
    render(Tabs, { props: { tabs: defaultTabs, disabled: true } });
    const allTabs = screen.getAllByRole('tab');
    allTabs.forEach(tab => {
      expect(tab).toHaveAttribute('disabled');
      expect(tab).toHaveClass('disabled');
    });
  });

  it('applies sm size class', () => {
    render(Tabs, { props: { tabs: defaultTabs, size: 'sm' } });
    const tab = screen.getAllByRole('tab')[0];
    expect(tab).toHaveClass('tab-sm');
  });

  it('applies md size class by default', () => {
    render(Tabs, { props: { tabs: defaultTabs } });
    const tab = screen.getAllByRole('tab')[0];
    expect(tab).toHaveClass('tab-md');
  });

  it('applies lg size class', () => {
    render(Tabs, { props: { tabs: defaultTabs, size: 'lg' } });
    const tab = screen.getAllByRole('tab')[0];
    expect(tab).toHaveClass('tab-lg');
  });

  it('applies default variant class', () => {
    render(Tabs, { props: { tabs: defaultTabs, variant: 'default' } });
    const tab = screen.getAllByRole('tab')[0];
    expect(tab).toHaveClass('tab-default');
  });

  it('applies filled variant class', () => {
    render(Tabs, { props: { tabs: defaultTabs, variant: 'filled' } });
    const tab = screen.getAllByRole('tab')[0];
    expect(tab).toHaveClass('tab-filled');
  });

  it('applies outlined variant class', () => {
    render(Tabs, { props: { tabs: defaultTabs, variant: 'outlined' } });
    const tab = screen.getAllByRole('tab')[0];
    expect(tab).toHaveClass('tab-outlined');
  });

  it('applies fullWidth class when set', () => {
    render(Tabs, { props: { tabs: defaultTabs, fullWidth: true } });
    const tabsContainer = document.querySelector('.tabs');
    expect(tabsContainer).toHaveClass('fullWidth');
  });

  it('renders tabpanels for each tab', () => {
    render(Tabs, { props: { tabs: defaultTabs } });
    const panels = document.querySelectorAll('[role="tabpanel"]');
    expect(panels).toHaveLength(3);
  });

  it('active tabpanel has active class', () => {
    render(Tabs, { props: { tabs: defaultTabs, activeTab: 1 } });
    const panels = document.querySelectorAll('.tab-panel');
    expect(panels[1]).toHaveClass('active');
    expect(panels[0]).not.toHaveClass('active');
    expect(panels[2]).not.toHaveClass('active');
  });
});
