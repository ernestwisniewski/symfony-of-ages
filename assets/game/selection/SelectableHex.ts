import type { SelectableObject } from './SelectionManager';

/**
 * SelectableHex represents a hex tile that can be selected
 * Contains position and terrain data for the selected hex
 */
export class SelectableHex implements SelectableObject {
    readonly type = 'hex';
    readonly id: string;
    readonly position: { row: number; col: number };
    readonly displayName: string;
    
    constructor(
        public readonly row: number,
        public readonly col: number,
        public readonly terrainData: any
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
    getPosition(): { row: number; col: number } {
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
    getTerrainData(): any {
        return this.terrainData;
    }

    /**
     * Get selection data for UI display
     */
    getSelectionData(): any {
        return {
            type: this.type,
            position: this.getPosition(),
            terrain: {
                type: this.getTerrainType(),
                name: this.getTerrainName(),
                ...this.terrainData
            }
        };
    }
} 