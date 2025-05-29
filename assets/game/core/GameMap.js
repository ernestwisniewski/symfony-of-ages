import {Application, Container, Assets} from 'pixi.js';
import {HexGrid} from './HexGrid.js';
import {HexPopup} from './HexPopup.js';
import {getAllTerrainTextures} from './TerrainTextures.js';

/**
 * GameMap class manages the main game map with hexagonal tiles
 * Handles PIXI.js application setup, camera controls, and user interactions
 * Provides Civilization-style map navigation with drag and zoom functionality
 */
export class GameMap {
  /**
   * Creates a new GameMap instance
   *
   * @param {HTMLElement} element - DOM element to attach the game canvas to
   * @param {Object} config - Configuration object for the map
   * @param {number} config.rows - Number of rows in the hex grid
   * @param {number} config.cols - Number of columns in the hex grid
   * @param {number} config.hexSize - Size (radius) of individual hexagons
   * @param {Array} config.mapData - 2D array containing terrain data for each hex
   */
  constructor(element, config) {
    this.element = element;
    this.config = config;
    this.isDragging = false;
    this.lastPosition = null;
    this.scrollSpeed = 2;
    this.init();
  }

  /**
   * Initializes the game map with all necessary components
   * Sets up PIXI application, preloads textures, creates hex grid, and configures interactions
   *
   * @async
   * @returns {Promise<void>}
   */
  async init() {
    await this.setupPixiApp();
    await this.preloadTextures();
    this.createHexGrid();
    this.setupInteraction();
    this.calculateMapBoundaries();
    this.setInitialView(); // Set initial zoomed view
  }

  /**
   * Sets up the PIXI.js application with full browser viewport
   * Creates application with full screen dimensions and responsive design
   *
   * @async
   * @returns {Promise<void>}
   */
  async setupPixiApp() {
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    this.app = new Application();
    await this.app.init({
      background: '#34495e',
      width: viewportWidth,
      height: viewportHeight,
      antialias: true
    });

    this.element.appendChild(this.app.canvas);
    this.setupResizeHandler();
  }

  /**
   * Sets up the window resize handler for responsive design
   * Uses full browser dimensions and adjusts map scaling accordingly
   */
  setupResizeHandler() {
    window.addEventListener('resize', () => {
      const width = window.innerWidth;
      const height = window.innerHeight;
      this.app.renderer.resize(width, height);
      this.adjustScaleToFitViewport();
      this.calculateMapBoundaries();
      this.centerMap();
      // Update popup position after resize
      if (this.popup && this.popup.visible) {
        this.popup.updatePosition();
      }
    });
  }

  /**
   * Creates and initializes the hex grid and UI layers
   * Adds the hex grid to a world container and UI elements to a separate UI container
   */
  createHexGrid() {
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

    this.adjustScaleToFitViewport();
    this.centerMap();
  }

  /**
   * Sets the initial view with appropriate zoom based on hex visibility
   * Positions the camera at the center of the map with reasonable zoom level
   */
  setInitialView() {
    // Calculate center hex position
    const centerRow = Math.floor(this.config.rows / 2);
    const centerCol = Math.floor(this.config.cols / 2);

    // Set initial zoom to show a reasonable number of hexes on screen
    // Target: show about 8-12 hexes across the screen width
    const hexWidth = this.config.size * Math.sqrt(3);
    const targetHexesAcrossScreen = 10; // Show about 10 hexes across
    const targetHexScreenWidth = this.app.screen.width / targetHexesAcrossScreen;
    const initialScale = targetHexScreenWidth / hexWidth;

    // Ensure initial scale is within bounds
    const clampedInitialScale = Math.max(this.minScale, Math.min(this.maxScale, initialScale));

    this.worldContainer.scale.x = clampedInitialScale;
    this.worldContainer.scale.y = clampedInitialScale * 0.8; // Maintain isometric ratio

    // Center the view
    this.centerMap();

    // Update boundaries after setting initial zoom
    this.calculateMapBoundaries();
  }

  /**
   * Centers the map in the viewport
   * Positions the world container at the center of the screen
   */
  centerMap() {
    this.worldContainer.position.x = this.app.screen.width / 2;
    this.worldContainer.position.y = this.app.screen.height / 2;
  }

  /**
   * Calculates the map boundaries for camera constraints
   * Determines the limits for camera movement based on current scale and map size
   */
  calculateMapBoundaries() {
    const bounds = this.hexGrid.getBounds();
    const scale = this.worldContainer.scale.x;

    // Calculate boundaries considering pivot point and isometric scaling
    const effectiveWidth = bounds.width * scale;
    const effectiveHeight = bounds.height * scale * 0.8;

    this.mapBounds = {
      minX: this.app.screen.width / 2 - effectiveWidth / 2,
      maxX: this.app.screen.width / 2 + effectiveWidth / 2,
      minY: this.app.screen.height / 2 - effectiveHeight / 2,
      maxY: this.app.screen.height / 2 + effectiveHeight / 2
    };
  }

  /**
   * Adjusts the scale to fit the viewport while maintaining aspect ratio
   * Calculates minimum and maximum scale values based on hex size consistency
   */
  adjustScaleToFitViewport() {
    const bounds = this.hexGrid.getBounds();
    const viewportWidth = this.app.screen.width;
    const viewportHeight = this.app.screen.height;

    // Calculate hex dimensions
    const hexWidth = this.config.size * Math.sqrt(3); // Actual hex width in pixels

    // Min zoom: show specific number of hexes across screen (strategic overview)
    const minHexesAcrossScreen = 20; // Show 20 hexes across when most zoomed out
    const minHexScreenWidth = viewportWidth / minHexesAcrossScreen;
    const minZoomFromHexSize = minHexScreenWidth / hexWidth;

    // Max zoom: show specific number of hexes across screen (detailed view)
    const maxHexesAcrossScreen = 5; // Show 5 hexes across when most zoomed in
    const maxHexScreenWidth = viewportWidth / maxHexesAcrossScreen;
    const maxZoomFromHexSize = maxHexScreenWidth / hexWidth;

    this.minScale = minZoomFromHexSize;
    this.maxScale = maxZoomFromHexSize;

    // Ensure reasonable absolute limits
    const absoluteMinScale = 0.01; // Never go below this
    const absoluteMaxScale = 10;   // Never go above this
    this.minScale = Math.max(this.minScale, absoluteMinScale);
    this.maxScale = Math.min(this.maxScale, absoluteMaxScale);

    // Ensure max zoom is greater than min zoom
    this.maxScale = Math.max(this.maxScale, this.minScale * 2);
  }

  /**
   * Constrains the camera to stay within map boundaries
   * Prevents the camera from moving outside the calculated map bounds
   */
  constrainCamera() {
    const bounds = this.hexGrid.getBounds();
    const scale = this.worldContainer.scale.x;

    // Calculate effective dimensions with pivot point and isometric scaling
    const effectiveWidth = bounds.width * scale;
    const effectiveHeight = bounds.height * scale * 0.8;

    const minX = this.app.screen.width / 2 - effectiveWidth / 2;
    const maxX = this.app.screen.width / 2 + effectiveWidth / 2;
    const minY = this.app.screen.height / 2 - effectiveHeight / 2;
    const maxY = this.app.screen.height / 2 + effectiveHeight / 2;

    this.worldContainer.position.x = Math.max(minX, Math.min(maxX, this.worldContainer.position.x));
    this.worldContainer.position.y = Math.max(minY, Math.min(maxY, this.worldContainer.position.y));
  }

  /**
   * Sets up user interaction handlers for dragging and zooming
   * Configures both drag and zoom interaction systems
   */
  setupInteraction() {
    this.setupDragHandlers();
    this.setupZoomHandler();
  }

  /**
   * Sets up drag interaction handlers
   * Configures mouse/touch events for map panning functionality
   */
  setupDragHandlers() {
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
   * Configures mouse wheel events for map zooming functionality
   */
  setupZoomHandler() {
    this.app.canvas.addEventListener('wheel', (e) => {
      e.preventDefault();
      this.handleZoom(e);
    }, { passive: false });
  }

  /**
   * Handles zoom interaction with mouse wheel
   * Provides smooth zooming with mouse position as zoom center
   *
   * @param {WheelEvent} e - The wheel event containing scroll direction and position
   */
  handleZoom(e) {
    const direction = e.deltaY > 0 ? -1 : 1;
    const factor = 0.1;
    const currentScale = this.worldContainer.scale.x;
    const newScale = currentScale + direction * factor;

    if (newScale >= this.minScale && newScale <= this.maxScale) {
      const mousePosition = {
        x: e.clientX - this.app.canvas.getBoundingClientRect().left,
        y: e.clientY - this.app.canvas.getBoundingClientRect().top
      };

      const gridPos = {
        x: (mousePosition.x - this.worldContainer.position.x) / currentScale,
        y: (mousePosition.y - this.worldContainer.position.y) / currentScale
      };

      this.worldContainer.scale.x = newScale;
      this.worldContainer.scale.y = newScale * 0.8; // Maintain isometric ratio

      this.worldContainer.position.x = mousePosition.x - gridPos.x * newScale;
      this.worldContainer.position.y = mousePosition.y - gridPos.y * newScale;

      this.calculateMapBoundaries();
      this.constrainCamera();
    }

  }

  /**
   * Handles the start of a drag operation
   * Initiates map dragging and changes cursor to indicate drag state
   *
   * @param {PIXI.FederatedPointerEvent} event - The pointer event containing position data
   */
  onDragStart(event) {
    this.isDragging = true;
    this.lastPosition = event.global.clone();
    this.app.stage.cursor = 'grabbing';
  }

  /**
   * Handles the end of a drag operation
   * Stops map dragging and restores normal cursor
   */
  onDragEnd() {
    this.isDragging = false;
    this.lastPosition = null;
    this.app.stage.cursor = 'grab';
  }

  /**
   * Handles the drag movement during active dragging
   * Updates map position based on mouse movement with scroll speed multiplier
   *
   * @param {PIXI.FederatedPointerEvent} event - The pointer event containing current position
   */
  onDragMove(event) {
    if (!this.isDragging || !this.lastPosition) return;

    const newPosition = event.global;
    const dx = (newPosition.x - this.lastPosition.x) * this.scrollSpeed;
    const dy = (newPosition.y - this.lastPosition.y) * this.scrollSpeed;

    this.worldContainer.position.x += dx;
    this.worldContainer.position.y += dy;

    this.constrainCamera();
    this.lastPosition = newPosition.clone();
  }

  /**
   * Preloads all terrain textures before creating the map
   * Uses Vite-imported texture URLs for proper asset versioning
   *
   * @async
   * @returns {Promise<void>}
   */
  async preloadTextures() {
    try {
      // Get all terrain textures from the import system
      const textureUrls = getAllTerrainTextures();

      // Preload all textures
      await Assets.load(textureUrls);

    } catch (error) {
      console.warn('Failed to preload some textures:', error);
    }
  }
}
