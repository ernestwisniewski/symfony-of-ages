import { Container, Graphics } from 'pixi.js';
import { HexGeometry } from './HexGeometry.ts';
import type { MapConfig } from './types.ts';

/**
 * DebugRenderer handles debug visualization
 * Manages debug markers and visual debugging aids
 */
export class DebugRenderer {
  // Debug marker constants
  private static readonly DEBUG_MARKER_RADIUS = 10;
  private static readonly DEBUG_MARKER_SIZE = 15;
  private static readonly DEBUG_MARKER_WIDTH = 3;
  private static readonly DEBUG_MARKER_ALPHA = 0.8;
  private static readonly DEBUG_HEX_ZERO_COLOR = 0x00FF00;
  private static readonly DEBUG_HEX_CENTER_COLOR = 0x0000FF;

  private worldContainer: Container;
  private config: MapConfig;

  constructor(worldContainer: Container, config: MapConfig) {
    this.worldContainer = worldContainer;
    this.config = config;
  }

  /**
   * Adds debug markers to visualize grid positioning
   */
  addDebugMarkers(): void {
    this.addZeroHexMarker();
    this.addCenterHexMarker();
  }

  /**
   * Adds a debug marker at hex position (0,0)
   */
  private addZeroHexMarker(): void {
    const hexGeometry = new HexGeometry(this.config.size);
    const zeroPos = hexGeometry.calculatePosition(0, 0);
    
    const marker = this.createDebugMarker(DebugRenderer.DEBUG_HEX_ZERO_COLOR);
    marker.position.set(zeroPos.x, zeroPos.y);
    this.worldContainer.addChild(marker);
    
    console.log(`Debug: hex(0,0) marker placed at world position (${zeroPos.x}, ${zeroPos.y})`);
  }

  /**
   * Adds a debug marker at the center hex position
   */
  private addCenterHexMarker(): void {
    const hexGeometry = new HexGeometry(this.config.size);
    const centerRow = Math.floor(this.config.rows / 2);
    const centerCol = Math.floor(this.config.cols / 2);
    const centerPos = hexGeometry.calculatePosition(centerRow, centerCol);
    
    const marker = this.createDebugMarker(DebugRenderer.DEBUG_HEX_CENTER_COLOR);
    marker.position.set(centerPos.x, centerPos.y);
    this.worldContainer.addChild(marker);
    
    console.log(`Debug: hex(${centerRow},${centerCol}) marker placed at world position (${centerPos.x}, ${centerPos.y})`);
  }

  /**
   * Creates a debug marker with specified color
   */
  private createDebugMarker(color: number): Container {
    const marker = new Container();
    const graphics = new Graphics();
    
    graphics.circle(0, 0, DebugRenderer.DEBUG_MARKER_RADIUS);
    graphics.fill({ color: color, alpha: DebugRenderer.DEBUG_MARKER_ALPHA });
    graphics.moveTo(-DebugRenderer.DEBUG_MARKER_SIZE, 0);
    graphics.lineTo(DebugRenderer.DEBUG_MARKER_SIZE, 0);
    graphics.moveTo(0, -DebugRenderer.DEBUG_MARKER_SIZE);
    graphics.lineTo(0, DebugRenderer.DEBUG_MARKER_SIZE);
    graphics.stroke({ color: color, width: DebugRenderer.DEBUG_MARKER_WIDTH });
    
    marker.addChild(graphics);
    return marker;
  }

  /**
   * Removes all debug markers from the container
   */
  removeDebugMarkers(): void {
    // This would remove all debug markers if needed
    // Implementation depends on specific requirements
  }
} 