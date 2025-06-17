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
    document.addEventListener('unitclick', (event: Event) => {
      const customEvent = event as CustomEvent;
      this.handleUnitSelection(customEvent.detail);
    });
    document.addEventListener('keydown', (event: KeyboardEvent) => {
      if (event.key === 'Escape' && this.component) {
        this.component.emit('panel:close', {});
      }
    });
  }

  private handleUnitSelection(unitData: { unitData: any }): void {
    if (!this.component) return;
    const payload = this.formatUnitPayload(unitData.unitData);
    this.component.emit('panel:unit:close', {payload});
  }

  private formatUnitPayload(unitData: any): {
    type: string | null;
    ownerId: string | null;
    gameId: string | null;
    position: { x: number; y: number } | null;
    movementRange: number | null;
    currentHealth: number | null;
    maxHealth: number | null;
    health: number;
    attack: number | null;
    defense: number | null;
    isDead: boolean | null;
    unitId: string | null;
  } {
    const unitId = unitData.unitId || unitData.id;
    const type = unitData.type;
    const ownerId = unitData.ownerId;
    const gameId = unitData.gameId;
    const position = unitData.position;
    const currentHealth = unitData.currentHealth;
    const maxHealth = unitData.maxHealth;
    const attackPower = unitData.attackPower;
    const defensePower = unitData.defensePower;
    const movementRange = unitData.movementRange;
    const isDead = unitData.isDead;
    return {
      type: type ?? null,
      ownerId: ownerId ?? null,
      gameId: gameId ?? null,
      position: position ?? null,
      movementRange: movementRange ?? null,
      currentHealth: currentHealth ?? null,
      maxHealth: maxHealth ?? null,
      health: currentHealth && maxHealth
        ? Math.round((currentHealth / maxHealth) * 100)
        : 0,
      attack: attackPower ?? null,
      defense: defensePower ?? null,
      isDead: isDead ?? null,
      unitId: unitId ?? null
    };
  }

  disconnect(): void {
    this.component = null;
  }

  close(): void {
    if (!this.component) return;
    this.component.emit('panel:close', {});
  }
}
