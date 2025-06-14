import { GameMap } from './map/GameMap';
import { SelectionSystem } from './selection/SelectionSystem';
import type { GameResource, UnitResource, CityResource, MapResource } from '../api';
import type { TerrainTile, MapConfig, GameData, UnitData, CityData, MapData } from './core';

/**
 * GameManager handles map rendering and game interactions
 * Uses data passed from the controller instead of API calls
 */
export class GameManager {
  private gameMap: GameMap;
  private selectionSystem: SelectionSystem;
  private currentGame: GameData | null = null;
  private currentUnits: UnitData[] = [];
  private currentCities: CityData[] = [];

  constructor(element: HTMLElement, config: MapConfig, gameData?: GameResource, units?: UnitResource[], cities?: CityResource[]) {
    this.gameMap = new GameMap(element, config);
    this.selectionSystem = new SelectionSystem();

    // Convert API resources to internal game data
    this.currentGame = gameData ? this.convertGameResource(gameData) : null;
    this.currentUnits = units ? units.map(unit => this.convertUnitResource(unit)) : [];
    this.currentCities = cities ? cities.map(city => this.convertCityResource(city)) : [];

    this.setupEventHandlers();
    this.updateGameMap();
  }

  /**
   * Convert GameResource to internal GameData
   */
  private convertGameResource(gameResource: GameResource): GameData {
    return {
      id: gameResource.gameId || '',
      name: gameResource.name || '',
      status: gameResource.status || '',
      activePlayer: gameResource.activePlayer || '',
      currentTurn: gameResource.currentTurn || 0,
      createdAt: gameResource.createdAt || '',
      players: gameResource.players || [],
      userId: 0, // Not available in API resource
      startedAt: gameResource.startedAt || null,
      currentTurnAt: null // Not available in API resource
    };
  }

  /**
   * Convert UnitResource to internal UnitData
   */
  private convertUnitResource(unitResource: UnitResource): UnitData {
    return {
      id: unitResource.unitId || '',
      ownerId: unitResource.ownerId || '',
      gameId: unitResource.gameId || '',
      type: unitResource.type || '',
      position: unitResource.position || { x: 0, y: 0 },
      currentHealth: unitResource.currentHealth || 0,
      maxHealth: unitResource.maxHealth || 0,
      isDead: unitResource.isDead || false,
      attackPower: unitResource.attackPower || 0,
      defensePower: unitResource.defensePower || 0,
      movementRange: unitResource.movementRange || 0
    };
  }

  /**
   * Convert CityResource to internal CityData
   */
  private convertCityResource(cityResource: CityResource): CityData {
    return {
      id: cityResource.cityId || '',
      ownerId: cityResource.ownerId || '',
      gameId: cityResource.gameId || '',
      name: cityResource.name || '',
      position: cityResource.position || { x: 0, y: 0 }
    };
  }

  /**
   * Initialize the game map
   */
  async init(): Promise<void> {
    await this.gameMap.init();
  }

  /**
   * Setup event handlers for map interactions
   */
  private setupEventHandlers(): void {
    // Handle hex clicks for selection and interaction
    this.gameMap.onHexClick = (row: number, col: number, terrainData: TerrainTile) => {
      this.selectionSystem.selectHex(terrainData, { row, col });

      // Emit custom event for external handling
      this.gameMap.getElement().dispatchEvent(new CustomEvent('hexclick', {
        detail: { row, col, terrainData }
      }));
    };

    // Handle unit clicks for selection
    this.gameMap.onPlayerClick = (playerData: any) => {
      // Convert to UnitData if it's a unit, or use as PlayerData if it's already in the right format
      const unitData = this.convertToUnitData(playerData);
      if (unitData) {
        // Convert UnitData to PlayerData format for selection system
        const playerDataForSelection = {
          id: unitData.id,
          name: `${unitData.type} (${unitData.ownerId})`,
          position: {
            row: unitData.position.y,
            col: unitData.position.x
          },
          movementPoints: unitData.movementRange,
          maxMovementPoints: unitData.movementRange,
          color: this.getColorForOwner(unitData.ownerId)
        };
        this.selectionSystem.selectPlayer(playerDataForSelection);
      }

      // Emit custom event for external handling
      this.gameMap.getElement().dispatchEvent(new CustomEvent('unitclick', {
        detail: { playerData: unitData || playerData }
      }));
    };
  }

  /**
   * Convert player data to UnitData format
   */
  private convertToUnitData(playerData: any): UnitData | null {
    // If it's already UnitData, return as is
    if (playerData && playerData.id && playerData.type) {
      return playerData as UnitData;
    }

    // If it's PlayerData format, try to find corresponding UnitData
    if (playerData && playerData.id) {
      return this.currentUnits.find(unit => unit.id === playerData.id) || null;
    }

    return null;
  }

  /**
   * Update game map with current data
   */
  private updateGameMap(): void {
    // Update units on map
    this.currentUnits.forEach(unit => {
      this.addUnit(unit);
    });

    // Update cities on map
    this.currentCities.forEach(city => {
      this.addCity(city);
    });
  }

  /**
   * Add unit to the map
   */
  addUnit(unitData: UnitData): void {
    // Convert UnitData to PlayerData for compatibility with existing GameMap
    const playerData = {
      id: unitData.id,
      name: `${unitData.type} (${unitData.ownerId})`,
      position: {
        row: unitData.position.y,
        col: unitData.position.x
      },
      movementPoints: unitData.movementRange,
      maxMovementPoints: unitData.movementRange,
      color: this.getColorForOwner(unitData.ownerId)
    };

    this.gameMap.addPlayer(playerData);
  }

  /**
   * Get color for owner ID (simple hash-based color generation)
   */
  private getColorForOwner(ownerId: string): number {
    let hash = 0;
    for (let i = 0; i < ownerId.length; i++) {
      const char = ownerId.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash; // Convert to 32bit integer
    }

    // Generate a color from the hash
    const hue = Math.abs(hash) % 360;
    return this.hsvToRgb(hue, 0.8, 0.9);
  }

  /**
   * Convert HSV to RGB color
   */
  private hsvToRgb(h: number, s: number, v: number): number {
    const c = v * s;
    const x = c * (1 - Math.abs((h / 60) % 2 - 1));
    const m = v - c;

    let r = 0, g = 0, b = 0;

    if (h >= 0 && h < 60) {
      r = c; g = x; b = 0;
    } else if (h >= 60 && h < 120) {
      r = x; g = c; b = 0;
    } else if (h >= 120 && h < 180) {
      r = 0; g = c; b = x;
    } else if (h >= 180 && h < 240) {
      r = 0; g = x; b = c;
    } else if (h >= 240 && h < 300) {
      r = x; g = 0; b = c;
    } else if (h >= 300 && h < 360) {
      r = c; g = 0; b = x;
    }

    const red = Math.round((r + m) * 255);
    const green = Math.round((g + m) * 255);
    const blue = Math.round((b + m) * 255);

    return (red << 16) | (green << 8) | blue;
  }

  /**
   * Add city to the map
   */
  addCity(cityData: CityData): void {
    // TODO: Implement city rendering
  }

  /**
   * Get current game data
   */
  getCurrentGame(): GameData | null {
    return this.currentGame;
  }

  /**
   * Get current units
   */
  getCurrentUnits(): UnitData[] {
    return this.currentUnits;
  }

  /**
   * Get current cities
   */
  getCurrentCities(): CityData[] {
    return this.currentCities;
  }

  /**
   * Get selected hex data
   */
  getSelectedHex(): any {
    const selected = this.selectionSystem.getManager().getSelected();
    return selected && selected.type === 'hex' ? selected : null;
  }

  /**
   * Get selected unit data
   */
  getSelectedUnit(): any {
    const selected = this.selectionSystem.getManager().getSelected();
    return selected && selected.type === 'player' ? selected : null;
  }

  /**
   * Get PIXI application for cleanup
   */
  get app() {
    return this.gameMap.app;
  }

  /**
   * Cleanup all systems
   */
  destroy(): void {
    this.selectionSystem.destroy();
    if (this.gameMap.app) {
      this.gameMap.app.destroy(true);
    }
  }
}
