/**
 * Types and interfaces for the player system
 */

/**
 * Interface for player data from backend
 */
export interface PlayerData {
  id: string;
  name: string;
  position: {
    row: number;
    col: number;
  };
  movementPoints: number;
  maxMovementPoints: number;
  color: number;
}

/**
 * Interface for player animation configuration
 */
export interface PlayerAnimationConfig {
  bounceHeight: number;
  animationDuration: number;
}

/**
 * Interface for player visual configuration
 */
export interface PlayerVisualConfig {
  circleRadiusRatio: number;
  innerRadiusRatio: number;
  borderColor: number;
  borderWidth: number;
  shadowOffsetX: number;
  shadowOffsetY: number;
  shadowAlpha: number;
} 