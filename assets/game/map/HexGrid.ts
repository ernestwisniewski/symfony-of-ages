import {Container} from 'pixi.js';
import {HexTile} from './HexTile';
import {HexGeometry} from './HexGeometry';
import {HexRenderer} from './HexRenderer';

/**
 * Interface for hex grid configuration
 */
interface HexGridConfig {
  size: number;
  rows: number;
  cols: number;
  mapData: any[][];
}

/**
 * HexGrid class manages a collection of hexagonal tiles arranged in a grid
 * Extends PIXI.Container to provide a complete hex grid with interactions and popup system
 * Handles tile creation, positioning, and coordinates game events
 */
export class HexGrid extends Container {
  private static readonly ISOMETRIC_Y_SCALE = 0.8;

  private config: HexGridConfig;
  private geometry: HexGeometry;
  private readonly hoverEffect: any[];

  /**
   * Creates a new HexGrid instance with tiles and interaction system
   *
   * @param config - Configuration object for the hex grid
   */
  constructor(config: HexGridConfig) {
    super();
    this.config = config;
    this.geometry = new HexGeometry(config.size);
    this.hoverEffect = HexRenderer.createHoverEffect();
    this.buildGrid();
    this.setupPosition(); // Initialize grid position and scale
  }

  /**
   * Builds the hex grid by creating and positioning individual hex tiles
   * Creates HexTile instances for each position in the grid and sets up their interactions
   */
  private buildGrid(): void {
    const hexes: HexTile[] = [];

    for (let r = 0; r < this.config.rows; r++) {
      for (let c = 0; c < this.config.cols; c++) {
        const position = this.geometry.calculatePosition(r, c);
        const terrainData = this.config.mapData[r][c];

        // Add coordinates to terrain data for easier access
        const terrainDataWithCoords = {
          ...terrainData,
          coordinates: { row: r, col: c }
        };

        const hex = new HexTile({
          size: this.config.size,
          position,
          hoverEffect: this.hoverEffect,
          terrainData: terrainDataWithCoords
        });

        this.setupHexInteraction(hex);
        hexes.push(hex);
      }
    }

    this.addChild(...hexes);

    // Force bounds recalculation after adding children
    this.getBounds();
  }

  /**
   * Sets up hover and click interactions for a hex tile
   * Configures event handlers for showing/hiding popups and handling user interactions
   *
   * @param hex - The hex tile to set up interactions for
   */
  private setupHexInteraction(hex: HexTile): void {
    hex.on('hexhover', (event: any) => {
      const gameMap = this.parent?.parent as any;
      if (gameMap && !gameMap.isDragging) {
        // this.popup.show(event.data);
      }
    });

    hex.on('hexhoverend', () => {
      // this.popup.hide();
    });

    hex.on('hexclick', (event: any) => {
      const gameMap = this.parent?.parent as any;

      if (gameMap && !gameMap.isDragging) {
        // Extract coordinates and emit event for GameMap to handle
        if (event.data) {
          const { row, col } = this.getHexCoordinates(event);

          // Emit event that GameMap can listen to
          this.emit('hexclick', { row, col, terrainData: event.data });
        }
      }
    });
  }

  /**
   * Gets hex coordinates from event data
   * @param eventData - Event data from hex tile
   * @returns Object with row and col coordinates
   */
  private getHexCoordinates(eventData: any): { row: number; col: number } {
    // Try to get coordinates from terrain data first
    // HexTile emits with { data: terrainData, position: ... }
    const terrainData = eventData.data;
    if (terrainData && terrainData.coordinates) {
      return {
        row: terrainData.coordinates.row,
        col: terrainData.coordinates.col
      };
    }

    // Fallback: calculate from position (less reliable)
    const position = eventData.position || { x: 0, y: 0 };
    const col = Math.round(position.x / (this.config.size * Math.sqrt(3)));
    const row = Math.round(position.y / (this.config.size * 1.5));

    return { row, col };
  }

  /**
   * Sets up the grid's pivot point and applies isometric scaling
   * Configures the grid for proper centering and applies visual perspective
   */
  private setupPosition(): void {
    const bounds = this.getBounds();

    const centerPosition = this.calculateGridCenter(bounds);
    this.pivot.set(centerPosition.x, centerPosition.y);

    // Apply isometric scaling for visual perspective
    this.scale.y = HexGrid.ISOMETRIC_Y_SCALE;
  }

  /**
   * Calculates the center position for the grid
   */
  private calculateGridCenter(bounds: any): { x: number, y: number } {
    if (this.areBoundsEmpty(bounds)) {
      return this.calculateCenterManually();
    } else {
      return this.calculateCenterFromBounds(bounds);
    }
  }

  /**
   * Checks if bounds are empty (width or height is 0)
   */
  private areBoundsEmpty(bounds: any): boolean {
    return bounds.width === 0 || bounds.height === 0;
  }

  /**
   * Calculates center manually when bounds are empty
   */
  private calculateCenterManually(): { x: number, y: number } {
    const cornerPositions = this.getCornerPositions();
    const actualBounds = this.calculateActualBounds(cornerPositions);

    const centerX = (actualBounds.minX + actualBounds.maxX) / 2;
    const centerY = (actualBounds.minY + actualBounds.maxY) / 2;

    return { x: centerX, y: centerY };
  }

  /**
   * Gets positions of corner hexes
   */
  private getCornerPositions() {
    const lastRowIndex = this.config.rows - 1;
    const lastColIndex = this.config.cols - 1;

    return {
      topLeft: this.geometry.calculatePosition(0, 0),
      topRight: this.geometry.calculatePosition(0, lastColIndex),
      bottomLeft: this.geometry.calculatePosition(lastRowIndex, 0),
      bottomRight: this.geometry.calculatePosition(lastRowIndex, lastColIndex)
    };
  }

  /**
   * Calculates actual bounds including hex radius
   */
  private calculateActualBounds(cornerPositions: any) {
    const { topLeft, topRight, bottomLeft, bottomRight } = cornerPositions;

    const minX = Math.min(topLeft.x, topRight.x, bottomLeft.x, bottomRight.x);
    const maxX = Math.max(topLeft.x, topRight.x, bottomLeft.x, bottomRight.x);
    const minY = Math.min(topLeft.y, topRight.y, bottomLeft.y, bottomRight.y);
    const maxY = Math.max(topLeft.y, topRight.y, bottomLeft.y, bottomRight.y);

    // Add hex size to account for hex dimensions (not just center points)
    const hexRadius = this.config.size;

    return {
      minX: minX - hexRadius,
      maxX: maxX + hexRadius,
      minY: minY - hexRadius,
      maxY: maxY + hexRadius
    };
  }

  /**
   * Calculates center from existing bounds
   */
  private calculateCenterFromBounds(bounds: any): { x: number, y: number } {
    const centerX = bounds.x + (bounds.width / 2);
    const centerY = bounds.y + (bounds.height / 2);
    return { x: centerX, y: centerY };
  }
}
