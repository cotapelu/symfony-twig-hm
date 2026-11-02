import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import BotManager from './BotManager.svelte';

describe('BotManager Component', () => {
  it('renders the bot automation header', () => {
    render(BotManager);
    expect(screen.getByText(/Quản lý Chatbot/i)).toBeTruthy();
  });

  it('displays bots section heading', () => {
    render(BotManager);
    expect(screen.getByText('Chatbots')).toBeTruthy();
  });

  it('renders both bot cards', () => {
    render(BotManager);
    expect(screen.getByText('Customer Support Bot')).toBeTruthy();
    expect(screen.getByText('Sales Bot')).toBeTruthy();
  });

  it('shows bot status with active class', () => {
    render(BotManager);
    const activeStatuses = screen.getAllByText('active');
    expect(activeStatuses).toHaveLength(2);
    activeStatuses.forEach(status => {
      expect(status).toHaveClass('status', 'active');
    });
  });

  it('displays bot conversation counts', () => {
    render(BotManager);
    expect(screen.getByText(/1,250 cuộc trò chuyện/)).toBeTruthy();
    expect(screen.getByText(/890 cuộc trò chuyện/)).toBeTruthy();
  });

  it('displays bot response times', () => {
    render(BotManager);
    expect(screen.getByText(/2s phản hồi/)).toBeTruthy();
    expect(screen.getByText(/3s phản hồi/)).toBeTruthy();
  });

  it('displays workflows section heading', () => {
    render(BotManager);
    expect(screen.getByText('Workflows')).toBeTruthy();
  });

  it('renders all workflows', () => {
    render(BotManager);
    expect(screen.getByText('Welcome Flow')).toBeTruthy();
    expect(screen.getByText('Support Flow')).toBeTruthy();
  });

  it('shows workflow triggers', () => {
    render(BotManager);
    expect(screen.getByText('Trigger: new_user')).toBeTruthy();
    expect(screen.getByText('Trigger: help_command')).toBeTruthy();
  });

  it('displays workflow status badges', () => {
    render(BotManager);
    const badges = screen.getAllByText('enabled');
    expect(badges).toHaveLength(2);
    badges.forEach(badge => {
      expect(badge).toHaveClass('badge');
    });
  });
});
