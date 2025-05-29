import {Application, Container} from 'pixi.js';
import {HexGrid} from './HexGrid.ts';
import {HexPopup} from './HexPopup.ts';
import {preloadTerrainTextures} from './TerrainTextures.ts';
import {CameraController} from './CameraController.ts';
import {InteractionController} from './InteractionController.ts';
import {PlayerManager} from '../player/PlayerManager.ts';
import {DebugRenderer} from './DebugRenderer.ts';
import {SelectionManager} from './SelectionManager.ts';
import {SelectableHex} from './SelectableHex.ts';
import {SelectablePlayer} from '../player/SelectablePlayer.ts';
import {SelectionPanel} from '../ui/SelectionPanel.ts';
import type {MapConfig} from './types.ts';
import type {PlayerData} from '../player/types.ts';

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
  private popup!: HexPopup;
  
  // Specialized controllers
  private cameraController!: CameraController;
  private interactionController!: InteractionController;
  private playerManager!: PlayerManager;
  private debugRenderer!: DebugRenderer;
  private selectionManager!: SelectionManager;
  private selectionPanel!: SelectionPanel;

  /**
   * Creates a new GameMap instance
   *
   * @param element - DOM element to attach the game canvas to
   * @param config - Configuration object for the map
   */
  constructor(element: HTMLElement, config: MapConfig) {
    this.element = element;
    this.config = config;
    this.init();
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
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    this.app = new Application();
    await this.app.init({
      background: GameMap.BACKGROUND_COLOR,
      width: viewportWidth,
      height: viewportHeight,
      antialias: true
    });

    this.element.appendChild(this.app.canvas);
    this.setupResizeHandler();
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

    // Create popup in UI container (always on top, fixed to viewport)
    this.popup = new HexPopup();
    this.popup.setApp(this.app);
    this.uiContainer.addChild(this.popup);

    // Pass popup reference to hex grid for interactions
    this.hexGrid.popup = this.popup;
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
      this.popup,
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

    // Initialize selection manager
    this.selectionManager = new SelectionManager();

    // Initialize selection panel
    this.selectionPanel = new SelectionPanel();
    
    // Connect selection manager to selection panel
    this.selectionManager.onSelectionChange((data) => {
      this.selectionPanel.onSelectionChange(data);
    });
    
    // Handle clear selection events from panel
    this.selectionPanel.getElement().addEventListener('clearSelection', () => {
      this.selectionManager.clearSelection();
    });
    
    // Setup player click handling
    this.hexGrid.on('playerclick', (event: any) => {
      this.handlePlayerClick(event.playerData);
    });
    
    // Setup hex click handling
    this.hexGrid.on('hexclick', (event: any) => {
      this.onHexClick(event.row, event.col);
    });
  }

  /**
   * Sets the initial view with appropriate zoom based on hex visibility
   */
  private setInitialView(): void {
    this.cameraController.setInitialView();
    
    // Only center the map if no player is present
    if (!this.playerManager.getPlayer()) {
      this.cameraController.centerMap();
    }
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
   * Adds a player to the game map
   * @param playerData - Player data from backend
   */
  addPlayer(playerData: PlayerData): void {
    this.playerManager.addPlayer(playerData);
    
    // Add debug markers when player is added
    this.debugRenderer.addDebugMarkers();
  }

  /**
   * Updates player position
   * @param playerData - Updated player data
   */
  updatePlayerPosition(playerData: PlayerData): void {
    this.playerManager.updatePlayerPosition(playerData);
  }

  /**
   * Removes player from the map
   */
  removePlayer(): void {
    this.playerManager.removePlayer();
  }

  /**
   * Gets the current player
   */
  getPlayer() {
    return this.playerManager.getPlayer();
  } 

  /**
   * Handles hex tile click events
   * @param row - Row coordinate of clicked hex
   * @param col - Column coordinate of clicked hex
   */
  onHexClick(row: number, col: number): void {
    // Get terrain data for the clicked hex
    const terrainData = this.config.mapData[row]?.[col];
    
    if (terrainData) {
      // Create selectable hex object
      const selectableHex = new SelectableHex(row, col, terrainData);
      
      // Select the hex
      this.selectionManager.select(selectableHex);
    }
    
    // Emit custom event for game controller to handle player movement
    this.element.dispatchEvent(new CustomEvent('hexclick', {
      detail: { row, col }
    }));
  }

  /**
   * Handles player click events
   * @param playerData - Player data from the clicked player
   */
  private handlePlayerClick(playerData: any): void {
    // Create selectable player object
    const selectablePlayer = new SelectablePlayer(playerData);
    
    // Select the player
    this.selectionManager.select(selectablePlayer);
  }

  /**
   * Gets the current dragging state from interaction controller
   */
  get isDragging(): boolean {
    return this.interactionController.getIsDragging();
  }
}
