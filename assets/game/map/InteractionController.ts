import {Application, FederatedPointerEvent} from 'pixi.js';
import {CameraController} from './CameraController';
import type {Position} from './types';

/**
 * InteractionController handles all user interactions
 * Manages drag operations, zoom events, and input handling
 */
export class InteractionController {
  // Interaction constants
  private static readonly DEFAULT_SCROLL_SPEED = 2;
  private static readonly ZOOM_FACTOR = 0.1;

  private app: Application;
  private cameraController: CameraController;
  private isDragging: boolean = false;
  private lastPosition: Position | null = null;
  private scrollSpeed: number = InteractionController.DEFAULT_SCROLL_SPEED;

  constructor(app: Application, cameraController: CameraController) {
    this.app = app;
    this.cameraController = cameraController;
    this.setupInteraction();
  }

  /**
   * Sets up user interaction handlers for dragging and zooming
   */
  private setupInteraction(): void {
    this.setupDragHandlers();
    this.setupZoomHandler();
  }

  /**
   * Sets up drag interaction handlers
   */
  private setupDragHandlers(): void {
    this.app.stage.eventMode = 'static';
    this.app.stage.cursor = 'grab';

    this.app.stage
      .on('pointerdown', this.onDragStart.bind(this))
      .on('pointerup', this.onDragEnd.bind(this))
      .on('pointerupoutside', this.onDragEnd.bind(this))
      .on('pointermove', this.onDragMove.bind(this));
  }

  /**
   * Sets up zoom interaction handler with passive event listener
   */
  private setupZoomHandler(): void {
    this.app.canvas.addEventListener('wheel', (e) => {
      e.preventDefault();
      this.handleZoom(e);
    }, {passive: false});
  }

  /**
   * Handles zoom interaction with mouse wheel
   */
  private handleZoom(e: WheelEvent): void {
    const direction = e.deltaY > 0 ? -1 : 1;
    const mousePosition = {
      x: e.clientX - this.app.canvas.getBoundingClientRect().left,
      y: e.clientY - this.app.canvas.getBoundingClientRect().top
    };

    this.cameraController.handleZoom(direction * InteractionController.ZOOM_FACTOR, mousePosition);
  }

  /**
   * Handles the start of a drag operation
   */
  private onDragStart(event: FederatedPointerEvent): void {
    this.isDragging = true;
    this.lastPosition = event.global.clone();
    this.app.stage.cursor = 'grabbing';
  }

  /**
   * Handles the end of a drag operation
   */
  private onDragEnd(): void {
    this.isDragging = false;
    this.lastPosition = null;
    this.app.stage.cursor = 'grab';
  }

  /**
   * Handles the drag movement during active dragging
   */
  private onDragMove(event: FederatedPointerEvent): void {
    if (!this.isDragging || !this.lastPosition) return;

    const newPosition = event.global;
    const dx = (newPosition.x - this.lastPosition.x) * this.scrollSpeed;
    const dy = (newPosition.y - this.lastPosition.y) * this.scrollSpeed;

    this.cameraController.updateCameraPosition(dx, dy);
    this.lastPosition = newPosition.clone();
  }

  /**
   * Gets the current dragging state
   */
  getIsDragging(): boolean {
    return this.isDragging;
  }

  /**
   * Sets the scroll speed for drag operations
   */
  setScrollSpeed(speed: number): void {
    this.scrollSpeed = speed;
  }

  /**
   * Gets the current scroll speed
   */
  getScrollSpeed(): number {
    return this.scrollSpeed;
  }
}
