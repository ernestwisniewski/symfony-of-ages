import {Container, Graphics} from 'pixi.js';
import {HexGeometry} from '../map/HexGeometry';
import {ColorUtils} from '../utils/ColorUtils';
import type {UnitData} from '../core/types';

/**
 * Unit class for rendering units as pulsating dots on the hex map
 * Handles unit visualization, position updates, and pulsating animation
 */
export class Unit {
  // Visual configuration constants
  private static readonly UNIT_CIRCLE_RADIUS_RATIO = 0.12;
  private static readonly PULSE_RADIUS_RATIO = 0.2;
  private static readonly SHADOW_OFFSET_X = 1;
  private static readonly SHADOW_OFFSET_Y = 1;
  private static readonly SHADOW_ALPHA = 0.2;
  private static readonly BORDER_COLOR = 0xFFFFFF;
  private static readonly BORDER_WIDTH = 2;
  private static readonly INNER_CIRCLE_ALPHA = 0.9;
  private static readonly PULSE_ALPHA = 0.4;
  private static readonly PULSE_COLOR = 0x00FFFF;

  // Animation constants
  private static readonly PULSE_DURATION = 1500; // 1.5 seconds for full pulse cycle
  private static readonly PULSE_SCALE_MIN = 0.7;
  private static readonly PULSE_SCALE_MAX = 1.3;

  private data: UnitData;
  private hexSize: number;
  private hexGeometry: HexGeometry;
  public sprite: Container;
  private circle: Graphics;
  private shadow: Graphics;
  private pulseRing: Graphics;
  private animationId: number | null = null;

  constructor(unitData: UnitData, hexSize: number) {
    this.data = unitData;
    this.hexSize = hexSize;
    this.hexGeometry = new HexGeometry(hexSize);

    this.sprite = new Container();
    this.circle = new Graphics();
    this.shadow = new Graphics();
    this.pulseRing = new Graphics();

    this.createSprite();
    this.updatePosition(unitData.position);
    this.startPulseAnimation();
  }

  /**
   * Create the unit sprite with circle, shadow and pulse ring
   */
  private createSprite(): void {
    this.createShadow();
    this.createPulseRing();
    this.createUnitCircle();
    this.setupSpriteInteractivity();
  }

  /**
   * Creates the shadow behind the unit
   */
  private createShadow(): void {
    this.shadow.circle(Unit.SHADOW_OFFSET_X, Unit.SHADOW_OFFSET_Y, this.hexSize * Unit.UNIT_CIRCLE_RADIUS_RATIO);
    this.shadow.fill({color: 0x000000, alpha: Unit.SHADOW_ALPHA});
    this.sprite.addChild(this.shadow);
  }

  /**
   * Creates the pulsating ring effect
   */
  private createPulseRing(): void {
    this.pulseRing.circle(0, 0, this.hexSize * Unit.PULSE_RADIUS_RATIO);
    this.pulseRing.stroke({color: Unit.PULSE_COLOR, width: 2, alpha: Unit.PULSE_ALPHA});
    this.sprite.addChild(this.pulseRing);
  }

  /**
   * Creates the main unit circle
   */
  private createUnitCircle(): void {
    this.drawUnitCircleElements();
    this.sprite.addChild(this.circle);
  }

  /**
   * Draws all circle elements for the unit
   */
  private drawUnitCircleElements(): void {
    const color = ColorUtils.getColorForOwner(this.data.ownerId);

    // Main unit circle
    this.circle.circle(0, 0, this.hexSize * Unit.UNIT_CIRCLE_RADIUS_RATIO);
    this.circle.fill({color: color});

    // Add white border for visibility
    this.circle.circle(0, 0, this.hexSize * Unit.UNIT_CIRCLE_RADIUS_RATIO);
    this.circle.stroke({color: Unit.BORDER_COLOR, width: Unit.BORDER_WIDTH});

    // Add inner circle for better definition
    this.circle.circle(0, 0, this.hexSize * Unit.UNIT_CIRCLE_RADIUS_RATIO * 0.6);
    this.circle.fill({color: color, alpha: Unit.INNER_CIRCLE_ALPHA});

    // Add unit type indicator (small dot in center)
    this.circle.circle(0, 0, this.hexSize * Unit.UNIT_CIRCLE_RADIUS_RATIO * 0.2);
    this.circle.fill({color: 0xFFFFFF, alpha: 0.8});
  }

  /**
   * Sets up sprite interactivity
   */
  private setupSpriteInteractivity(): void {
    this.sprite.eventMode = 'static';
    this.sprite.cursor = 'pointer';

    // Add click event
    this.sprite.on('click', () => {
      this.sprite.emit('unitclick', {unitData: this.data});
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
      const progress = (elapsed % Unit.PULSE_DURATION) / Unit.PULSE_DURATION;

      // Create a smooth sine wave for the pulse
      const pulseScale = Unit.PULSE_SCALE_MIN +
        (Unit.PULSE_SCALE_MAX - Unit.PULSE_SCALE_MIN) *
        (Math.sin(progress * Math.PI * 2) * 0.5 + 0.5);

      this.pulseRing.scale.set(pulseScale);

      // Adjust alpha based on scale
      const alpha = Unit.PULSE_ALPHA * (1 - (pulseScale - Unit.PULSE_SCALE_MIN) / (Unit.PULSE_SCALE_MAX - Unit.PULSE_SCALE_MIN));
      this.pulseRing.alpha = alpha;

      this.animationId = requestAnimationFrame(animate);
    };

    animate();
  }

  /**
   * Update unit position on the hex grid
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
   * Update unit data with new values
   */
  updateData(newData: Partial<UnitData>): void {
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
   * Get unit data
   */
  getData(): UnitData {
    return this.data;
  }

  /**
   * Check if unit is at specific position
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
