import { Container, Graphics, Text, Color } from 'pixi.js';

/**
 * HexPopup class for displaying terrain information in a thin bottom bar
 * Extends PIXI.Container to provide a compact information panel at bottom center of screen
 */
export class HexPopup extends Container {
  /**
   * Creates a new HexPopup instance
   * Initializes the popup with background and text components as a bottom bar
   */
  constructor() {
    super();
    this.visible = false;
    this.app = null; // Will be set by GameMap
    this.setupBackground();
    this.setupText();
  }

  /**
   * Sets the PIXI application reference for screen dimensions
   * 
   * @param {Application} app - PIXI Application instance
   */
  setApp(app) {
    this.app = app;
  }

  /**
   * Sets up the background graphics for the popup bar
   * Creates a Graphics object that will be used for the thin bottom bar background
   */
  setupBackground() {
    this.background = new Graphics();
    this.addChild(this.background);
  }

  /**
   * Sets up the text component for displaying popup content
   * Creates a Text object with predefined styling for terrain information in horizontal layout
   */
  setupText() {
    this.content = new Text({
      text: '',
      style: {
        fontFamily: 'Arial',
        fontSize: 16,
        fill: '#ffffff',
        align: 'center'
      }
    });

    this.addChild(this.content);
  }

  /**
   * Shows the popup with terrain data as a thin bottom bar
   *
   * @param {Object} data - Terrain data object containing type, name, and properties
   */
  show(data) {
    // Format text in horizontal layout for the bottom bar
    this.content.text = this.formatTerrainInfoHorizontal(data);

    // Calculate dimensions for the bottom bar
    const padding = 20;
    const textWidth = this.content.width;
    const barWidth = Math.max(textWidth + padding * 2, 400); // Minimum width for aesthetics
    const barHeight = 50; // Fixed thin height

    // Position text in center of bar
    this.content.position.set(barWidth / 2 - textWidth / 2, barHeight / 2 - this.content.height / 2);

    // Redraw background as thin bottom bar
    this.background.clear();
    this.background.setStrokeStyle({
      width: 2,
      color: new Color('#34495e').toNumber(),
      alpha: 0.9
    });

    // Rounded rectangle for the bar
    this.background.roundRect(0, 0, barWidth, barHeight, 25);
    this.background.fill({
      color: new Color('#2c3e50').toNumber(),
      alpha: 0.95
    });
    this.background.stroke();

    // Position at bottom center of screen
    this.updatePosition(barWidth, barHeight);
    this.visible = true;
  }

  /**
   * Updates the popup position to bottom center of screen
   * Called when showing the popup and when screen is resized
   * 
   * @param {number} barWidth - Width of the popup bar
   * @param {number} barHeight - Height of the popup bar
   */
  updatePosition(barWidth = 400, barHeight = 50) {
    // Use app reference directly for screen dimensions
    if (this.app && this.app.screen) {
      const margin = 20;
      this.position.set(
        this.app.screen.width / 2 - barWidth / 2,  // Center horizontally
        this.app.screen.height - barHeight - margin // Bottom with margin
      );
    }
  }

  /**
   * Hides the popup by setting its visibility to false
   */
  hide() {
    this.visible = false;
  }

  /**
   * Formats terrain data into a compact horizontal string for the bottom bar
   *
   * @param {Object} data - Terrain data object
   * @param {string} data.type - Type of terrain (e.g., 'plains', 'forest')
   * @param {string} data.name - Display name of terrain
   * @param {Object} data.properties - Terrain properties object
   * @param {number} data.properties.movement - Movement cost for this terrain
   * @param {number} data.properties.defense - Defense bonus for this terrain
   * @param {number} data.properties.resources - Resource value for this terrain
   * @returns {string} Formatted compact string containing terrain information
   */
  formatTerrainInfoHorizontal(data) {
    const name = data.name || data.type;
    const props = data.properties || {};
    
    // Compact horizontal format with separators
    return `${name} • Ruch: ${props.movement || 'N/A'} • Obrona: ${props.defense || 'N/A'} • Zasoby: ${props.resources || 'N/A'}`;
  }
}
