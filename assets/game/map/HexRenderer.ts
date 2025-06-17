import {ColorMatrixFilter, Container} from 'pixi.js';
import {DropShadowFilter} from '@pixi/filter-drop-shadow';

/**
 * HexRenderer handles visual effects and filters for hex tiles
 * Separates rendering concerns from grid logic
 */
export class HexRenderer {
  // Shadow effect configuration constants
  private static readonly SHADOW_COLOR = 0x000000;
  private static readonly SHADOW_ALPHA = 0.4;
  private static readonly SHADOW_BLUR = 8;
  private static readonly SHADOW_DISTANCE = 6;
  private static readonly SHADOW_OFFSET_X = 3;
  private static readonly SHADOW_OFFSET_Y = 3;
  private static readonly SHADOW_QUALITY = 5;
  private static readonly SHADOW_RESOLUTION = 2;
  private static readonly BRIGHTNESS_MULTIPLIER = 1.15;

  /**
   * Creates a combined filter for hex hover effect with shadow and brightness
   * Combines drop shadow and brightness filters for enhanced visual feedback
   *
   * @returns Array of PIXI filters to apply for hover effects
   */
  static createHoverEffect(): any[] {
    // Create drop shadow filter
    const shadowFilter = new DropShadowFilter({
      color: HexRenderer.SHADOW_COLOR,
      alpha: HexRenderer.SHADOW_ALPHA,
      blur: HexRenderer.SHADOW_BLUR,
      distance: HexRenderer.SHADOW_DISTANCE,
      offset: {x: HexRenderer.SHADOW_OFFSET_X, y: HexRenderer.SHADOW_OFFSET_Y},
      quality: HexRenderer.SHADOW_QUALITY
    });

    // Set resolution using the modern approach
    shadowFilter.resolution = HexRenderer.SHADOW_RESOLUTION;

    // Create brightness filter for better visibility
    const brightnessFilter = new ColorMatrixFilter();
    brightnessFilter.brightness(HexRenderer.BRIGHTNESS_MULTIPLIER, false);

    // Return array of filters to apply both effects
    return [shadowFilter, brightnessFilter];
  }

  /**
   * Apply hover effect to a container
   */
  static applyHoverEffect(container: Container): void {
    container.filters = HexRenderer.createHoverEffect();
  }

  /**
   * Remove hover effect from a container
   */
  static removeHoverEffect(container: Container): void {
    container.filters = [];
  }
}
