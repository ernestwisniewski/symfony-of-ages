import {Controller} from '@hotwired/stimulus'
import {Component, getComponent} from '@symfony/ux-live-component';
import type {CityResource, UnitResource} from '../api'
import {TerrainType} from '../api'
import type {GridPosition} from '../game/core'

/**
 * Selection Panel Controller
 * Handles communication between game events and the Live Component selection panel
 */
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
    // Listen for hex click events from the game map
    document.addEventListener('hexclick', (event: Event) => {
      const customEvent = event as CustomEvent;
      this.handleHexSelection(customEvent.detail);
    });

    // Listen for unit click events from the game map
    document.addEventListener('unitclick', (event: Event) => {
      const customEvent = event as CustomEvent;
      this.handleUnitSelection(customEvent.detail);
    });

    // Listen for city click events from the game map
    document.addEventListener('cityclick', (event: Event) => {
      const customEvent = event as CustomEvent;
      this.handleCitySelection(customEvent.detail);
    });

    // Listen for escape key to close panel
    document.addEventListener('keydown', (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        this.closePanel();
      }
    });

    // Listen for flash messages from Live Component
    document.addEventListener('flash:success', (event: Event) => {
      const customEvent = event as CustomEvent;
      this.showFlashMessage('success', customEvent.detail.message);
    });

    document.addEventListener('flash:error', (event: Event) => {
      const customEvent = event as CustomEvent;
      this.showFlashMessage('error', customEvent.detail.message);
    });
  }

  /**
   * Handle hex tile selection
   */
  private handleHexSelection(hexData: { row: number; col: number; terrainData: any }): void {
    if (!this.component) return;

    const payload = this.formatHexPayload(hexData);
    this.component.emit('open', {type: 'hex', payload});
  }

  /**
   * Handle unit selection
   */
  private handleUnitSelection(unitData: { unitData: any }): void {
    console.log('Unit selection event received:', unitData);

    if (!this.component) {
      console.warn('Live Component not initialized');
      return;
    }

    const payload = this.formatUnitPayload(unitData.unitData);
    console.log('Formatted unit payload:', payload);

    this.component.emit('open', {type: 'unit', payload});
  }

  /**
   * Handle city selection
   */
  private handleCitySelection(cityData: { cityData: CityResource }): void {
    if (!this.component) return;

    const payload = this.formatCityPayload(cityData.cityData);
    this.component.emit('open', {type: 'city', payload});
  }

  /**
   * Format hex data for the Live Component payload
   * Returns proper API types for consistency with backend
   */
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

    // Ensure we have a valid terrain type using TerrainType enum
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

  /**
   * Get valid terrain type from terrain data, ensuring it matches TerrainType enum
   */
  private getValidTerrainType(terrainData: any): TerrainType {
    // If terrain has a valid type, use it
    if (terrainData.type && Object.values(TerrainType).includes(terrainData.type)) {
      return terrainData.type;
    }

    // Try to detect from properties
    const detectedType = this.detectTerrainType(terrainData);

    // Ensure the detected type is valid
    if (Object.values(TerrainType).includes(detectedType as TerrainType)) {
      return detectedType as TerrainType;
    }

    // Default to plains if we can't determine
    return TerrainType.PLAINS;
  }

  /**
   * Detect terrain type from terrain data if not explicitly set
   */
  private detectTerrainType(terrainData: any): string {
    const properties = terrainData.properties;

    if (properties) {
      // Check if it's water (impassable)
      if (properties.impassable) {
        return TerrainType.WATER;
      }

      // Check movement cost to guess terrain type
      if (properties.movementCost === 2) {
        return TerrainType.FOREST;
      }
      if (properties.movementCost === 3) {
        return TerrainType.MOUNTAIN;
      }
    }

    // Default to plains if we can't determine
    return TerrainType.PLAINS;
  }

  /**
   * Get display name for terrain type
   */
  private getTerrainDisplayName(terrainData: any): string {
    // If terrain has a name, use it
    if (terrainData.name && terrainData.name !== 'Unknown') {
      return terrainData.name;
    }

    // Get terrain type (either from data or detected)
    const terrainType = this.getValidTerrainType(terrainData);

    // Generate name from type
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

  /**
   * Format unit data for the Live Component payload
   * Converts UnitData to UnitResource format for consistency with backend
   */
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
    // Handle both UnitData (internal) and UnitResource (API) formats
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

  /**
   * Format city data for the Live Component payload
   * Returns proper CityResource structure for consistency with backend
   */
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
      cityId: cityData.cityId ?? null,
      // Default values for city properties (can be extended later)
      population: 1000,
      production: 10,
      food: 20,
      gold: 50
    };
  }

  /**
   * Calculate resources from terrain properties
   */
  private calculateResources(properties: any): number {
    let resources = 0;

    if (properties.foodBonus) resources += properties.foodBonus;
    if (properties.goldBonus) resources += properties.goldBonus;
    if (properties.productionBonus) resources += properties.productionBonus;

    return resources;
  }

  /**
   * Close the selection panel
   */
  private closePanel(): void {
    if (this.component) {
      this.component.emit('close', {});
    }
  }

  /**
   * Show flash message
   */
  private showFlashMessage(type: 'success' | 'error', message: string): void {
    // Create flash message element
    const flashElement = document.createElement('div');
    flashElement.className = `flash-message ${type}`;
    flashElement.innerHTML = `
      <span class="flash-icon">
        ${type === 'success' ? '✓' : '✗'}
      </span>
      <span class="flash-text">${message}</span>
    `;

    // Add to flash messages container or create one
    let flashContainer = document.querySelector('.flash-messages');
    if (!flashContainer) {
      flashContainer = document.createElement('div');
      flashContainer.className = 'flash-messages';
      document.body.appendChild(flashContainer);
    }

    flashContainer.appendChild(flashElement);

    // Auto-remove after 5 seconds
    setTimeout(() => {
      if (flashElement.parentNode) {
        flashElement.parentNode.removeChild(flashElement);
      }
    }, 5000);
  }

  disconnect(): void {
    this.component = null;
  }
}
