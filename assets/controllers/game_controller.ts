// controllers/hex_map_controller.js
import {Controller} from '@hotwired/stimulus'
import {GameMap} from '../map/GameMap.ts'

/**
 * Interface for map configuration received from the server
 */
interface MapConfig {
  cols: number;
  rows: number;
  size: number;
}

/**
 * Interface for terrain properties
 */
interface TerrainProperties {
  color: number;
  movement: number;
  defense: number;
  resources: number;
}

/**
 * Interface for tile data
 */
interface TileData {
  type: string;
  name: string;
  properties: TerrainProperties;
}

/**
 * Interface for the complete API response
 */
interface MapDataResponse {
  config: MapConfig;
  data: TileData[][];
}

/**
 * Interface for GameMap constructor options
 */
interface GameMapOptions {
  cols: number;
  rows: number;
  size: number;
  mapData: TileData[][];
}

/**
 * Stimulus controller for managing the hexagonal game map
 * Handles initialization, data loading, and lifecycle management of the game map
 * Extends Stimulus Controller to provide seamless integration with Symfony/Twig
 */
export default class extends Controller<HTMLElement> {
  /**
   * Stimulus values configuration for the controller
   * Defines the data attributes that can be passed from HTML/Twig templates
   */
  static values = {
    mapUrl: String
  }

  // Value type declarations for TypeScript
  declare readonly mapUrlValue: string;

  // Game map instance
  private gameMap: GameMap | null = null;

  /**
   * Stimulus connect lifecycle method - called when controller is connected to DOM
   * Fetches map data from the server and initializes the game map
   */
  async connect(): Promise<void> {
    try {
      const response: Response = await fetch(this.mapUrlValue);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const responseData: MapDataResponse = await response.json();

      const options: GameMapOptions = {
        cols: responseData.config.cols,
        rows: responseData.config.rows,
        size: responseData.config.size,
        mapData: responseData.data
      };

      this.gameMap = new GameMap(this.element, options);
    } catch (error) {
      console.error('Error loading map data:', error);
    }
  }

  /**
   * Stimulus disconnect lifecycle method - called when controller is disconnected from DOM
   * Performs cleanup to prevent memory leaks and properly destroy PIXI application
   */
  disconnect(): void {
    // Cleanup when the controller is disconnected
    if (this.gameMap?.app) {
      this.gameMap.app.destroy(true);
    }
    this.gameMap = null;
  }
}
