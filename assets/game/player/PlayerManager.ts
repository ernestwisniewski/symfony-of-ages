import { Container } from 'pixi.js';
import { Player } from './Player.ts';
import { HexGrid } from '../map/HexGrid.ts';
import { CameraController } from '../map/CameraController.ts';
import type { PlayerData } from './types.ts';
import type { MapConfig } from '../map/types.ts';

/**
 * PlayerManager handles all player-related operations
 * Manages player lifecycle, positioning, and camera integration
 */
export class PlayerManager {
  private hexGrid: HexGrid;
  private cameraController: CameraController;
  private config: MapConfig;
  private player: Player | null = null;

  constructor(hexGrid: HexGrid, cameraController: CameraController, config: MapConfig) {
    this.hexGrid = hexGrid;
    this.cameraController = cameraController;
    this.config = config;
  }

  /**
   * Adds a player to the map
   * @param playerData - Player data from backend
   */
  addPlayer(playerData: PlayerData): void {
    // Remove existing player if any
    this.removePlayer();

    // Create new player sprite
    this.player = new Player(playerData, this.config.size);
    this.hexGrid.addChild(this.player.sprite);

    // Setup player click handling
    this.player.sprite.on('playerclick', (event: any) => {
      this.hexGrid.emit('playerclick', event);
    });

    // Set optimal zoom for player visibility and center camera
    this.cameraController.setOptimalPlayerZoom();
    this.centerCameraOnPlayer();
  }

  /**
   * Updates player position
   */
  updatePlayerPosition(playerData: PlayerData): void {
    if (this.player) {
      this.player.updateData(playerData);
      
      // Always center camera on player after movement
      this.centerCameraOnPlayer();
    }
  }

  /**
   * Removes player from the map
   */
  removePlayer(): void {
    if (this.player) {
      this.hexGrid.removeChild(this.player.sprite);
      this.player.destroy();
      this.player = null;
    }
  }

  /**
   * Gets the current player
   */
  getPlayer(): Player | null {
    return this.player;
  }

  /**
   * Centers camera on player position
   */
  private centerCameraOnPlayer(): void {
    if (!this.player) return;

    const playerWorldPosition = this.calculatePlayerWorldPosition();
    this.cameraController.centerCameraOnPlayer(playerWorldPosition);
  }

  /**
   * Calculates the player's world position considering hexGrid transforms
   */
  private calculatePlayerWorldPosition(): { x: number, y: number } {
    if (!this.player) return { x: 0, y: 0 };

    const playerSprite = this.player.sprite;
    const hexGridWorldX = this.hexGrid.x;
    const hexGridWorldY = this.hexGrid.y;
    
    // Player is now inside hexGrid, so we need to account for hexGrid's transform
    const playerWorldX = hexGridWorldX + (playerSprite.x - this.hexGrid.pivot.x) * this.hexGrid.scale.x;
    const playerWorldY = hexGridWorldY + (playerSprite.y - this.hexGrid.pivot.y) * this.hexGrid.scale.y;
    
    return { x: playerWorldX, y: playerWorldY };
  }

  /**
   * Keeps the player centered on camera
   */
  keepPlayerInView(): void {
    if (!this.player) return;
    this.centerCameraOnPlayer();
  }
} 