import {Container, Graphics} from 'pixi.js';
import {HexGeometry} from '../map/HexGeometry';
import {ColorUtils} from '../utils/ColorUtils';
import type {CityData} from '../core/types';

/**
 * City class for rendering cities as pulsating dots on the hex map
 * Handles city visualization, position updates, and pulsating animation
 */
export class City {
  // Visual configuration constants
  private static readonly CITY_CIRCLE_RADIUS_RATIO = 0.15;
  private static readonly PULSE_RADIUS_RATIO = 0.25;
  private static readonly SHADOW_OFFSET_X = 1;
  private static readonly SHADOW_OFFSET_Y = 1;
  private static readonly SHADOW_ALPHA = 0.2;
  private static readonly BORDER_COLOR = 0xFFFFFF;
  private static readonly BORDER_WIDTH = 2;
  private static readonly INNER_CIRCLE_ALPHA = 0.9;
  private static readonly PULSE_ALPHA = 0.3;
  private static readonly PULSE_COLOR = 0xFFFF00;

  // Animation constants
  private static readonly PULSE_DURATION = 2000; // 2 seconds for full pulse cycle
  private static readonly PULSE_SCALE_MIN = 0.8;
  private static readonly PULSE_SCALE_MAX = 1.2;

  private data: CityData;
  private hexSize: number;
  private hexGeometry: HexGeometry;
  public sprite: Container;
  private circle: Graphics;
  private shadow: Graphics;
  private pulseRing: Graphics;
  private animationId: number | null = null;

  constructor(cityData: CityData, hexSize: number) {
    this.data = cityData;
    this.hexSize = hexSize;
    this.hexGeometry = new HexGeometry(hexSize);

    this.sprite = new Container();
    this.circle = new Graphics();
    this.shadow = new Graphics();
    this.pulseRing = new Graphics();

    this.createSprite();
    this.updatePosition(cityData.position);
    this.startPulseAnimation();
  }

  /**
   * Create the city sprite with circle, shadow and pulse ring
   */
  private createSprite(): void {
    this.createShadow();
    this.createPulseRing();
    this.createCityCircle();
    this.setupSpriteInteractivity();
  }

  /**
   * Creates the shadow behind the city
   */
  private createShadow(): void {
    this.shadow.circle(City.SHADOW_OFFSET_X, City.SHADOW_OFFSET_Y, this.hexSize * City.CITY_CIRCLE_RADIUS_RATIO);
    this.shadow.fill({color: 0x000000, alpha: City.SHADOW_ALPHA});
    this.sprite.addChild(this.shadow);
  }

  /**
   * Creates the pulsating ring effect
   */
  private createPulseRing(): void {
    this.pulseRing.circle(0, 0, this.hexSize * City.PULSE_RADIUS_RATIO);
    this.pulseRing.stroke({color: City.PULSE_COLOR, width: 2, alpha: City.PULSE_ALPHA});
    this.sprite.addChild(this.pulseRing);
  }

  /**
   * Creates the main city circle
   */
  private createCityCircle(): void {
    this.drawCityCircleElements();
    this.sprite.addChild(this.circle);
  }

  /**
   * Draws all circle elements for the city
   */
  private drawCityCircleElements(): void {
    const color = ColorUtils.getColorForOwner(this.data.ownerId);

    // Main city circle
    this.circle.circle(0, 0, this.hexSize * City.CITY_CIRCLE_RADIUS_RATIO);
    this.circle.fill({color: color});

    // Add white border for visibility
    this.circle.circle(0, 0, this.hexSize * City.CITY_CIRCLE_RADIUS_RATIO);
    this.circle.stroke({color: City.BORDER_COLOR, width: City.BORDER_WIDTH});

    // Add inner circle for better definition
    this.circle.circle(0, 0, this.hexSize * City.CITY_CIRCLE_RADIUS_RATIO * 0.6);
    this.circle.fill({color: color, alpha: City.INNER_CIRCLE_ALPHA});
  }

  /**
   * Sets up sprite interactivity
   */
  private setupSpriteInteractivity(): void {
    this.sprite.eventMode = 'static';
    this.sprite.cursor = 'pointer';

    // Add click event
    this.sprite.on('click', () => {
      this.sprite.emit('cityclick', {cityData: this.data});
    });

    // Add hover events for visual feedback
    this.sprite.on('pointerover', () => {
      this.sprite.scale.set(1.1);
    });

    this.sprite.on('pointerout', () => {
      this.sprite.scale.set(1.0);
    });
  }

  /**
   * Start the pulsating animation
   */
  private startPulseAnimation(): void {
    const startTime = Date.now();

    const animate = (): void => {
      const elapsed = Date.now() - startTime;
      const progress = (elapsed % City.PULSE_DURATION) / City.PULSE_DURATION;

      // Create a smooth sine wave for the pulse
      const pulseScale = City.PULSE_SCALE_MIN +
        (City.PULSE_SCALE_MAX - City.PULSE_SCALE_MIN) *
        (Math.sin(progress * Math.PI * 2) * 0.5 + 0.5);

      this.pulseRing.scale.set(pulseScale);

      // Adjust alpha based on scale
      const alpha = City.PULSE_ALPHA * (1 - (pulseScale - City.PULSE_SCALE_MIN) / (City.PULSE_SCALE_MAX - City.PULSE_SCALE_MIN));
      this.pulseRing.alpha = alpha;

      this.animationId = requestAnimationFrame(animate);
    };

    animate();
  }

  /**
   * Update city position on the hex grid
   */
  updatePosition(position: { x: number; y: number }): void {
    this.data.position = position;

    const worldPos = this.hexGeometry.calculatePosition(position.y, position.x);

    // Validate the calculated position
    if (this.isValidWorldPosition(worldPos)) {
      this.setSpritePosition(worldPos);
    }
  }

  /**
   * Validates if the calculated world position is valid
   */
  private isValidWorldPosition(worldPos: { x: number, y: number }): boolean {
    if (isNaN(worldPos.x) || isNaN(worldPos.y)) {
      return false;
    }
    return true;
  }

  /**
   * Sets the sprite position
   */
  private setSpritePosition(worldPos: { x: number, y: number }): void {
    this.sprite.x = worldPos.x;
    this.sprite.y = worldPos.y;
  }

  /**
   * Update city data with new values
   */
  updateData(newData: Partial<CityData>): void {
    this.data = {...this.data, ...newData};

    if (newData.position) {
      this.updatePosition(newData.position);
    }
  }

  /**
   * Get grid position
   */
  getGridPosition(): { x: number; y: number } {
    return this.data.position;
  }

  /**
   * Get city data
   */
  getData(): CityData {
    return this.data;
  }

  /**
   * Check if city is at specific position
   */
  isAtPosition(x: number, y: number): boolean {
    return this.data.position.x === x && this.data.position.y === y;
  }

  /**
   * Cleanup resources
   */
  destroy(): void {
    if (this.animationId) {
      cancelAnimationFrame(this.animationId);
      this.animationId = null;
    }

    if (this.sprite.parent) {
      this.sprite.parent.removeChild(this.sprite);
    }

    this.sprite.destroy({children: true});
  }
}
