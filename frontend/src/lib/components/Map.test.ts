import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import Map from './Map.svelte';

describe('Map Component', () => {
  it('renders without errors', async () => {
    const { component } = render(Map);
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts center prop as [lat, lng]', async () => {
    const { component } = render(Map, { props: { center: [40.7128, -74.0060] } });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts zoom prop', async () => {
    const { component } = render(Map, { props: { zoom: 10 } });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts height prop', async () => {
    const { component } = render(Map, { props: { height: '500px' } });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts markers prop', async () => {
    const markers = [
      { lat: 51.5, lng: -0.1, popup: 'Marker 1' },
      { lat: 51.6, lng: -0.2, popup: 'Marker 2' },
    ];
    const { component } = render(Map, { props: { markers } });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts empty markers array', async () => {
    const { component } = render(Map, { props: { markers: [] } });
    await tick();
    expect(component).not.toBeNull();
  });

  it('multiple instances render independently', async () => {
    const { component: c1 } = render(Map);
    const { component: c2 } = render(Map, { props: { zoom: 5 } });
    await tick();
    expect(c1).not.toBeNull();
    expect(c2).not.toBeNull();
  });

  it('accepts marker with icon', async () => {
    const markers = [
      { lat: 51.5, lng: -0.1, icon: '🏠' },
    ];
    const { component } = render(Map, { props: { markers } });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts high zoom level', async () => {
    const { component } = render(Map, { props: { zoom: 20 } });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts negative coordinates', async () => {
    const { component } = render(Map, { props: { center: [-33.8688, 151.2093] } });
    await tick();
    expect(component).not.toBeNull();
  });
});
