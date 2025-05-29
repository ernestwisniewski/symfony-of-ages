import type { SelectableObject } from './SelectionManager.ts';

/**
 * SelectableHex represents a hexagonal tile that can be selected
 * Implements SelectableObject interface for hex tiles
 */
export class SelectableHex implements SelectableObject {
  readonly id: string;
  readonly type: string = 'hex';
  readonly position: { row: number; col: number };
  readonly displayName: string;
  private terrainData: any;

  constructor(row: number, col: number, terrainData: any) {
    this.id = `hex_${row}_${col}`;
    this.position = { row, col };
    this.displayName = terrainData.name || 'Nieznane pole';
    this.terrainData = terrainData;
  }

  /**
   * Gets selection information for display in the selection panel
   */
  getSelectionInfo(): Record<string, any> {
    // Server sends 'movement' but we also check 'movementCost' for compatibility
    const movementCost = this.terrainData.properties?.movement || 
                        this.terrainData.properties?.movementCost || 0;
    
    return {
      'Nazwa terenu': this.terrainData.name || 'Nieznany',
      'Koszt ruchu': movementCost,
      'Obrona': this.terrainData.properties?.defense || 0,
      'Zasoby': this.terrainData.properties?.resources || 0
    };
  }

  /**
   * Get terrain data
   */
  getTerrainData(): any {
    return this.terrainData;
  }
} 