import {Graphics, Texture, Assets} from 'pixi.js';
import {Color} from 'pixi.js';
import {getTerrainTexture} from './TerrainTextures';
import type { TerrainTile } from '../core';

/**
 * Interface for hex tile configuration
 */
interface HexTileConfig {
  size: number;
  position: { x: number; y: number };
  hoverEffect: any[];
  terrainData: TerrainTile;
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
  // Visual configuration constants
  private static readonly COLOR_BLEND_INTENSITY = 0.05;
  private static readonly EDGE_COLOR = 0xE0E0E0;
  private static readonly STROKE_WIDTH = 2;
  private static readonly INNER_STROKE_WIDTH = 1;
  private static readonly STROKE_ALPHA = 0.7;
  private static readonly INNER_ALPHA = 0.3;
  private static readonly INNER_HEX_SCALE = 0.92;
  private static readonly HOVER_TEXTURE_ALPHA = 0.9;
  private static readonly HOVER_MULTIPLIER = 0.96;
  private static readonly HEX_ANGLE_OFFSET = 30;
  private static readonly HEX_ANGLE_STEP = 60;
  private static readonly HEX_VERTEX_COUNT = 6;
  private static readonly DEFAULT_TERRAIN_COLOR = 0xF8F8F8;

  private size: number;
  private hoverEffect: any[];
  private terrainData: TerrainTile;
  private terrainTexture: Texture | null = null;
  private defaultState: DefaultState;

  /**
   * Creates a new HexTile instance
   *
   * @param config - Configuration object for the hex tile
   */
  constructor({size, position, hoverEffect, terrainData}: HexTileConfig) {
    super();
    this.size = size;
    this.hoverEffect = hoverEffect;
    this.terrainData = terrainData;

    // Get base color from terrain properties for fallback
    const terrainColor = terrainData.properties?.color || HexTile.DEFAULT_TERRAIN_COLOR;
    const baseColor = this.blendWithWhite(terrainColor, HexTile.COLOR_BLEND_INTENSITY);

    this.defaultState = {
      fillColor: baseColor,
      edgeColor: HexTile.EDGE_COLOR,
      strokeWidth: HexTile.STROKE_WIDTH,
      innerStrokeWidth: HexTile.INNER_STROKE_WIDTH,
      strokeAlpha: HexTile.STROKE_ALPHA,
      innerAlpha: HexTile.INNER_ALPHA
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
    const innerPoints = this.getPoints(HexTile.INNER_HEX_SCALE);

    this.drawOuterHex(points, isHovered);
    this.drawInnerHex(innerPoints);
  }

  /**
   * Draws the outer hexagon with stroke and fill
   */
  private drawOuterHex(points: number[], isHovered: boolean): void {
    this.drawHexOutline(points, this.defaultState.strokeWidth, this.defaultState.strokeAlpha);
    this.fillHex(isHovered);
  }

  /**
   * Draws the inner hexagon with lighter stroke
   */
  private drawInnerHex(innerPoints: number[]): void {
    this.drawHexOutline(innerPoints, this.defaultState.innerStrokeWidth, this.defaultState.innerAlpha);
  }

  /**
   * Draws hexagon outline with specified stroke properties
   */
  private drawHexOutline(points: number[], strokeWidth: number, strokeAlpha: number): void {
    this.setStrokeStyle({
      width: strokeWidth,
      color: this.defaultState.edgeColor,
      alpha: strokeAlpha
    });

    this.beginPath();
    this.moveTo(points[0], points[1]);
    for (let i = 2; i < points.length; i += 2) {
      this.lineTo(points[i], points[i + 1]);
    }
    this.lineTo(points[0], points[1]);
    this.closePath();
    this.stroke();
  }

  /**
   * Fills the hexagon with texture or color
   */
  private fillHex(isHovered: boolean): void {
    if (this.terrainTexture) {
      this.fillWithTexture(isHovered);
    } else {
      this.fillWithColor(isHovered);
    }
  }

  /**
   * Fills hexagon with terrain texture
   */
  private fillWithTexture(isHovered: boolean): void {
    this.fill({
      texture: this.terrainTexture!,
      alpha: isHovered ? HexTile.HOVER_TEXTURE_ALPHA : 1.0
    });
  }

  /**
   * Fills hexagon with solid color (fallback when no texture available)
   */
  private fillWithColor(isHovered: boolean): void {
    const fillColor = isHovered
      ? new Color(this.defaultState.fillColor).multiply([HexTile.HOVER_MULTIPLIER, HexTile.HOVER_MULTIPLIER, HexTile.HOVER_MULTIPLIER]).toNumber()
      : this.defaultState.fillColor;

    this.fill({ color: fillColor });
  }

  /**
   * Calculates the points for drawing a hexagon
   *
   * @param scale - Scale factor for the hexagon size (1 = full size, 0.92 = inner hex)
   * @returns Array of x,y coordinates for the hexagon vertices
   */
  private getPoints(scale: number = 1): number[] {
    const points: number[] = [];
    for (let i = 0; i < HexTile.HEX_VERTEX_COUNT; i++) {
      const angle = (HexTile.HEX_ANGLE_STEP * i + HexTile.HEX_ANGLE_OFFSET) * Math.PI / 180;
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
      const gameMap = this.parent?.parent?.parent as any;
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
    this.filters = this.hoverEffect;
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
    const gameMap = this.parent?.parent?.parent as any;
    if (!gameMap || !gameMap.isDragging) {
      this.emit('hexclick', {
        data: this.terrainData,
        position: this.getGlobalPosition()
      });
    }
  }
}
