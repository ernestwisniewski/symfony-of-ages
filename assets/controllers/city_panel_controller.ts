import {Controller} from '@hotwired/stimulus'
import {Component, getComponent} from '@symfony/ux-live-component';
import type {CityResource} from '../api'

export default class extends Controller<HTMLElement> {
  private component: Component | null = null;

  async connect(): Promise<void> {
    await this.initializeComponent();
    this.setupInteractionHandlers();
  }

  async initializeComponent(): Promise<void> {
    try {
      this.component = await getComponent(this.element);
    } catch (error) {
      console.error('Failed to initialize Live Component:', error);
    }
  }

  setupInteractionHandlers(): void {
    document.addEventListener('cityclick', (event: Event) => {
      const customEvent = event as CustomEvent;
      this.handleCitySelection(customEvent.detail);
    });
    document.addEventListener('keydown', (event: KeyboardEvent) => {
      if (event.key === 'Escape' && this.component) {
        this.component.emit('panel:close', {});
      }
    });
  }

  private handleCitySelection(cityData: { cityData: CityResource }): void {
    if (!this.component) return;
    const payload = this.formatCityPayload(cityData.cityData);
    this.component.emit('city-panel:open', {payload});
  }

  private formatCityPayload(cityData: CityResource): {
    name: string | null;
    ownerId: string | null;
    position: { x: number; y: number } | null;
    cityId: string | null;
    population: number;
    production: number;
    food: number;
    gold: number;
  } {
    return {
      name: cityData.name ?? null,
      ownerId: cityData.ownerId ?? null,
      position: cityData.position ?? null,
      cityId: cityData.id ?? null,
      population: 1000,
      production: 10,
      food: 20,
      gold: 50
    };
  }

  close(): void {
    if (!this.component) return;
    this.component.emit('panel:close', {});
  }

  disconnect(): void {
    this.component = null;
  }
}
