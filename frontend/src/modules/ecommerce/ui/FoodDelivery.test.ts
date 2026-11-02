import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, fireEvent, cleanup } from '@testing-library/svelte';
import FoodDelivery from './FoodDelivery.svelte';

describe('FoodDelivery', () => {
  afterEach(() => cleanup());

  describe('rendering', () => {
    it('renders the heading', () => {
      render(FoodDelivery);
      expect(screen.getByText(/Giao đồ ăn/)).toBeInTheDocument();
    });

    it('renders both restaurants', () => {
      render(FoodDelivery);
      expect(screen.getByText('Nhà hàng A')).toBeInTheDocument();
      expect(screen.getByText('Quán B')).toBeInTheDocument();
    });

    it('renders restaurant cuisine, rating and delivery time', () => {
      render(FoodDelivery);
      expect(screen.getByText(/Việt Nam/)).toBeInTheDocument();
      expect(screen.getByText(/4\.5/)).toBeInTheDocument();
      expect(screen.getByText(/30-45 min/)).toBeInTheDocument();
    });

    it('renders an add-to-cart button per restaurant', () => {
      render(FoodDelivery);
      expect(screen.getAllByRole('button', { name: 'Thêm vào giỏ' })).toHaveLength(2);
    });

    it('does not render the cart when empty', () => {
      render(FoodDelivery);
      expect(document.querySelector('.cart')).not.toBeInTheDocument();
    });
  });

  describe('addToCart', () => {
    it('shows the cart with count 1 after one add', () => {
      render(FoodDelivery);
      fireEvent.click(screen.getAllByRole('button', { name: 'Thêm vào giỏ' })[0]);
      expect(screen.getByText(/Giỏ hàng: 1 món/)).toBeInTheDocument();
    });

    it('increments the cart count on multiple adds', () => {
      render(FoodDelivery);
      const buttons = screen.getAllByRole('button', { name: 'Thêm vào giỏ' });
      fireEvent.click(buttons[0]);
      fireEvent.click(buttons[1]);
      fireEvent.click(buttons[0]);
      expect(screen.getByText(/Giỏ hàng: 3 món/)).toBeInTheDocument();
    });

    it('cart appears only after first add (edge: zero items hidden)', () => {
      render(FoodDelivery);
      expect(document.querySelector('.cart')).not.toBeInTheDocument();
      fireEvent.click(screen.getAllByRole('button', { name: 'Thêm vào giỏ' })[0]);
      expect(document.querySelector('.cart')).toBeInTheDocument();
    });
  });
});
