import { Application, Container } from 'pixi.js';
import { HexGrid } from './HexGrid.ts';
import { HexPopup } from './HexPopup.ts';
import type { MapConfig } from './types.ts';

/**
 * CameraController handles all camera-related operations
 * Manages zoom, constraints, centering, and viewport adjustments
 */
export class CameraController {
  // Camera configuration constants
  private static readonly ISOMETRIC_Y_SCALE = 0.8;
  private static readonly TARGET_HEXES_ACROSS_SCREEN = 7;
  private static readonly MIN_HEXES_ACROSS_SCREEN = 25;
  private static readonly MAX_HEXES_ACROSS_SCREEN = 3;
  private static readonly ABSOLUTE_MIN_SCALE = 0.01;
  private static readonly ABSOLUTE_MAX_SCALE = 15;
  private static readonly BASE_CAMERA_MARGIN = 30;
  private static readonly MARGIN_SCALE_FACTOR = 2;
  private static readonly CONSTRAINT_THRESHOLD = 1;
  private static readonly TARGET_PLAYER_SCREEN_SIZE = 30;
  private static readonly PLAYER_RADIUS_RATIO = 0.2;

  private app: Application;
  private worldContainer: Container;
  private hexGrid: HexGrid;
  private popup: HexPopup;
  private config: MapConfig;
  private minScale: number = 0;
  private maxScale: number = 0;
  private isCenteringOnPlayer: boolean = false;

  constructor(app: Application, worldContainer: Container, hexGrid: HexGrid, popup: HexPopup, config: MapConfig) {
    this.app = app;
    this.worldContainer = worldContainer;
    this.hexGrid = hexGrid;
    this.popup = popup;
    this.config = config;
    this.adjustScaleToFitViewport();
  }

  /**
   * Sets the initial view with appropriate zoom based on hex visibility
   */
  setInitialView(): void {
    const hexWidth = this.config.size * Math.sqrt(3);
    const targetHexScreenWidth = this.app.screen.width / CameraController.TARGET_HEXES_ACROSS_SCREEN;
    const initialScale = targetHexScreenWidth / hexWidth;

    const clampedInitialScale = Math.max(this.minScale, Math.min(this.maxScale, initialScale));
    
    this.worldContainer.scale.x = clampedInitialScale;
    this.worldContainer.scale.y = clampedInitialScale * CameraController.ISOMETRIC_Y_SCALE;
  }

  /**
   * Centers the map in the viewport
   */
  centerMap(): void {
    this.worldContainer.position.x = this.app.screen.width / 2;
    this.worldContainer.position.y = this.app.screen.height / 2;
  }

  /**
   * Adjusts the scale to fit the viewport while maintaining aspect ratio
   */
  adjustScaleToFitViewport(): void {
    const viewportWidth = this.app.screen.width;
    const hexWidth = this.config.size * Math.sqrt(3);

    const minHexScreenWidth = viewportWidth / CameraController.MIN_HEXES_ACROSS_SCREEN;
    const minZoomFromHexSize = minHexScreenWidth / hexWidth;

    const maxHexScreenWidth = viewportWidth / CameraController.MAX_HEXES_ACROSS_SCREEN;
    const maxZoomFromHexSize = maxHexScreenWidth / hexWidth;

    this.minScale = Math.max(minZoomFromHexSize, CameraController.ABSOLUTE_MIN_SCALE);
    this.maxScale = Math.min(maxZoomFromHexSize, CameraController.ABSOLUTE_MAX_SCALE);
    this.maxScale = Math.max(this.maxScale, this.minScale * 2);
  }

  /**
   * Constrains the camera to stay within map boundaries
   */
  constrainCamera(): void {
    if (this.isCenteringOnPlayer) {
      return;
    }
    
    const constraintData = this.calculateConstraintData();
    
    if (this.shouldCenterMapOnScreen(constraintData)) {
      this.centerMap();
      return;
    }
    
    const newPosition = this.calculateConstrainedPosition(constraintData);
    this.applyPositionChanges(constraintData.currentPosition, newPosition);
  }

  /**
   * Centers camera on player position
   */
  centerCameraOnPlayer(playerWorldPosition: { x: number, y: number }): void {
    this.isCenteringOnPlayer = true;

    const cameraPosition = this.calculateCameraCenterPosition(playerWorldPosition);
    
    this.worldContainer.position.x = cameraPosition.x;
    this.worldContainer.position.y = cameraPosition.y;
    
    this.isCenteringOnPlayer = false;
  }

  /**
   * Sets optimal zoom level for player visibility
   */
  setOptimalPlayerZoom(): void {
    const playerWorldSize = this.config.size * CameraController.PLAYER_RADIUS_RATIO;
    const optimalScale = CameraController.TARGET_PLAYER_SCREEN_SIZE / playerWorldSize;
    const clampedScale = Math.max(this.minScale, Math.min(this.maxScale, optimalScale));
    
    this.worldContainer.scale.x = clampedScale;
    this.worldContainer.scale.y = clampedScale * CameraController.ISOMETRIC_Y_SCALE;
  }

  /**
   * Handles zoom with mouse position as center
   */
  handleZoom(direction: number, mousePosition: { x: number, y: number }): boolean {
    const currentScale = this.worldContainer.scale.x;
    const newScale = currentScale + direction * 0.1; // ZOOM_FACTOR moved from GameMap

    if (newScale >= this.minScale && newScale <= this.maxScale) {
      const gridPos = {
        x: (mousePosition.x - this.worldContainer.position.x) / currentScale,
        y: (mousePosition.y - this.worldContainer.position.y) / currentScale
      };

      this.worldContainer.scale.x = newScale;
      this.worldContainer.scale.y = newScale * CameraController.ISOMETRIC_Y_SCALE;

      this.worldContainer.position.x = mousePosition.x - gridPos.x * newScale;
      this.worldContainer.position.y = mousePosition.y - gridPos.y * newScale;

      this.constrainCamera();
      return true;
    }
    return false;
  }

  /**
   * Updates camera position during drag
   */
  updateCameraPosition(dx: number, dy: number): void {
    this.worldContainer.position.x += dx;
    this.worldContainer.position.y += dy;
    this.constrainCamera();
  }

  /**
   * Handles resize event
   */
  onResize(): void {
    this.adjustScaleToFitViewport();
    this.centerMap();
    if (this.popup && this.popup.visible) {
      this.popup.updatePosition();
    }
  }

  // Private helper methods

  private calculateConstraintData() {
    const scale = this.worldContainer.scale.x;
    const adaptiveMargin = Math.max(CameraController.BASE_CAMERA_MARGIN, CameraController.BASE_CAMERA_MARGIN * (CameraController.MARGIN_SCALE_FACTOR / scale));
    
    const bounds = this.hexGrid.getBounds();
    const scaleY = this.worldContainer.scale.y;

    const mapScreenWidth = bounds.width * scale;
    const mapScreenHeight = bounds.height * scaleY;
    const screenWidth = this.app.screen.width;
    const screenHeight = this.app.screen.height;
    
    const currentPosition = {
      x: this.worldContainer.position.x,
      y: this.worldContainer.position.y
    };
    
    return {
      scale,
      adaptiveMargin,
      mapScreenWidth,
      mapScreenHeight,
      screenWidth,
      screenHeight,
      currentPosition
    };
  }

  private shouldCenterMapOnScreen(constraintData: any): boolean {
    return constraintData.mapScreenWidth <= constraintData.screenWidth && 
           constraintData.mapScreenHeight <= constraintData.screenHeight;
  }

  private calculateConstrainedPosition(constraintData: any) {
    const { adaptiveMargin, mapScreenWidth, mapScreenHeight, screenWidth, screenHeight, currentPosition } = constraintData;
    
    const bounds = {
      minX: adaptiveMargin - (mapScreenWidth / 2),
      maxX: screenWidth - adaptiveMargin + (mapScreenWidth / 2),
      minY: adaptiveMargin - (mapScreenHeight / 2),
      maxY: screenHeight - adaptiveMargin + (mapScreenHeight / 2)
    };
    
    let newX = currentPosition.x;
    let newY = currentPosition.y;
    
    if (mapScreenWidth > screenWidth) {
      newX = this.constrainAxisPosition(currentPosition.x, bounds.minX, bounds.maxX, 'X');
    }
    
    if (mapScreenHeight > screenHeight) {
      newY = this.constrainAxisPosition(currentPosition.y, bounds.minY, bounds.maxY, 'Y');
    }
    
    return { x: newX, y: newY };
  }

  private constrainAxisPosition(current: number, min: number, max: number, axis: string): number {
    if (current < min) {
      return min;
    } else if (current > max) {
      return max;
    }
    return current;
  }

  private applyPositionChanges(currentPosition: any, newPosition: any): void {
    if (Math.abs(newPosition.x - currentPosition.x) > CameraController.CONSTRAINT_THRESHOLD) {
      this.worldContainer.position.x = newPosition.x;
    }
    if (Math.abs(newPosition.y - currentPosition.y) > CameraController.CONSTRAINT_THRESHOLD) {
      this.worldContainer.position.y = newPosition.y;
    }
  }

  private calculateCameraCenterPosition(playerWorldPosition: { x: number, y: number }) {
    const worldScale = this.worldContainer.scale.x;
    const worldScaleY = this.worldContainer.scale.y;
    
    const centerX = this.app.screen.width / 2;
    const centerY = this.app.screen.height / 2;
    
    return {
      x: centerX - playerWorldPosition.x * worldScale,
      y: centerY - playerWorldPosition.y * worldScaleY,
      centerX,
      centerY
    };
  }
} 