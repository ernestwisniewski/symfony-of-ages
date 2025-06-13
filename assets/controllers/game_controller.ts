// controllers/game_controller.ts
import {Controller} from '@hotwired/stimulus'
import {GameManager} from '../game/GameManager'
import type {GameData, UnitData, CityData, MapData} from '../game/core/index.ts'

/**
 * Interface for map configuration
 */
interface MapConfig {
  cols: number;
  rows: number;
  size: number;
  mapData: any[][];
}

/**
 * Stimulus controller for managing the hexagonal game map
 * Handles initialization and data loading from controller
 */
export default class extends Controller<HTMLElement> {
  /**
   * Stimulus values configuration for the controller
   */
  static values = {
    gameId: String,
    mapData: Object,
    gameData: Object,
    unitsData: Array,
    citiesData: Array
  }

  // Value type declarations for TypeScript
  declare readonly gameIdValue: string;
  declare readonly mapDataValue: MapData;
  declare readonly gameDataValue: GameData;
  declare readonly unitsDataValue: UnitData[];
  declare readonly citiesDataValue: CityData[];

  // Game manager instance
  private gameManager: GameManager | null = null;

  /**
   * Stimulus connect lifecycle method - called when controller is connected to DOM
   */
  async connect(): Promise<void> {
    try {
      await this.initializeGame();
    } catch (error) {
      console.error('Error initializing game:', error);
    }
  }

  /**
   * Initialize the game using GameManager
   */
  private async initializeGame(): Promise<void> {
    if (!this.gameIdValue) {
      throw new Error('Game ID is required');
    }

    // Use data passed from Twig instead of API calls
    const mapData: MapData = this.mapDataValue;
    const gameData: GameData = this.gameDataValue;
    const unitsData: UnitData[] = this.unitsDataValue || [];
    const citiesData: CityData[] = this.citiesDataValue || [];

    // Convert map data to GameManager format
    const config: MapConfig = {
      cols: mapData.width,
      rows: mapData.height,
      size: 32, // Default hex size
      mapData: mapData.tiles
    };

    // Create GameManager with data passed from controller
    this.gameManager = new GameManager(
      this.element,
      config,
      gameData,
      unitsData,
      citiesData
    );
    await this.gameManager.init();

    // Add event listeners for basic interactions
    this.setupInteractionHandlers();
  }

  /**
   * Setup basic interaction handlers
   */
  private setupInteractionHandlers(): void {
    // Handle hex clicks
    this.element.addEventListener('hexclick', (event: any) => {
      console.log('Hex clicked:', event.detail);
      this.handleHexSelection(event.detail);
    });

    // Handle unit clicks
    this.element.addEventListener('unitclick', (event: any) => {
      console.log('Unit clicked:', event.detail);
      this.handleUnitSelection(event.detail);
    });

    // Handle clear selection events from panel
    document.addEventListener('clearSelection', () => {
      console.log('Clear selection event received');
      // The Live Component will handle the UI update
    });
  }

  /**
   * Handle hex selection
   */
  private handleHexSelection(hexData: any): void {
    console.log('🎯 Handling hex selection:', hexData);
    
    const formattedHexData = {
      terrainName: hexData.terrainData?.name || hexData.terrainData?.type || 'Unknown terrain',
      movementCost: hexData.terrainData?.properties?.movement || 1,
      defense: hexData.terrainData?.properties?.defense || 0,
      resources: hexData.terrainData?.properties?.resources || 0,
      position: {
        row: hexData.row,
        col: hexData.col
      }
    };
    
    console.log('Formatted hex data:', formattedHexData);
    
    document.dispatchEvent(new CustomEvent('hexSelected', {
      detail: formattedHexData,
      bubbles: true
    }));
  }

  /**
   * Handle unit selection
   */
  private handleUnitSelection(unitData: any): void {
    console.log('🎮 Handling unit selection:', unitData);
    
    const formattedUnitData = {
      type: unitData.playerData?.name || 'Unknown unit',
      ownerId: unitData.playerData?.id || 'Unknown player',
      movementRange: unitData.playerData?.movementPoints || 0,
      position: {
        x: unitData.playerData?.position?.col || 0,
        y: unitData.playerData?.position?.row || 0
      }
    };
    
    document.dispatchEvent(new CustomEvent('unitSelected', {
      detail: formattedUnitData,
      bubbles: true
    }));
  }

  /**
   * Get current game data
   */
  getCurrentGame(): GameData | null {
    return this.gameManager?.getCurrentGame() || null;
  }

  /**
   * Stimulus disconnect lifecycle method
   */
  disconnect(): void {
    if (this.gameManager) {
      this.gameManager.destroy();
    }
    this.gameManager = null;
  }
}
