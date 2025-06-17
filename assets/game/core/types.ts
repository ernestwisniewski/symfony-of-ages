/**
 * Core Game Types and Interfaces
 *
 * This file contains TypeScript interfaces for internal game logic and rendering.
 * These types are used for game state management and UI interactions.
 */

// ============================================================================
// BASE TYPES
// ============================================================================

/**
 * Position coordinates used throughout the application
 */
export interface Position {
  x: number;
  y: number;
}

/**
 * Grid position coordinates (row/col system)
 */
export interface GridPosition {
  row: number;
  col: number;
}

// ============================================================================
// GAME TYPES
// ============================================================================

/**
 * Game data for internal use
 */
export interface GameData {
  id: string;
  name: string;
  status: string;
  activePlayer: string;
  currentTurn: number;
  createdAt: string;
  players: string[];
  userId: number;
  startedAt?: string | null;
  currentTurnAt?: string | null;
}

// ============================================================================
// UNIT TYPES
// ============================================================================

/**
 * Unit data for internal use
 */
export interface UnitData {
  id: string;
  ownerId: string;
  gameId: string;
  type: string;
  position: Position;
  currentHealth: number;
  maxHealth: number;
  isDead: boolean;
  attackPower: number;
  defensePower: number;
  movementRange: number;
}

// ============================================================================
// CITY TYPES
// ============================================================================

/**
 * City data for internal use
 */
export interface CityData {
  id: string;
  ownerId: string;
  gameId: string;
  name: string;
  position: Position;
}

// ============================================================================
// MAP TYPES
// ============================================================================

/**
 * Terrain type enumeration
 */
export enum TerrainType {
  PLAINS = 'plains',
  FOREST = 'forest',
  MOUNTAIN = 'mountain',
  WATER = 'water',
  DESERT = 'desert',
  SWAMP = 'swamp'
}

/**
 * Terrain properties
 */
export interface TerrainProperties {
  color?: number;
  movementCost?: number;
  defenseBonus?: number;
  productionBonus?: number;
  foodBonus?: number;
  goldBonus?: number;
  impassable?: boolean;
}

/**
 * Terrain tile data for internal use
 */
export interface TerrainTile {
  type: TerrainType;
  name: string;
  properties: TerrainProperties;
  coordinates?: GridPosition;
  // Additional fields for rendering
  x?: number;
  y?: number;
  terrain?: string;
  isOccupied?: boolean;
}

/**
 * Map data for internal use
 */
export interface MapData {
  gameId: string;
  width: number;
  height: number;
  tiles: TerrainTile[][];
  generatedAt: string;
}

/**
 * Map configuration for rendering
 */
export interface MapConfig {
  rows: number;
  cols: number;
  size: number;
  mapData: TerrainTile[][];
}

// ============================================================================
// TURN TYPES
// ============================================================================

/**
 * Turn data for internal use
 */
export interface TurnData {
  gameId: string;
  activePlayer: string;
  currentTurn: number;
  turnEndedAt: string;
}

// ============================================================================
// PLAYER TYPES
// ============================================================================

/**
 * Player data for game rendering
 */
export interface PlayerData {
  id: string;
  name: string;
  position: GridPosition;
  movementPoints: number;
  maxMovementPoints: number;
  color: number;
  // Additional fields from Player domain
  playerId?: string;
  gameId?: string;
  userId?: number;
}

// ============================================================================
// SELECTION TYPES
// ============================================================================

/**
 * Selectable object interface
 */
export interface SelectableObject {
  readonly id: string;
  readonly type: string;
  readonly position: GridPosition;
  readonly displayName: string;

  getSelectionInfo(): Record<string, any>;
}

/**
 * Selection data
 */
export interface SelectionData {
  id: string;
  type: string;
  position: GridPosition;
  displayName: string;
  info: Record<string, any>;
}

// ============================================================================
// UTILITY TYPES
// ============================================================================

/**
 * Type guard for checking if object is a valid position
 */
export function isPosition(obj: any): obj is Position {
  return obj && typeof obj.x === 'number' && typeof obj.y === 'number';
}

/**
 * Type guard for checking if object is a valid grid position
 */
export function isGridPosition(obj: any): obj is GridPosition {
  return obj && typeof obj.row === 'number' && typeof obj.col === 'number';
}

/**
 * Convert Position to GridPosition
 */
export function positionToGrid(position: Position): GridPosition {
  return {row: position.y, col: position.x};
}

/**
 * Convert GridPosition to Position
 */
export function gridToPosition(grid: GridPosition): Position {
  return {x: grid.col, y: grid.row};
}
