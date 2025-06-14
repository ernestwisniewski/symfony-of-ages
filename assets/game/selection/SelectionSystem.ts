import { SelectionManager } from './SelectionManager';
import { SelectableHex } from './SelectableHex';
import { SelectablePlayer } from './SelectablePlayer';
import type { PlayerData, TerrainTile, GridPosition } from '../core/types';

/**
 * SelectionSystem coordinates all selection-related functionality
 * Separates selection concerns into a dedicated module
 */
export class SelectionSystem {
  private selectionManager: SelectionManager;

  constructor() {
    this.selectionManager = new SelectionManager();
  }

  /**
   * Select a hex tile
   */
  selectHex(terrainData: TerrainTile, position: GridPosition): void {
    const selectableHex = new SelectableHex(position.row, position.col, terrainData);
    this.selectionManager.select(selectableHex);
  }

  /**
   * Select a player
   */
  selectPlayer(playerData: PlayerData): void {
    const selectablePlayer = new SelectablePlayer(playerData);
    this.selectionManager.select(selectablePlayer);
  }

  /**
   * Clear selection
   */
  clearSelection(): void {
    this.selectionManager.clearSelection();
  }

  /**
   * Get selection manager for advanced use
   */
  getManager(): SelectionManager {
    return this.selectionManager;
  }

  /**
   * Cleanup resources
   */
  destroy(): void {
    this.selectionManager.clearSelection();
  }
} 