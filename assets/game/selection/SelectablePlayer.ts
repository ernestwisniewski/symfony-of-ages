import type {PlayerData, SelectableObject} from '../core/types';

/**
 * SelectablePlayer represents a player that can be selected
 * Contains player data and provides methods for accessing player information
 */
export class SelectablePlayer implements SelectableObject {
  readonly type = 'player';
  readonly id: string;
  readonly position: { row: number; col: number };
  readonly displayName: string;

  constructor(
    public readonly playerData: PlayerData
  ) {
    this.id = this.playerData.id;
    this.position = this.playerData.position;
    this.displayName = this.playerData.name;
  }

  /**
   * Get selection info for the selection panel
   */
  getSelectionInfo(): Record<string, any> {
    return {
      name: this.getName(),
      position: this.getPosition(),
      movement: {
        current: this.getMovementPoints(),
        maximum: this.getMaxMovementPoints(),
        canMove: this.canMove()
      },
      color: this.getColor(),
      playerData: this.playerData
    };
  }

  /**
   * Get player ID
   */
  getId(): string {
    return this.playerData.id;
  }

  /**
   * Get player name
   */
  getName(): string {
    return this.playerData.name;
  }

  /**
   * Get player position
   */
  getPosition(): { row: number; col: number } {
    return this.playerData.position;
  }

  /**
   * Get player color
   */
  getColor(): number {
    return this.playerData.color;
  }

  /**
   * Get movement points
   */
  getMovementPoints(): number {
    return this.playerData.movementPoints || 0;
  }

  /**
   * Get max movement points
   */
  getMaxMovementPoints(): number {
    return this.playerData.maxMovementPoints || 0;
  }

  /**
   * Check if player can move
   */
  canMove(): boolean {
    return this.getMovementPoints() > 0;
  }

  /**
   * Get all player data
   */
  getPlayerData(): PlayerData {
    return this.playerData;
  }

  /**
   * Get selection data for UI display
   */
  getSelectionData(): any {
    return {
      type: this.type,
      player: {
        id: this.getId(),
        name: this.getName(),
        position: this.getPosition(),
        color: this.getColor(),
        movementPoints: this.getMovementPoints(),
        maxMovementPoints: this.getMaxMovementPoints(),
        canMove: this.canMove()
      }
    };
  }
}
