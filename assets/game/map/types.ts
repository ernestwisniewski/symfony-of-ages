/**
 * Common types and interfaces for the map system
 */

/**
 * Interface for map configuration
 */
export interface MapConfig {
  rows: number;
  cols: number;
  size: number;
  mapData: any[][];
}

/**
 * Interface for position coordinates
 */
export interface Position {
  x: number;
  y: number;
} 