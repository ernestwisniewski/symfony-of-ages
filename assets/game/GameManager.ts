import { GameMap } from './map/GameMap';
import { SelectionSystem } from './selection/SelectionSystem';
import type { PlayerData } from './player/types';
import type { MapConfig } from './map/types';

/**
 * GameManager coordinates between all game systems
 * Separates concerns from GameMap and manages high-level game logic
 */
export class GameManager {
  private gameMap: GameMap;
  private selectionSystem: SelectionSystem;

  constructor(element: HTMLElement, config: MapConfig) {
    this.gameMap = new GameMap(element, config);
    this.selectionSystem = new SelectionSystem();
    
    this.setupEventHandlers();
  }

  /**
   * Initialize all game systems
   */
  async init(): Promise<void> {
    await this.gameMap.init();
  }

  /**
   * Setup event handlers between game systems
   */
  private setupEventHandlers(): void {
    // Handle hex clicks for selection
    this.gameMap.onHexClick = (row: number, col: number, terrainData: any) => {
      this.selectionSystem.selectHex(terrainData, { row, col });
    };

    // Handle player clicks for selection
    this.gameMap.onPlayerClick = (playerData: PlayerData) => {
      this.selectionSystem.selectPlayer(playerData);
    };
  }

  /**
   * Add player to the game
   */
  addPlayer(playerData: PlayerData): void {
    this.gameMap.addPlayer(playerData);
  }

  /**
   * Update player position
   */
  updatePlayerPosition(playerData: PlayerData): void {
    this.gameMap.updatePlayerPosition(playerData);
  }

  /**
   * Remove player from the game
   */
  removePlayer(): void {
    this.gameMap.removePlayer();
  }

  /**
   * Get current player
   */
  getPlayer(): any {
    return this.gameMap.getPlayer();
  }

  /**
   * Check if currently dragging
   */
  get isDragging(): boolean {
    return this.gameMap.isDragging;
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