import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import Marketplace from './Marketplace.svelte';

describe('Marketplace Component', () => {
  it('renders the marketplace header', () => {
    render(Marketplace);
    expect(screen.getByText(/Chợ trực tuyến/i)).toBeTruthy();
  });

  it('displays all initial products', () => {
    render(Marketplace);
    expect(screen.getByText('iPhone 15 Pro')).toBeTruthy();
    expect(screen.getByText('MacBook Air M3')).toBeTruthy();
    expect(screen.getByText('AirPods Pro')).toBeTruthy();
  });

  it('shows seller and rating for each product', () => {
    render(Marketplace);
    expect(screen.getByText(/TechStore/)).toBeTruthy();
    expect(screen.getByText(/⭐ 4.8/)).toBeTruthy();
  });

  it('displays product prices formatted with VND', () => {
    render(Marketplace);
    expect(screen.getByText('25,000,000 VND')).toBeTruthy();
    expect(screen.getByText('28,000,000 VND')).toBeTruthy();
    expect(screen.getByText('4,500,000 VND')).toBeTruthy();
  });

  it('renders add to cart buttons for each product', () => {
    render(Marketplace);
    const buttons = screen.getAllByRole('button', { name: /Thêm vào giỏ/i });
    expect(buttons).toHaveLength(3);
  });

  it('adds product to cart when button clicked', async () => {
    render(Marketplace);
    const addButtons = screen.getAllByRole('button', { name: /Thêm vào giỏ/i });
    fireEvent.click(addButtons[0]);
    await waitFor(() => {
      expect(screen.getByText('🛒 1 sản phẩm')).toBeTruthy();
    });
  });

  it('shows cart float only when cart has items', async () => {
    render(Marketplace);
    expect(screen.queryByText(/🛒 \d+ sản phẩm/)).toBeFalsy();
    fireEvent.click(screen.getAllByRole('button', { name: /Thêm vào giỏ/i })[0]);
    await waitFor(() => {
      expect(screen.getByText('🛒 1 sản phẩm')).toBeTruthy();
    });
  });

  it('increments cart count when multiple items added', async () => {
    render(Marketplace);
    const addButtons = screen.getAllByRole('button', { name: /Thêm vào giỏ/i });
    fireEvent.click(addButtons[0]);
    fireEvent.click(addButtons[1]);
    fireEvent.click(addButtons[2]);
    await waitFor(() => {
      expect(screen.getByText('🛒 3 sản phẩm')).toBeTruthy();
    });
  });

  it('adds same product multiple times', async () => {
    render(Marketplace);
    const addButtons = screen.getAllByRole('button', { name: /Thêm vào giỏ/i });
    fireEvent.click(addButtons[0]);
    fireEvent.click(addButtons[0]);
    await waitFor(() => {
      expect(screen.getByText('🛒 2 sản phẩm')).toBeTruthy();
    });
  });
});
