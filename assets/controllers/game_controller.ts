// controllers/game_controller.ts
import {Controller} from '@hotwired/stimulus'
import {GameManager} from '../game/GameManager.ts'
import type { PlayerData } from '../game/player/types.ts'

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
 * Interface for GameManager constructor options
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
 * Uses GameManager for better separation of concerns
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

  // Game manager instance
  private gameManager: GameManager | null = null;
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
   * Initialize the game map using GameManager
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

    this.gameManager = new GameManager(this.element, options);
    await this.gameManager.init();
    
    // Add event listener for hex clicks to handle player movement
    this.element.addEventListener('hexclick', (event: any) => {
      if (event.detail && event.detail.row !== undefined && event.detail.col !== undefined) {
        this.handlePlayerMovement(event.detail.row, event.detail.col);
      }
    });
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
        if (this.gameManager) {
          this.gameManager.addPlayer(this.player);
        }
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
  private async handlePlayerMovement(row: number, col: number): Promise<void> {
    if (!this.player) {
      return;
    }

    if (this.player.movementPoints <= 0) {
      return;
    }

    // Check if the target tile is adjacent
    const currentPos = this.player.position;
    const distance = this.calculateHexDistance(currentPos.row, currentPos.col, row, col);
    
    if (distance > 1) {
      return;
    }

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
        this.player = result.player;
        
        // Update game map - this will center camera on player
        if (this.gameManager) {
          this.gameManager.updatePlayerPosition(this.player);
        }
      }
    } catch (error) {
      console.error('Error moving player:', error);
    }
  }

  /**
   * Stimulus disconnect lifecycle method
   */
  disconnect(): void {
    if (this.gameManager) {
      this.gameManager.destroy();
    }
    this.gameManager = null;
    this.player = null;
  }
}
