import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Menu from './Menu.svelte';

describe('Menu Component', () => {
  const defaultItems = [
    { key: '1', label: 'Dashboard', icon: '🏠' },
    { key: '2', label: 'Settings', icon: '⚙️', badge: '3' },
    { key: '3', label: 'Profile', icon: '👤' },
    { key: '4', label: 'Disabled Item', disabled: true },
  ];

  const subMenuItems = [
    { key: '1', label: 'Dashboard', icon: '🏠' },
    { 
      key: '2', 
      label: 'Products', 
      icon: '📦',
      children: [
        { key: '2-1', label: 'All Products' },
        { key: '2-2', label: 'Categories' },
        { key: '2-3', label: 'Inventory' },
      ]
    },
    { 
      key: '3', 
      label: 'Settings', 
      icon: '⚙️',
      children: [
        { key: '3-1', label: 'General' },
        { key: '3-2', label: 'Notifications', badge: '5' },
        { key: '3-3', label: 'Privacy' },
      ]
    },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Basic Rendering', () => {
    it('renders menu with default vertical mode', () => {
      render(Menu, { props: { items: defaultItems } });
      const menu = screen.getByRole('menu');
      expect(menu).toHaveClass('menu-vertical');
      expect(menu).not.toHaveClass('menu-horizontal');
      expect(menu).not.toHaveClass('menu-inline');
    });

    it('renders all menu items', () => {
      render(Menu, { props: { items: defaultItems } });
      expect(screen.getByText('Dashboard')).toBeInTheDocument();
      expect(screen.getByText('Settings')).toBeInTheDocument();
      expect(screen.getByText('Profile')).toBeInTheDocument();
      expect(screen.getByText('Disabled Item')).toBeInTheDocument();
    });

    it('renders horizontal mode correctly', () => {
      render(Menu, { props: { items: defaultItems, mode: 'horizontal' } });
      const menu = screen.getByRole('menu');
      expect(menu).toHaveClass('menu-horizontal');
      expect(menu).toHaveAttribute('aria-orientation', 'horizontal');
    });

    it('renders inline mode correctly', () => {
      render(Menu, { props: { items: defaultItems, mode: 'inline' } });
      const menu = screen.getByRole('menu');
      expect(menu).toHaveClass('menu-inline');
    });

    it('renders dark theme correctly', () => {
      render(Menu, { props: { items: defaultItems, theme: 'dark' } });
      const menu = screen.getByRole('menu');
      expect(menu).toHaveClass('menu-dark');
    });

    it('renders icons when provided', () => {
      render(Menu, { props: { items: defaultItems } });
      expect(screen.getByText('🏠')).toBeInTheDocument();
      expect(screen.getByText('⚙️')).toBeInTheDocument();
      expect(screen.getByText('👤')).toBeInTheDocument();
    });

    it('renders badge when provided', () => {
      render(Menu, { props: { items: defaultItems } });
      expect(screen.getByText('3')).toBeInTheDocument();
    });

    it('applies disabled class to disabled items', () => {
      render(Menu, { props: { items: defaultItems } });
      const disabledItem = screen.getByText('Disabled Item').closest('.menu-item');
      expect(disabledItem).toHaveClass('disabled');
    });
  });

  describe('Selection Handling', () => {
    it('applies selected class when selectedKeys prop matches', () => {
      render(Menu, { props: { items: defaultItems, selectedKeys: ['2'] } });
      const selectedItem = screen.getByText('Settings').closest('.menu-item');
      expect(selectedItem).toHaveClass('selected');
    });

    it('does not select disabled items', async () => {
      const selectHandler = vi.fn();
      render(Menu, { props: { items: defaultItems, onselect: selectHandler } });
      
      await fireEvent.click(screen.getByText('Disabled Item'));
      
      expect(selectHandler).not.toHaveBeenCalled();
    });

    it('updates selectedKeys when provided as prop', () => {
      const { rerender } = render(Menu, { props: { items: defaultItems, selectedKeys: ['1'] } });
      expect(screen.getByText('Dashboard').closest('.menu-item')).toHaveClass('selected');
      
      rerender({ items: defaultItems, selectedKeys: ['3'] });
      expect(screen.getByText('Profile').closest('.menu-item')).toHaveClass('selected');
    });
  });

  describe('Submenu Behavior', () => {
    it('renders submenu items when children provided', () => {
      render(Menu, { props: { items: subMenuItems } });
      expect(screen.getByText('Products')).toBeInTheDocument();
      expect(screen.getByText('Settings')).toBeInTheDocument();
    });

    it('opens submenu on click for click trigger', async () => {
      render(Menu, { 
        props: { 
          items: subMenuItems, 
          triggerSubMenuAction: 'click' 
        } 
      });
      
      // Initially submenu should be closed
      expect(screen.queryByText('All Products')).not.toBeInTheDocument();
      
      await fireEvent.click(screen.getByText('Products'));
      
      await waitFor(() => {
        expect(screen.getByText('All Products')).toBeInTheDocument();
        expect(screen.getByText('Categories')).toBeInTheDocument();
        expect(screen.getByText('Inventory')).toBeInTheDocument();
      });
    });

    it('closes submenu on click for click trigger', async () => {
      render(Menu, { 
        props: { 
          items: subMenuItems, 
          triggerSubMenuAction: 'click' 
        } 
      });
      
      await fireEvent.click(screen.getByText('Products'));
      await waitFor(() => {
        expect(screen.getByText('All Products')).toBeInTheDocument();
      });
      
      await fireEvent.click(screen.getByText('Products'));
      
      await waitFor(() => {
        expect(screen.queryByText('All Products')).not.toBeInTheDocument();
      });
    });

    it('shows arrow indicator for submenu', () => {
      render(Menu, { props: { items: subMenuItems } });
      const productsItem = screen.getByText('Products').closest('.menu-submenu');
      expect(productsItem).toBeInTheDocument();
    });
  });

  describe('Disabled State', () => {
    it('applies disabled class to entire menu when disabled prop is true', () => {
      render(Menu, { props: { items: defaultItems, disabled: true } });
      const menu = screen.getByRole('menu');
      expect(menu).toHaveClass('menu-disabled');
    });

    it('does not open submenu on disabled menu', async () => {
      render(Menu, { 
        props: { 
          items: subMenuItems, 
          disabled: true,
          triggerSubMenuAction: 'click'
        } 
      });
      
      await fireEvent.click(screen.getByText('Products'));
      
      await waitFor(() => {
        expect(screen.queryByText('All Products')).not.toBeInTheDocument();
      });
    });

    it('disables specific items via disabled prop', async () => {
      const selectHandler = vi.fn();
      const itemsWithDisabled = [
        ...defaultItems,
        { key: '5', label: 'Also Disabled', disabled: true }
      ];
      render(Menu, { 
        props: { items: itemsWithDisabled, onselect: selectHandler } 
      });
      
      await fireEvent.click(screen.getByText('Also Disabled'));
      
      expect(selectHandler).not.toHaveBeenCalled();
    });
  });

  describe('openKeys Prop', () => {
    it('opens submenu when key is in openKeys prop', () => {
      render(Menu, { 
        props: { 
          items: subMenuItems, 
          openKeys: ['2'] 
        } 
      });
      
      expect(screen.getByText('All Products')).toBeInTheDocument();
      expect(screen.getByText('Categories')).toBeInTheDocument();
      expect(screen.getByText('Inventory')).toBeInTheDocument();
    });
  });

  describe('Horizontal Mode Specific', () => {
    it('renders items horizontally', () => {
      render(Menu, { 
        props: { items: defaultItems, mode: 'horizontal' } 
      });
      const menu = screen.getByRole('menu');
      expect(menu).toHaveClass('menu-horizontal');
      expect(menu).toHaveAttribute('aria-orientation', 'horizontal');
    });

    it('has correct aria-orientation', () => {
      render(Menu, { 
        props: { items: defaultItems, mode: 'horizontal' } 
      });
      const menu = screen.getByRole('menu');
      expect(menu).toHaveAttribute('aria-orientation', 'horizontal');
    });
  });

  describe('Accessibility', () => {
    it('has correct role attributes', () => {
      render(Menu, { props: { items: defaultItems } });
      expect(screen.getByRole('menu')).toBeInTheDocument();
      expect(screen.getAllByRole('menuitem')).toHaveLength(4);
    });

    it('sets aria-expanded on submenu', () => {
      render(Menu, { props: { items: subMenuItems } });
      const submenu = screen.getByText('Products').closest('[aria-expanded]');
      expect(submenu).toHaveAttribute('aria-expanded', 'false');
    });

    it('sets aria-disabled on disabled items', () => {
      render(Menu, { props: { items: defaultItems } });
      const disabledItem = screen.getByText('Disabled Item').closest('[aria-disabled]');
      expect(disabledItem).toHaveAttribute('aria-disabled', 'true');
    });

    it('sets tabindex correctly', () => {
      render(Menu, { props: { items: defaultItems } });
      const enabledItem = screen.getByText('Profile').closest('[tabindex]');
      const disabledItem = screen.getByText('Disabled Item').closest('[tabindex]');
      expect(enabledItem).toHaveAttribute('tabindex', '0');
      expect(disabledItem).toHaveAttribute('tabindex', '-1');
    });

    it('sets aria-haspopup on submenu', () => {
      render(Menu, { props: { items: subMenuItems } });
      const submenu = screen.getByText('Products').closest('[aria-haspopup]');
      expect(submenu).toHaveAttribute('aria-haspopup', 'true');
    });
  });

  describe('Edge Cases', () => {
    it('handles empty items array', () => {
      render(Menu, { props: { items: [] } });
      const menu = screen.getByRole('menu');
      expect(menu).toBeInTheDocument();
    });

    it('handles items without keys (generates default keys)', () => {
      const itemsWithoutKeys = [
        { label: 'Item 1' },
        { label: 'Item 2' },
      ];
      render(Menu, { props: { items: itemsWithoutKeys } });
      expect(screen.getByText('Item 1')).toBeInTheDocument();
      expect(screen.getByText('Item 2')).toBeInTheDocument();
    });

    it('renders multiple instances independently', () => {
      const { container: container1 } = render(Menu, { props: { items: defaultItems } });
      const { container: container2 } = render(Menu, { props: { items: subMenuItems } });
      expect(container1).toBeTruthy();
      expect(container2).toBeTruthy();
    });

    it('handles badge as number', () => {
      const itemsWithNumberBadge = [
        { key: '1', label: 'Item 1', badge: 5 }
      ];
      render(Menu, { props: { items: itemsWithNumberBadge } });
      expect(screen.getByText('5')).toBeInTheDocument();
    });

    it('handles badge as string', () => {
      const itemsWithStringBadge = [
        { key: '1', label: 'Item 1', badge: 'NEW' }
      ];
      render(Menu, { props: { items: itemsWithStringBadge } });
      expect(screen.getByText('NEW')).toBeInTheDocument();
    });
  });
});