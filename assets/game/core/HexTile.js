import {Graphics, Texture, Assets} from 'pixi.js';
import {Color} from 'pixi.js';
import {getTerrainTexture} from './TerrainTextures.js';

/**
 * HexTile class representing a single hexagonal tile on the game map
 * Extends PIXI.Graphics to provide interactive hexagonal terrain tiles with textures
 */
export class HexTile extends Graphics {
  /**
   * Creates a new HexTile instance
   *
   * @param {Object} config - Configuration object for the hex tile
   * @param {number} config.size - Size (radius) of the hexagon
   * @param {Object} config.position - Position object with x and y coordinates
   * @param {number} config.position.x - X coordinate for tile positioning
   * @param {number} config.position.y - Y coordinate for tile positioning
   * @param {Object} config.hoverShadow - Shadow filter for hover effects
   * @param {Object} config.terrainData - Terrain data object containing type and properties
   * @param {string} config.terrainData.type - Type of terrain (plains, forest, mountain, etc.)
   */
  constructor({size, position, hoverShadow, terrainData}) {
    super();
    this.size = size;
    this.hoverShadow = hoverShadow;
    this.terrainData = terrainData;
    this.texture = null;

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
   * @param {number} terrainColor - The terrain color from properties
   * @param {number} intensity - How much of the terrain color to mix (0.0 to 1.0)
   * @returns {number} The blended color as a hex number
   */
  blendWithWhite(terrainColor, intensity) {
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
   * Uses the TerrainTextures system for Vite-managed assets
   */
  async loadTexture() {
    try {
      const textureUrl = getTerrainTexture(this.terrainData.type);
      if (textureUrl) {
        // Load texture using the Vite-managed URL
        this.texture = await Assets.load(textureUrl);
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
   * @param {boolean} [isHovered=false] - Whether the tile is currently being hovered
   */
  draw(isHovered = false) {
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
    if (this.texture) {
      // Use texture fill
      this.fill({
        texture: this.texture,
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
   * @param {number} [scale=1] - Scale factor for the hexagon size (1 = full size, 0.92 = inner hex)
   * @returns {number[]} Array of x,y coordinates for the hexagon vertices
   */
  getPoints(scale = 1) {
    const points = [];
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
  setupInteractivity() {
    this.eventMode = 'static';
    this.cursor = 'pointer';

    this.on('pointerover', () => {
      // Check if map is not currently being dragged before triggering hover
      const gameMap = this.parent?.parent;
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
   * Redraws the tile with hover styling
   */
  onHoverStart() {
    this.draw(true);
  }

  /**
   * Handles the end of hover interaction
   * Redraws the tile with normal styling
   */
  onHoverEnd() {
    this.draw(false);
  }

  /**
   * Handles click events on the hex tile
   * Emits a hexclick event with terrain data and position
   */
  onClick() {
    this.emit('hexclick', {
      data: this.terrainData,
      position: this.getGlobalPosition()
    });
  }
}
