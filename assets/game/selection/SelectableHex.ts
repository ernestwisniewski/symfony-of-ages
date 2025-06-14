import type { SelectableObject, GridPosition, TerrainTile } from '../core';

/**
 * SelectableHex represents a hex tile that can be selected
 * Contains position and terrain data for the selected hex
 */
export class SelectableHex implements SelectableObject {
    readonly type = 'hex';
    readonly id: string;
    readonly position: GridPosition;
    readonly displayName: string;
    
    constructor(
        public readonly row: number,
        public readonly col: number,
        public readonly terrainData: TerrainTile
    ) {
        this.position = { row, col };
        this.id = `hex_${row}_${col}`;
        this.displayName = this.terrainData?.name || 'Unknown Terrain';
    }

    /**
     * Get selection info for the selection panel
     */
    getSelectionInfo(): Record<string, any> {
        return {
            coordinates: `(${this.row}, ${this.col})`,
            terrain: this.terrainData,
            type: this.getTerrainType(),
            name: this.getTerrainName(),
            properties: this.terrainData?.properties || {}
        };
    }

    /**
     * Get position as object
     */
    getPosition(): GridPosition {
        return { row: this.row, col: this.col };
    }

    /**
     * Get terrain type
     */
    getTerrainType(): string {
        return this.terrainData?.type || 'unknown';
    }

    /**
     * Get terrain name for display
     */
    getTerrainName(): string {
        return this.terrainData?.name || 'Unknown Terrain';
    }

    /**
     * Get all terrain data
     */
    getTerrainData(): TerrainTile {
        return this.terrainData;
    }

    /**
     * Get selection data for UI display
     */
    getSelectionData(): any {
        return {
            type: this.type,
            position: this.getPosition(),
            terrain: this.terrainData
        };
    }
} 