import {Container, ColorMatrixFilter} from 'pixi.js';
import {DropShadowFilter} from '@pixi/filter-drop-shadow';
import {HexTile} from './HexTile.ts';
import {HexGeometry} from './HexGeometry.ts';
import {HexPopup} from './HexPopup.ts';

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
 * Handles tile creation, positioning, hover effects, and popup management
 */
export class HexGrid extends Container {
  // Shadow effect configuration constants
  private static readonly SHADOW_COLOR = 0x000000;
  private static readonly SHADOW_ALPHA = 0.4;
  private static readonly SHADOW_BLUR = 8;
  private static readonly SHADOW_DISTANCE = 6;
  private static readonly SHADOW_OFFSET_X = 3;
  private static readonly SHADOW_OFFSET_Y = 3;
  private static readonly SHADOW_QUALITY = 5;
  private static readonly SHADOW_RESOLUTION = 2;
  private static readonly BRIGHTNESS_MULTIPLIER = 1.15;
  private static readonly ISOMETRIC_Y_SCALE = 0.8;
  
  private config: HexGridConfig;
  public popup: HexPopup | null = null; // Will be set by GameMap
  private geometry: HexGeometry;
  private readonly hoverShadow: any[];

  /**
   * Creates a new HexGrid instance with tiles and interaction system
   *
   * @param config - Configuration object for the hex grid
   */
  constructor(config: HexGridConfig) {
    super();
    this.config = config;
    this.geometry = new HexGeometry(config.size);
    this.hoverShadow = this.createHoverShadow();
    this.buildGrid();
    this.setupPosition(); // Initialize grid position and scale
  }

  /**
   * Creates a combined filter for hex hover effect with shadow and brightness
   * Combines drop shadow and brightness filters for enhanced visual feedback
   *
   * @returns Array of PIXI filters to apply for hover effects
   */
  private createHoverShadow(): any[] {
    // Create drop shadow filter
    const shadowFilter = new DropShadowFilter({
      color: HexGrid.SHADOW_COLOR,
      alpha: HexGrid.SHADOW_ALPHA,           // More transparent shadow
      blur: HexGrid.SHADOW_BLUR,
      distance: HexGrid.SHADOW_DISTANCE,
      offset: { x: HexGrid.SHADOW_OFFSET_X, y: HexGrid.SHADOW_OFFSET_Y },
      quality: HexGrid.SHADOW_QUALITY
    });

    // Set resolution using the modern approach
    shadowFilter.resolution = HexGrid.SHADOW_RESOLUTION;

    // Create brightness filter for better visibility
    const brightnessFilter = new ColorMatrixFilter();
    brightnessFilter.brightness(HexGrid.BRIGHTNESS_MULTIPLIER, false);

    // Return array of filters to apply both effects
    return [shadowFilter, brightnessFilter];
  }

  /**
   * Builds the hex grid by creating and positioning individual hex tiles
   * Creates HexTile instances for each position in the grid and sets up their interactions
   */
  private buildGrid(): void {
    const hexes: HexTile[] = [];

    console.log(`Building grid: ${this.config.rows} x ${this.config.cols} hexes`);

    for (let r = 0; r < this.config.rows; r++) {
      for (let c = 0; c < this.config.cols; c++) {
        const position = this.geometry.calculatePosition(r, c);
        const terrainData = this.config.mapData[r][c];

        const hex = new HexTile({
          size: this.config.size,
          position,
          hoverShadow: this.hoverShadow,
          terrainData
        });

        this.setupHexInteraction(hex);
        hexes.push(hex);
      }
    }

    console.log(`Created ${hexes.length} hex tiles, adding to container...`);
    this.addChild(...hexes);
    console.log(`Container now has ${this.children.length} children`);
    
    // Force bounds recalculation after adding children
    const testBounds = this.getBounds();
    console.log(`Bounds after adding children: x=${testBounds.x}, y=${testBounds.y}, w=${testBounds.width}, h=${testBounds.height}`);
  }

  /**
   * Sets up hover and click interactions for a hex tile
   * Configures event handlers for showing/hiding popups and handling user interactions
   *
   * @param hex - The hex tile to set up interactions for
   */
  private setupHexInteraction(hex: HexTile): void {
    hex.on('hexhover', (event: any) => {
      const gameMap = this.parent as any;
      if (gameMap && !gameMap.isDragging && this.popup) {
        this.popup.show(event.data);
      }
    });

    hex.on('hexhoverend', () => {
      if (this.popup) {
        this.popup.hide();
      }
    });

    hex.on('hexclick', (event: any) => {
      const gameMap = this.parent as any;
      if (gameMap && !gameMap.isDragging) {
        // Show popup if available
        if (this.popup) {
          this.popup.show(event.data);
        }
        
        // Pass click to GameMap with coordinates
        if (gameMap.onHexClick && event.data.terrainData) {
          // Extract row and col from terrain data or calculate from position
          const { row, col } = this.getHexCoordinates(event.data);
          gameMap.onHexClick(row, col);
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
    if (eventData.terrainData && eventData.terrainData.coordinates) {
      return {
        row: eventData.terrainData.coordinates.row,
        col: eventData.terrainData.coordinates.col
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
    console.log('HexGrid setupPosition - raw bounds:', bounds);
    
    const centerPosition = this.calculateGridCenter(bounds);
    this.pivot.set(centerPosition.x, centerPosition.y);
    
    // Apply isometric scaling for visual perspective
    this.scale.y = HexGrid.ISOMETRIC_Y_SCALE;
    
    console.log(`HexGrid setupPosition - pivot set to: (${this.pivot.x}, ${this.pivot.y})`);
    console.log(`HexGrid setupPosition - scale set to: (${this.scale.x}, ${this.scale.y})`);
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
    console.log('Bounds are empty, calculating center manually...');
    
    const cornerPositions = this.getCornerPositions();
    const actualBounds = this.calculateActualBounds(cornerPositions);
    
    const centerX = (actualBounds.minX + actualBounds.maxX) / 2;
    const centerY = (actualBounds.minY + actualBounds.maxY) / 2;
    
    console.log(`Manual calculation: bounds from (${actualBounds.minX}, ${actualBounds.minY}) to (${actualBounds.maxX}, ${actualBounds.maxY})`);
    console.log(`Manual center: (${centerX}, ${centerY})`);
    
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
    console.log(`Using getBounds center: (${centerX}, ${centerY})`);
    return { x: centerX, y: centerY };
  }
}
