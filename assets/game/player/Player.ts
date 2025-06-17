import {Container, Graphics} from 'pixi.js';
import {HexGeometry} from '../map/HexGeometry';
import type {PlayerData} from './types';

/**
 * Player class for rendering and managing a player as a simple circle on the hex map
 * Handles player visualization, position updates, and movement animations
 */
export class Player {
  // Visual configuration constants
  private static readonly PLAYER_CIRCLE_RADIUS_RATIO = 0.2;
  private static readonly PLAYER_INNER_RADIUS_RATIO = 0.12;
  private static readonly SHADOW_OFFSET_X = 2;
  private static readonly SHADOW_OFFSET_Y = 2;
  private static readonly SHADOW_ALPHA = 0.3;
  private static readonly BORDER_COLOR = 0xFFFFFF;
  private static readonly BORDER_WIDTH = 3;
  private static readonly INNER_CIRCLE_ALPHA = 0.8;
  private static readonly CROSS_SIZE_RATIO = 0.3;
  private static readonly CROSS_COLOR = 0xFF0000;
  private static readonly CROSS_WIDTH = 2;
  private static readonly CROSS_ALPHA = 0.7;

  // Animation constants
  private static readonly BOUNCE_HEIGHT = 8;
  private static readonly ANIMATION_DURATION = 200;

  private data: PlayerData;
  private hexSize: number;
  private hexGeometry: HexGeometry;
  public sprite: Container;
  private circle: Graphics;
  private shadow: Graphics;

  constructor(playerData: PlayerData, hexSize: number) {
    this.data = playerData;
    this.hexSize = hexSize;
    this.hexGeometry = new HexGeometry(hexSize);

    this.sprite = new Container();
    this.circle = new Graphics();
    this.shadow = new Graphics();

    this.createSprite();
    this.updatePosition(playerData.position);
  }

  /**
   * Create the player sprite with circle and shadow
   */
  private createSprite(): void {
    this.createShadow();
    this.createPlayerCircle();
    this.createDebugCrossMarker();
    this.setupSpriteInteractivity();
  }

  /**
   * Creates the shadow behind the player
   */
  private createShadow(): void {
    this.shadow.circle(Player.SHADOW_OFFSET_X, Player.SHADOW_OFFSET_Y, this.hexSize * Player.PLAYER_CIRCLE_RADIUS_RATIO);
    this.shadow.fill({color: 0x000000, alpha: Player.SHADOW_ALPHA});
    this.sprite.addChild(this.shadow);
  }

  /**
   * Creates the main player circle with border and inner circle
   */
  private createPlayerCircle(): void {
    this.drawPlayerCircleElements(this.data.color);
    this.sprite.addChild(this.circle);
  }

  /**
   * Draws all circle elements for the player
   */
  private drawPlayerCircleElements(color: number): void {
    // Main player circle - made bigger for better visibility
    this.circle.circle(0, 0, this.hexSize * Player.PLAYER_CIRCLE_RADIUS_RATIO);
    this.circle.fill({color: color});

    // Add white border for visibility
    this.circle.circle(0, 0, this.hexSize * Player.PLAYER_CIRCLE_RADIUS_RATIO);
    this.circle.stroke({color: Player.BORDER_COLOR, width: Player.BORDER_WIDTH});

    // Add inner circle for better definition
    this.circle.circle(0, 0, this.hexSize * Player.PLAYER_INNER_RADIUS_RATIO);
    this.circle.fill({color: color, alpha: Player.INNER_CIRCLE_ALPHA});
  }

  /**
   * Creates a debug cross marker for exact center positioning
   */
  private createDebugCrossMarker(): void {
    const crossMarker = new Graphics();
    crossMarker.moveTo(-this.hexSize * Player.CROSS_SIZE_RATIO, 0);
    crossMarker.lineTo(this.hexSize * Player.CROSS_SIZE_RATIO, 0);
    crossMarker.moveTo(0, -this.hexSize * Player.CROSS_SIZE_RATIO);
    crossMarker.lineTo(0, this.hexSize * Player.CROSS_SIZE_RATIO);
    crossMarker.stroke({color: Player.CROSS_COLOR, width: Player.CROSS_WIDTH, alpha: Player.CROSS_ALPHA});
    this.sprite.addChild(crossMarker);
  }

  /**
   * Sets up sprite interactivity
   */
  private setupSpriteInteractivity(): void {
    this.sprite.eventMode = 'static';
    this.sprite.cursor = 'pointer';

    // Add click event
    this.sprite.on('click', () => {
      this.sprite.emit('playerclick', {playerData: this.data});
    });

    // Add hover events for visual feedback
    this.sprite.on('pointerover', () => {
      this.sprite.scale.set(1.1);
    });

    this.sprite.on('pointerout', () => {
      this.sprite.scale.set(1.0);
    });
  }

  /**
   * Update player position on the hex grid
   */
  updatePosition(position: { row: number; col: number }): void {
    this.data.position = position;

    const worldPos = this.hexGeometry.calculatePosition(position.row, position.col);

    // Validate the calculated position
    if (this.isValidWorldPosition(worldPos)) {
      this.setSpritePosition(worldPos);
      this.animateMovement();
    }
  }

  /**
   * Validates if the calculated world position is valid
   */
  private isValidWorldPosition(worldPos: { x: number, y: number }): boolean {
    if (isNaN(worldPos.x) || isNaN(worldPos.y)) {
      return false;
    }
    return true;
  }

  /**
   * Sets the sprite position and logs debugging information
   */
  private setSpritePosition(worldPos: { x: number, y: number }): void {
    // Position the player sprite directly using world coordinates
    // The HexGrid's pivot and scaling will be handled by the parent container
    this.sprite.x = worldPos.x;
    this.sprite.y = worldPos.y; // Remove the 0.8 scaling - let HexGrid handle it
  }

  /**
   * Simple bounce animation when moving
   */
  private animateMovement(): void {
    const originalY = this.sprite.y;
    const duration = Player.ANIMATION_DURATION;
    const startTime = Date.now();

    const animate = (): void => {
      const elapsed = Date.now() - startTime;
      const progress = Math.min(elapsed / duration, 1);

      if (progress < 0.5) {
        // Up phase
        const bounceProgress = progress * 2;
        this.sprite.y = originalY - (Player.BOUNCE_HEIGHT * bounceProgress);
      } else {
        // Down phase
        const bounceProgress = (progress - 0.5) * 2;
        this.sprite.y = originalY - Player.BOUNCE_HEIGHT + (Player.BOUNCE_HEIGHT * bounceProgress);
      }

      if (progress < 1) {
        requestAnimationFrame(animate);
      } else {
        this.sprite.y = originalY;
      }
    };

    animate();
  }

  /**
   * Update player data with new values
   */
  updateData(newData: Partial<PlayerData>): void {
    // Update stored data
    this.data = {...this.data, ...newData};

    // Update position if provided
    if (newData.position) {
      this.updatePosition(newData.position);
    }

    // Update color if provided
    if (newData.color !== undefined) {
      this.updateColor(newData.color);
    }
  }

  /**
   * Updates the player's color
   */
  private updateColor(color: number): void {
    this.circle.clear();
    this.drawPlayerCircleElements(color);
  }

  /**
   * Get current grid position
   */
  getGridPosition(): { row: number; col: number } {
    return {...this.data.position};
  }

  /**
   * Get all player data
   */
  getData(): PlayerData {
    return {...this.data};
  }

  /**
   * Check if player is at specific position
   */
  isAtPosition(row: number, col: number): boolean {
    return this.data.position.row === row && this.data.position.col === col;
  }

  /**
   * Clean up resources
   */
  destroy(): void {
    this.sprite.destroy();
  }
}
