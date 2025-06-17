import {Application, Container} from 'pixi.js';
import {HexGrid} from './HexGrid';
import {CameraController} from './CameraController';
import {InteractionController} from './InteractionController';
import {PlayerManager} from '../player/PlayerManager';
import {DebugRenderer} from './DebugRenderer';
import {preloadTerrainTextures} from './TerrainTextures';
import type {MapConfig} from './types';
import type {CityData, UnitData} from '../core/types';
import {City} from '../player/City';
import {Unit} from '../player/Unit';

/**
 * GameMap class manages the main game map with hexagonal tiles
 * Handles PIXI.js application setup and coordinates between specialized controllers
 * Provides Civilization-style map navigation with drag and zoom functionality
 */
export class GameMap {
  // Configuration constants
  private static readonly BACKGROUND_COLOR = '#34495e';

  public app!: Application;
  private element: HTMLElement;
  private readonly config: MapConfig;
  private worldContainer!: Container;
  private uiContainer!: Container;
  private hexGrid!: HexGrid;

  // Specialized controllers
  private cameraController!: CameraController;
  private interactionController!: InteractionController;
  private playerManager!: PlayerManager;
  private debugRenderer!: DebugRenderer;

  // Callback functions for external handling
  public onHexClick?: (row: number, col: number, terrainData: any) => void;
  public onPlayerClick?: (data: CityData | UnitData) => void;

  /**
   * Creates a new GameMap instance
   *
   * @param element - DOM element to attach the game canvas to
   * @param config - Configuration object for the map
   */
  constructor(element: HTMLElement, config: MapConfig) {
    this.element = element;
    this.config = config;
  }

  /**
   * Initializes the game map with all necessary components
   * Sets up PIXI application, preloads textures, creates hex grid, and configures interactions
   */
  async init(): Promise<void> {
    await this.setupPixiApp();
    await this.preloadTextures();
    this.createHexGrid();
    this.setupControllers();
    this.setInitialView();
  }

  /**
   * Sets up the PIXI.js application with full browser viewport
   * Creates application with full screen dimensions and responsive design
   */
  async setupPixiApp(): Promise<void> {
    try {
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;

      this.app = new Application();
      await this.app.init({
        background: GameMap.BACKGROUND_COLOR,
        width: viewportWidth,
        height: viewportHeight,
        antialias: true
      });

      // Check if canvas is available before accessing it
      if (this.app.canvas) {
        this.element.appendChild(this.app.canvas);
        this.setupResizeHandler();
      } else {
        console.error('PIXI Application canvas is not available');
        throw new Error('Failed to create PIXI canvas');
      }
    } catch (error) {
      console.error('Error setting up PIXI application:', error);
      throw error;
    }
  }

  /**
   * Sets up the window resize handler for responsive design
   */
  private setupResizeHandler(): void {
    window.addEventListener('resize', () => {
      const width = window.innerWidth;
      const height = window.innerHeight;
      this.app.renderer.resize(width, height);
      this.cameraController.onResize();
    });
  }

  /**
   * Creates and initializes the hex grid and UI layers
   */
  private createHexGrid(): void {
    // Create world container for map elements (affected by camera)
    this.worldContainer = new Container();
    this.app.stage.addChild(this.worldContainer);

    // Create UI container for interface elements (fixed to viewport)
    this.uiContainer = new Container();
    this.app.stage.addChild(this.uiContainer);

    // Create hex grid in world container
    this.hexGrid = new HexGrid(this.config);
    this.worldContainer.addChild(this.hexGrid);
  }

  /**
   * Sets up all specialized controllers
   */
  private setupControllers(): void {
    // Initialize camera controller
    this.cameraController = new CameraController(
      this.app,
      this.worldContainer,
      this.hexGrid,
      this.config
    );

    // Initialize interaction controller
    this.interactionController = new InteractionController(
      this.app,
      this.cameraController
    );

    // Initialize player manager
    this.playerManager = new PlayerManager(
      this.hexGrid,
      this.cameraController,
      this.config
    );

    // Initialize debug renderer
    this.debugRenderer = new DebugRenderer(
      this.worldContainer,
      this.config
    );

    // Setup city click handling
    this.hexGrid.on('cityclick', (event: any) => {
      if (this.onPlayerClick) {
        this.onPlayerClick(event.cityData);
      }

      // Emit custom event for external handling
      const customEvent = new CustomEvent('cityclick', {
        detail: {cityData: event.cityData}
      });
      document.dispatchEvent(customEvent);
    });

    // Setup unit click handling
    this.hexGrid.on('unitclick', (event: any) => {
      if (this.onPlayerClick) {
        this.onPlayerClick(event.unitData);
      }

      // Emit custom event for external handling
      const customEvent = new CustomEvent('unitclick', {
        detail: {unitData: event.unitData}
      });
      document.dispatchEvent(customEvent);
    });

    // Setup hex click handling
    this.hexGrid.on('hexclick', (event: any) => {
      if (this.onHexClick) {
        this.onHexClick(event.row, event.col, event.terrainData);
      }

      // Emit custom event for game controller to handle player movement
      const customEvent = new CustomEvent('hexclick', {
        detail: {
          row: event.row,
          col: event.col,
          terrainData: event.terrainData
        }
      });

      document.dispatchEvent(customEvent);
    });
  }

  /**
   * Sets the initial view with appropriate zoom based on hex visibility
   */
  private setInitialView(): void {
    this.cameraController.setInitialView();

    // Center the map since we no longer have a single player
    this.cameraController.centerMap();
  }

  /**
   * Preloads all terrain textures before creating the map
   */
  async preloadTextures(): Promise<void> {
    try {
      await preloadTerrainTextures();
    } catch (error) {
      console.warn('Failed to preload some textures:', error);
    }
  }

  /**
   * Adds cities to the game map
   * @param citiesData - Array of city data from backend
   */
  addCities(citiesData: CityData[]): void {
    this.playerManager.updateCities(citiesData);
  }

  /**
   * Adds units to the game map
   * @param unitsData - Array of unit data from backend
   */
  addUnits(unitsData: UnitData[]): void {
    this.playerManager.updateUnits(unitsData);
  }

  /**
   * Updates cities on the map
   * @param citiesData - Updated cities data
   */
  updateCities(citiesData: CityData[]): void {
    this.playerManager.updateCities(citiesData);
  }

  /**
   * Updates units on the map
   * @param unitsData - Updated units data
   */
  updateUnits(unitsData: UnitData[]): void {
    this.playerManager.updateUnits(unitsData);
  }

  /**
   * Gets all cities
   */
  getCities(): Map<string, City> {
    return this.playerManager.getCities();
  }

  /**
   * Gets all units
   */
  getUnits(): Map<string, Unit> {
    return this.playerManager.getUnits();
  }

  /**
   * Gets city at specific position
   */
  getCityAtPosition(x: number, y: number): City | undefined {
    return this.playerManager.getCityAtPosition(x, y);
  }

  /**
   * Gets unit at specific position
   */
  getUnitAtPosition(x: number, y: number): Unit | undefined {
    return this.playerManager.getUnitAtPosition(x, y);
  }

  /**
   * Centers camera on specific position
   */
  centerCameraOnPosition(x: number, y: number): void {
    this.playerManager.centerCameraOnPosition(x, y);
  }

  /**
   * Gets the current dragging state from interaction controller
   */
  get isDragging(): boolean {
    return this.interactionController.getIsDragging();
  }

  /**
   * Get the DOM element for external event dispatching
   */
  getElement(): HTMLElement {
    return this.element;
  }

  /**
   * Get map configuration
   */
  getMapConfig(): MapConfig {
    return this.config;
  }
}
