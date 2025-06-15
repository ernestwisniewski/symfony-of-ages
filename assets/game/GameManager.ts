import { GameMap } from './map/GameMap';
import { SelectionSystem } from './selection/SelectionSystem';
import { ColorUtils } from './utils/ColorUtils';
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
    console.log(cities)
    this.currentGame = gameData ? this.convertGameResource(gameData) : null;
    this.currentUnits = units ? units.map(unit => this.convertUnitResource(unit)) : [];
    this.currentCities = cities ? cities.map(city => this.convertCityResource(city)) : [];

    this.setupEventHandlers();
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
      id: unitResource.id,
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
      id: cityResource.id,
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
    this.updateGameMap();
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
    this.gameMap.onPlayerClick = (data: any) => {
      // Check if it's unit data
      if (data && data.id && data.type) {
        const unitData = data as UnitData;
        this.selectionSystem.selectPlayer({
          id: unitData.id,
          name: `${unitData.type} (${unitData.ownerId})`,
          position: {
            row: unitData.position.y,
            col: unitData.position.x
          },
          movementPoints: unitData.movementRange,
          maxMovementPoints: unitData.movementRange,
          color: ColorUtils.getColorForOwner(unitData.ownerId)
        });

        // Emit custom event for external handling
        this.gameMap.getElement().dispatchEvent(new CustomEvent('unitclick', {
          detail: { unitData }
        }));
      }
      // Check if it's city data
      else if (data && data.id && data.name) {
        const cityData = data as CityData;
        this.selectionSystem.selectPlayer({
          id: cityData.id,
          name: cityData.name,
          position: {
            row: cityData.position.y,
            col: cityData.position.x
          },
          movementPoints: 0,
          maxMovementPoints: 0,
          color: ColorUtils.getColorForOwner(cityData.ownerId)
        });

        // Emit custom event for external handling
        this.gameMap.getElement().dispatchEvent(new CustomEvent('cityclick', {
          detail: { cityData }
        }));
      }
    };
  }

  /**
   * Update game map with current data
   */
  private updateGameMap(): void {
    // Update units on map
    this.gameMap.addUnits(this.currentUnits);

    // Update cities on map
    this.gameMap.addCities(this.currentCities);

    // Center camera on first unit if available
    if (this.currentUnits.length > 0) {
      const firstUnit = this.currentUnits[0];

      // Use setTimeout to ensure units are rendered before centering camera
      setTimeout(() => {
        this.gameMap.centerCameraOnPosition(firstUnit.position.x, firstUnit.position.y);
      }, 100);
    }
  }

  /**
   * Add unit to the map
   */
  addUnit(unitData: UnitData): void {
    this.gameMap.addUnits([unitData]);
  }

  /**
   * Add city to the map
   */
  addCity(cityData: CityData): void {
    this.gameMap.addCities([cityData]);
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
