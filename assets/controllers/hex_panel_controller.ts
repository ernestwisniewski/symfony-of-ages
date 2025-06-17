import {Controller} from '@hotwired/stimulus'
import {Component, getComponent} from '@symfony/ux-live-component';
import {TerrainType} from '../api'
import type {GridPosition} from '../game/core'

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
    document.addEventListener('hexclick', (event: Event) => {
      const customEvent = event as CustomEvent;
      this.handleHexSelection(customEvent.detail);
    });
    document.addEventListener('keydown', (event: KeyboardEvent) => {
      if (event.key === 'Escape' && this.component) {
        this.component.emit('panel:close', {});
      }
    });
  }

  private handleHexSelection(hexData: { row: number; col: number; terrainData: any }): void {
    if (!this.component) return;
    const payload = this.formatHexPayload(hexData);
    this.component.emit('panel:hex:open', {payload});
  }

  private formatHexPayload(hexData: { row: number; col: number; terrainData: any }): {
    terrainName: string;
    terrainType: TerrainType;
    position: GridPosition;
    movementCost: number;
    defense: number;
    resources: number;
    properties: Record<string, any>;
    coordinates: string;
  } {
    const {row, col, terrainData} = hexData;
    const terrainType = this.getValidTerrainType(terrainData);
    const terrainName = this.getTerrainDisplayName(terrainData);
    const properties = terrainData.properties || {};
    return {
      terrainName: terrainName,
      terrainType: terrainType,
      position: {row, col} as GridPosition,
      movementCost: properties.movementCost ?? 1,
      defense: properties.defenseBonus ?? 0,
      resources: this.calculateResources(properties),
      properties: properties,
      coordinates: `(${row}, ${col})`
    };
  }

  private getValidTerrainType(terrainData: any): TerrainType {
    if (terrainData.type && Object.values(TerrainType).includes(terrainData.type)) {
      return terrainData.type;
    }
    const detectedType = this.detectTerrainType(terrainData);
    if (Object.values(TerrainType).includes(detectedType as TerrainType)) {
      return detectedType as TerrainType;
    }
    return TerrainType.PLAINS;
  }

  private detectTerrainType(terrainData: any): string {
    const properties = terrainData.properties;
    if (properties) {
      if (properties.impassable) {
        return TerrainType.WATER;
      }
      if (properties.movementCost === 2) {
        return TerrainType.FOREST;
      }
      if (properties.movementCost === 3) {
        return TerrainType.MOUNTAIN;
      }
    }
    return TerrainType.PLAINS;
  }

  private getTerrainDisplayName(terrainData: any): string {
    if (terrainData.name && terrainData.name !== 'Unknown') {
      return terrainData.name;
    }
    const terrainType = this.getValidTerrainType(terrainData);
    const typeNames: Record<TerrainType, string> = {
      [TerrainType.PLAINS]: 'Plains',
      [TerrainType.FOREST]: 'Forest',
      [TerrainType.MOUNTAIN]: 'Mountain',
      [TerrainType.WATER]: 'Water',
      [TerrainType.DESERT]: 'Desert',
      [TerrainType.SWAMP]: 'Swamp'
    };
    return typeNames[terrainType] || 'Unknown Terrain';
  }

  private calculateResources(properties: any): number {
    let resources = 0;
    if (properties.foodBonus) resources += properties.foodBonus;
    if (properties.goldBonus) resources += properties.goldBonus;
    if (properties.productionBonus) resources += properties.productionBonus;
    return resources;
  }

  close(): void {
    if (!this.component) return;
    this.component.emit('panel:close', {});
  }

  disconnect(): void {
    this.component = null;
  }
}
