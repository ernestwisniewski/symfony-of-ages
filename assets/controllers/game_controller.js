// controllers/hex_map_controller.js
import {Controller} from '@hotwired/stimulus'
import {GameMap} from '../game/core/GameMap.js'

/**
 * Stimulus controller for managing the hexagonal game map
 * Handles initialization, data loading, and lifecycle management of the game map
 * Extends Stimulus Controller to provide seamless integration with Symfony/Twig
 */
export default class extends Controller {
  /**
   * Stimulus values configuration for the controller
   * Defines the data attributes that can be passed from HTML/Twig templates
   */
  static values = {
    mapUrl: String
  }

  /**
   * Stimulus connect lifecycle method - called when controller is connected to DOM
   * Fetches map data from the server and initializes the game map
   *
   * @async
   * @returns {Promise<void>}
   */
  async connect() {
    try {
      const response = await fetch(this.mapUrlValue);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const responseData = await response.json();

      this.gameMap = new GameMap(this.element, {
        cols: responseData.config.cols,
        rows: responseData.config.rows,
        size: responseData.config.size,
        mapData: responseData.data
      });
    } catch (error) {
      console.error('Error loading map data:', error);
    }
  }

  /**
   * Stimulus disconnect lifecycle method - called when controller is disconnected from DOM
   * Performs cleanup to prevent memory leaks and properly destroy PIXI application
   */
  disconnect() {
    // Cleanup when the controller is disconnected
    if (this.gameMap?.app) {
      this.gameMap.app.destroy(true);
    }
  }
}
