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
  private config: HexGridConfig;
  public popup: HexPopup | null = null; // Will be set by GameMap
  private geometry: HexGeometry;
  private hoverShadow: any[];

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
      color: 0x000000,
      alpha: 0.4,           // More transparent shadow
      blur: 8,
      distance: 6,
      offset: { x: 3, y: 3 },
      quality: 5
    });

    // Set resolution using the modern approach
    shadowFilter.resolution = 2;

    // Create brightness filter for better visibility
    const brightnessFilter = new ColorMatrixFilter();
    brightnessFilter.brightness(1.15, false);

    // Return array of filters to apply both effects
    return [shadowFilter, brightnessFilter];
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

    this.addChild(...hexes);
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
      if (gameMap && !gameMap.isDragging && this.popup) {
        this.popup.show(event.data);
      }
    });
  }

  /**
   * Sets up the grid's pivot point and applies isometric scaling
   * Configures the grid for proper centering and applies visual perspective
   */
  private setupPosition(): void {
    const bounds = this.getBounds();
    this.pivot.set(bounds.width / 2, bounds.height / 2);
    this.scale.y = 0.8; // Apply isometric scaling
  }

  /**
   * Updates the grid position to center it on the screen
   *
   * @param screenWidth - Width of the screen/viewport
   * @param screenHeight - Height of the screen/viewport
   */
  updatePosition(screenWidth: number, screenHeight: number): void {
    this.position.set(screenWidth / 2, screenHeight / 2);
  }

  /**
   * Gets the position of the center hex in world coordinates
   *
   * @returns Position object with x and y coordinates of the center hex
   */
  getCenterHexPosition(): { x: number; y: number } {
    const centerRow = Math.floor(this.config.rows / 2);
    const centerCol = Math.floor(this.config.cols / 2);
    return this.geometry.calculatePosition(centerRow, centerCol);
  }
}
