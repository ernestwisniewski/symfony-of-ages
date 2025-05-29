import type { SelectableObject } from '../map/SelectionManager.ts';

/**
 * SelectablePlayer represents a player that can be selected
 * Implements SelectableObject interface for player entities
 */
export class SelectablePlayer implements SelectableObject {
  readonly id: string;
  readonly type: string = 'player';
  readonly position: { row: number; col: number };
  readonly displayName: string;
  private playerData: any;

  constructor(playerData: any) {
    this.id = `player_${playerData.id}`;
    this.position = { 
      row: playerData.position.row, 
      col: playerData.position.col 
    };
    this.displayName = playerData.name || 'Nieznany gracz';
    this.playerData = playerData;
  }

  /**
   * Gets selection information for display in the selection panel
   */
  getSelectionInfo(): Record<string, any> {
    return {
      'Punkty ruchu': `${this.playerData.movementPoints || 0}/${this.playerData.maxMovementPoints || 0}`,
      'Kolor': `#${(this.playerData.color || 0).toString(16).padStart(6, '0')}`
    };
  }

  /**
   * Get full player data
   */
  getPlayerData(): any {
    return this.playerData;
  }

  /**
   * Update player data (useful when player moves or attributes change)
   */
  updatePlayerData(playerData: any): void {
    this.playerData = playerData;
    // Update position reference
    (this.position as any).row = playerData.position.row;
    (this.position as any).col = playerData.position.col;
  }
} 