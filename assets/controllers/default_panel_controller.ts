import {Controller} from '@hotwired/stimulus'
import {Component, getComponent} from '@symfony/ux-live-component';

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
    document.addEventListener('keydown', (event: KeyboardEvent) => {
      if (event.key === 'Escape' && this.component) {
        this.component.emit('panel:close', {});
      }
    });
  }

  show(): void {
    if (!this.component) return;
    this.component.emit('default-panel:open', {});
  }

  hide(): void {
    if (!this.component) return;
    this.component.emit('panel:close', {});
  }

  disconnect(): void {
    this.component = null;
  }
}
