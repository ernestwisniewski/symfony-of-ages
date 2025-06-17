import {Container, Graphics} from 'pixi.js';
import {HexGeometry} from './HexGeometry';
import type {MapConfig} from './types';

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
  private hexGeometry: HexGeometry;

  constructor(worldContainer: Container, config: MapConfig) {
    this.worldContainer = worldContainer;
    this.config = config;
    this.hexGeometry = new HexGeometry(this.config.size);
  }

  /**
   * Add debug markers to the map
   */
  addDebugMarkers(): void {
    // Mark hex (0,0) position
    const zeroPos = this.hexGeometry.calculatePosition(0, 0);
    const zeroMarker = this.createMarker(0xFF0000, 10); // Red marker
    zeroMarker.position.set(zeroPos.x, zeroPos.y);
    this.worldContainer.addChild(zeroMarker);

    // Mark center hex position
    const centerRow = Math.floor(this.config.rows / 2);
    const centerCol = Math.floor(this.config.cols / 2);
    const centerPos = this.hexGeometry.calculatePosition(centerRow, centerCol);
    const centerMarker = this.createMarker(0x00FF00, 8); // Green marker
    centerMarker.position.set(centerPos.x, centerPos.y);
    this.worldContainer.addChild(centerMarker);
  }

  /**
   * Creates a debug marker with specified color
   */
  private createMarker(color: number, radius: number): Container {
    const marker = new Container();
    const graphics = new Graphics();

    graphics.circle(0, 0, radius);
    graphics.fill({color: color, alpha: DebugRenderer.DEBUG_MARKER_ALPHA});
    graphics.moveTo(-DebugRenderer.DEBUG_MARKER_SIZE, 0);
    graphics.lineTo(DebugRenderer.DEBUG_MARKER_SIZE, 0);
    graphics.moveTo(0, -DebugRenderer.DEBUG_MARKER_SIZE);
    graphics.lineTo(0, DebugRenderer.DEBUG_MARKER_SIZE);
    graphics.stroke({color: color, width: DebugRenderer.DEBUG_MARKER_WIDTH});

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
