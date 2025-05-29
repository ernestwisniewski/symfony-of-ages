// controllers/game_controller.ts
import {Controller} from '@hotwired/stimulus'
import {GameMap} from '../map/GameMap.ts'
import type { PlayerData } from '../player/types.ts'

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
 * Interface for API response
 */
interface ApiResponse {
  success: boolean;
  message?: string;
  player?: PlayerData;
  remainingMovement?: number;
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
 * Stimulus controller for managing the hexagonal game map with player
 * Handles initialization, data loading, player creation, and movement
 */
export default class extends Controller<HTMLElement> {
  /**
   * Stimulus values configuration for the controller
   */
  static values = {
    mapUrl: String
  }

  // Value type declarations for TypeScript
  declare readonly mapUrlValue: string;

  // Game map and player instances
  private gameMap: GameMap | null = null;
  private player: PlayerData | null = null;

  /**
   * Stimulus connect lifecycle method - called when controller is connected to DOM
   */
  async connect(): Promise<void> {
    try {
      await this.initializeGame();
      // Add a small delay to ensure map is fully rendered before adding player
      setTimeout(async () => {
        await this.createPlayer();
      }, 200);
    } catch (error) {
      console.error('Error initializing game:', error);
    }
  }

  /**
   * Initialize the game map
   */
  private async initializeGame(): Promise<void> {
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
    
    // Override the onHexClick method to handle player movement
    this.gameMap.onHexClick = this.handleHexClick.bind(this);
    
    console.log('Game map initialized successfully');
  }

  /**
   * Create a new player
   */
  private async createPlayer(): Promise<void> {
    try {
      const response = await fetch('/api/player/create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          name: 'Hero'
        })
      });

      const result: ApiResponse = await response.json();
      
      if (result.success && result.player) {
        this.player = result.player;
        
        // Add player to game map
        if (this.gameMap) {
          this.gameMap.addPlayer(this.player);
        }
        
        console.log('Player created:', result.message);
      } else {
        console.error('Failed to create player:', result.message);
      }
    } catch (error) {
      console.error('Error creating player:', error);
    }
  }

  /**
   * Handle hex tile clicks for player movement
   */
  private async handleHexClick(row: number, col: number): Promise<void> {
    if (!this.player) {
      console.log('No player available');
      return;
    }

    if (this.player.movementPoints <= 0) {
      console.log('No movement points remaining');
      return;
    }

    // Check if the target tile is adjacent
    const currentPos = this.player.position;
    const distance = this.calculateHexDistance(currentPos.row, currentPos.col, row, col);
    
    if (distance > 1) {
      console.log('Can only move to adjacent hexes');
      return;
    }

    console.log(`Attempting to move from (${currentPos.row}, ${currentPos.col}) to (${row}, ${col})`);
    await this.movePlayer(row, col);
  }

  /**
   * Calculate distance between two hex coordinates
   */
  private calculateHexDistance(row1: number, col1: number, row2: number, col2: number): number {
    const dx = col1 - col2;
    const dy = row1 - row2;
    
    // Adjust for hexagonal coordinate system
    let adjustedDx = dx;
    if ((row1 % 2) !== (row2 % 2)) {
      if (row1 % 2 === 0) {
        adjustedDx += 0.5;
      } else {
        adjustedDx -= 0.5;
      }
    }
    
    return Math.max(Math.abs(adjustedDx), Math.abs(dy), Math.abs(adjustedDx + dy));
  }

  /**
   * Move player to specified coordinates
   */
  private async movePlayer(row: number, col: number): Promise<void> {
    try {
      const response = await fetch('/api/player/move', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ row, col })
      });

      const result: ApiResponse = await response.json();
      
      if (result.success && result.player) {
        // Update local player data
        const oldPosition = this.player ? this.player.position : null;
        this.player = result.player;
        
        console.log(`Player moved from (${oldPosition?.row}, ${oldPosition?.col}) to (${this.player.position.row}, ${this.player.position.col})`);
        console.log(`Movement points: ${this.player.movementPoints}/${this.player.maxMovementPoints}`);
        
        // Update game map - this will center camera on player
        if (this.gameMap) {
          this.gameMap.updatePlayerPosition(this.player);
        }
        
        console.log('Player moved successfully:', result.message);
      } else {
        console.log('Movement failed:', result.message);
      }
    } catch (error) {
      console.error('Error moving player:', error);
    }
  }

  /**
   * Stimulus disconnect lifecycle method
   */
  disconnect(): void {
    if (this.gameMap?.app) {
      this.gameMap.app.destroy(true);
    }
    this.gameMap = null;
    this.player = null;
  }
}
