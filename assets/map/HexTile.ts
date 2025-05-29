import {Graphics, Texture, Assets} from 'pixi.js';
import {Color} from 'pixi.js';
import {getTerrainTexture} from './TerrainTextures.ts';

/**
 * Interface for hex tile configuration
 */
interface HexTileConfig {
  size: number;
  position: { x: number; y: number };
  hoverShadow: any[];
  terrainData: any;
}

/**
 * Interface for default state configuration
 */
interface DefaultState {
  fillColor: number;
  edgeColor: number;
  strokeWidth: number;
  innerStrokeWidth: number;
  strokeAlpha: number;
  innerAlpha: number;
}

/**
 * HexTile class representing a single hexagonal tile on the game map
 * Extends PIXI.Graphics to provide interactive hexagonal terrain tiles with textures
 */
export class HexTile extends Graphics {
  private size: number;
  private hoverShadow: any[];
  private terrainData: any;
  private terrainTexture: Texture | null = null;
  private defaultState: DefaultState;

  /**
   * Creates a new HexTile instance
   *
   * @param config - Configuration object for the hex tile
   */
  constructor({size, position, hoverShadow, terrainData}: HexTileConfig) {
    super();
    this.size = size;
    this.hoverShadow = hoverShadow;
    this.terrainData = terrainData;

    // Get base color from terrain properties for fallback
    const terrainColor = terrainData.properties?.color || 0xF8F8F8;
    const baseColor = this.blendWithWhite(terrainColor, 0.05); // Very subtle 5% terrain color

    this.defaultState = {
      fillColor: baseColor,
      edgeColor: 0xE0E0E0,    // Slightly darker for edges
      strokeWidth: 2,
      innerStrokeWidth: 1,
      strokeAlpha: 0.7,       // Semi-transparent outer border
      innerAlpha: 0.3         // More transparent inner border
    };

    this.loadTexture();
    this.setupInteractivity();
    this.position.set(position.x, position.y);
  }

  /**
   * Blends a terrain color with white for very subtle color hints
   *
   * @param terrainColor - The terrain color from properties
   * @param intensity - How much of the terrain color to mix (0.0 to 1.0)
   * @returns The blended color as a hex number
   */
  private blendWithWhite(terrainColor: number, intensity: number): number {
    // Extract RGB components from terrain color
    const r = (terrainColor >> 16) & 0xFF;
    const g = (terrainColor >> 8) & 0xFF;
    const b = terrainColor & 0xFF;

    // Blend with white (255, 255, 255) using the intensity
    const white = 255;
    const blendedR = Math.round(white * (1 - intensity) + r * intensity);
    const blendedG = Math.round(white * (1 - intensity) + g * intensity);
    const blendedB = Math.round(white * (1 - intensity) + b * intensity);

    // Convert back to hex
    return (blendedR << 16) | (blendedG << 8) | blendedB;
  }

  /**
   * Loads the terrain texture for this hex tile
   * Uses the TerrainTextures system with Vite-managed assets
   */
  private async loadTexture(): Promise<void> {
    try {
      const textureUrl = getTerrainTexture(this.terrainData.type);
      if (textureUrl) {
        // Load texture using the Vite-managed URL
        this.terrainTexture = await Assets.load(textureUrl);
        this.draw(); // Redraw with texture
      } else {
        console.warn(`No texture found for terrain type: ${this.terrainData.type}`);
        this.draw(); // Draw with solid color fallback
      }
    } catch (error) {
      console.warn(`Failed to load texture for ${this.terrainData.type}:`, error);
      this.draw(); // Draw with solid color fallback
    }
  }

  /**
   * Draws the hexagonal tile with optional hover state and terrain texture
   * Creates a double-layered hex with outer edge and inner detail, using texture if available
   *
   * @param isHovered - Whether the tile is currently being hovered
   */
  private draw(isHovered: boolean = false): void {
    this.clear();

    const points = this.getPoints();
    const innerPoints = this.getPoints(0.92);

    // Draw outer edge (darker)
    this.setStrokeStyle({
      width: this.defaultState.strokeWidth,
      color: this.defaultState.edgeColor,
      alpha: this.defaultState.strokeAlpha
    });

    // Draw main hex outline
    this.beginPath();
    this.moveTo(points[0], points[1]);
    for (let i = 2; i < points.length; i += 2) {
      this.lineTo(points[i], points[i + 1]);
    }
    this.lineTo(points[0], points[1]);
    this.closePath();
    this.stroke();

    // Fill with texture or fallback color
    if (this.terrainTexture) {
      // Use texture fill
      this.fill({
        texture: this.terrainTexture,
        alpha: isHovered ? 0.9 : 1.0 // More subtle transparency when hovered
      });
    } else {
      // Fill with base color - darker when hovered
      const fillColor = isHovered
        ? new Color(this.defaultState.fillColor).multiply([0.96, 0.96, 0.96]).toNumber() // Darker when hovered
        : this.defaultState.fillColor;

      this.fill({ color: fillColor });
    }

    // Draw inner hex (same style whether hovered or not)
    this.setStrokeStyle({
      width: this.defaultState.innerStrokeWidth,
      color: this.defaultState.edgeColor,
      alpha: this.defaultState.innerAlpha
    });

    this.beginPath();
    this.moveTo(innerPoints[0], innerPoints[1]);
    for (let i = 2; i < innerPoints.length; i += 2) {
      this.lineTo(innerPoints[i], innerPoints[i + 1]);
    }
    this.lineTo(innerPoints[0], innerPoints[1]);
    this.closePath();
    this.stroke();
  }

  /**
   * Calculates the points for drawing a hexagon
   *
   * @param scale - Scale factor for the hexagon size (1 = full size, 0.92 = inner hex)
   * @returns Array of x,y coordinates for the hexagon vertices
   */
  private getPoints(scale: number = 1): number[] {
    const points: number[] = [];
    for (let i = 0; i < 6; i++) {
      const angle = (60 * i + 30) * Math.PI / 180;
      points.push(
        this.size * scale * Math.cos(angle),
        this.size * scale * Math.sin(angle)
      );
    }
    return points;
  }

  /**
   * Sets up interactive event handlers for the hex tile
   * Handles mouse hover, click events and prevents interaction during map dragging
   */
  private setupInteractivity(): void {
    this.eventMode = 'static';
    this.cursor = 'pointer';

    this.on('pointerover', () => {
      // Check if map is not currently being dragged before triggering hover
      const gameMap = (this.parent as any)?.parent;
      if (!gameMap || !gameMap.isDragging) {
        this.onHoverStart();
        this.emit('hexhover', {
          data: this.terrainData,
          position: this.getGlobalPosition()
        });
      }
    });

    this.on('pointerout', () => {
      this.onHoverEnd();
      this.emit('hexhoverend');
    });

    this.on('click', this.onClick.bind(this));
  }

  /**
   * Handles the start of hover interaction
   * Applies hover visual effects and shadow filters
   */
  private onHoverStart(): void {
    this.filters = this.hoverShadow;
    this.draw(true);
  }

  /**
   * Handles the end of hover interaction
   * Removes hover visual effects and returns to normal state
   */
  private onHoverEnd(): void {
    this.filters = [];
    this.draw(false);
  }

  /**
   * Handles click events on the hex tile
   * Emits click event with terrain data for parent components to handle
   */
  private onClick(): void {
    // Check if map is not currently being dragged before triggering click
    const gameMap = (this.parent as any)?.parent;
    if (!gameMap || !gameMap.isDragging) {
      this.emit('hexclick', {
        data: this.terrainData,
        position: this.getGlobalPosition()
      });
    }
  }
}
