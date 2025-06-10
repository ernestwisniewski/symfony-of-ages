import { SelectionManager } from './SelectionManager';
import { SelectableHex } from './SelectableHex';
import { SelectablePlayer } from './SelectablePlayer';
import { SelectionPanel } from '../ui/SelectionPanel';
import type { PlayerData } from '../player/types';

/**
 * SelectionSystem coordinates all selection-related functionality
 * Separates selection concerns into a dedicated module
 */
export class SelectionSystem {
  private selectionManager: SelectionManager;
  private selectionPanel: SelectionPanel;

  constructor() {
    this.selectionManager = new SelectionManager();
    this.selectionPanel = new SelectionPanel();
    this.setupConnections();
  }

  /**
   * Setup connections between manager and panel
   */
  private setupConnections(): void {
    // Connect selection manager to selection panel
    this.selectionManager.onSelectionChange((data) => {
      this.selectionPanel.onSelectionChange(data);
    });
    
    // Handle clear selection events from panel
    this.selectionPanel.getElement().addEventListener('clearSelection', () => {
      this.selectionManager.clearSelection();
    });
  }

  /**
   * Select a hex tile
   */
  selectHex(terrainData: any, position: { row: number; col: number }): void {
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
   * Get selection panel for advanced use
   */
  getPanel(): SelectionPanel {
    return this.selectionPanel;
  }

  /**
   * Cleanup resources
   */
  destroy(): void {
    this.selectionPanel.destroy();
    this.selectionManager.clearSelection();
  }
} 