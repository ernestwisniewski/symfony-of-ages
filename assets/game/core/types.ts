/**
 * Core API Types and Interfaces
 * 
 * This file contains all TypeScript interfaces that correspond to Symfony ViewModel classes.
 * These interfaces ensure type safety and consistency across the entire application.
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

/**
 * Common properties for all API resources
 */
export interface BaseApiResource {
  id?: string;
  createdAt?: string;
  updatedAt?: string;
}

// ============================================================================
// GAME TYPES (corresponds to GameView.php)
// ============================================================================

/**
 * Game status enumeration
 */
export enum GameStatus {
  WAITING = 'waiting',
  ACTIVE = 'active',
  FINISHED = 'finished',
  CANCELLED = 'cancelled'
}

/**
 * Game data from API (corresponds to GameView.php)
 */
export interface GameData extends BaseApiResource {
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

/**
 * Create game request
 */
export interface CreateGameRequest {
  name: string;
}

/**
 * Join game request
 */
export interface JoinGameRequest {
  playerId: string;
}

/**
 * Start game request
 */
export interface StartGameRequest {
  // No additional fields needed for starting a game
}

// ============================================================================
// UNIT TYPES (corresponds to UnitView.php)
// ============================================================================

/**
 * Unit type enumeration
 */
export enum UnitType {
  WARRIOR = 'warrior',
  ARCHER = 'archer',
  CAVALRY = 'cavalry'
}

/**
 * Unit data from API (corresponds to UnitView.php)
 */
export interface UnitData extends BaseApiResource {
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

/**
 * Create unit request
 */
export interface CreateUnitRequest {
  playerId: string;
  unitType: UnitType;
  x: number;
  y: number;
}

/**
 * Move unit request
 */
export interface MoveUnitRequest {
  toX: number;
  toY: number;
}

/**
 * Attack unit request
 */
export interface AttackUnitRequest {
  targetUnitId: string;
}

// ============================================================================
// CITY TYPES (corresponds to CityView.php)
// ============================================================================

/**
 * City data from API (corresponds to CityView.php)
 */
export interface CityData extends BaseApiResource {
  id: string;
  ownerId: string;
  gameId: string;
  name: string;
  position: Position;
}

/**
 * Create city request
 */
export interface CreateCityRequest {
  playerId: string;
  name: string;
  x: number;
  y: number;
}

// ============================================================================
// MAP TYPES (corresponds to MapView.php and MapTileView.php)
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
 * Terrain tile data (corresponds to MapTileView.php)
 */
export interface TerrainTile {
  type: TerrainType;
  name: string;
  properties: TerrainProperties;
  coordinates?: GridPosition;
  // Additional fields from MapTileView
  x?: number;
  y?: number;
  terrain?: string;
  isOccupied?: boolean;
}

/**
 * Map data from API (corresponds to MapView.php)
 */
export interface MapData extends BaseApiResource {
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
// TURN TYPES (corresponds to TurnView.php)
// ============================================================================

/**
 * Turn data from API (corresponds to TurnView.php)
 */
export interface TurnData extends BaseApiResource {
  gameId: string;
  activePlayer: string;
  currentTurn: number;
  turnEndedAt: string;
}

/**
 * End turn request
 */
export interface EndTurnRequest {
  playerId: string;
}

// ============================================================================
// PLAYER TYPES (corresponds to Player domain entity)
// ============================================================================

/**
 * Player data for game rendering (derived from Player domain entity)
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

/**
 * Player animation configuration
 */
export interface PlayerAnimationConfig {
  bounceHeight: number;
  animationDuration: number;
}

/**
 * Player visual configuration
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
// API RESPONSE TYPES
// ============================================================================

/**
 * Hydra collection response wrapper
 */
export interface HydraCollection<T> {
  'hydra:member': T[];
  'hydra:totalItems': number;
  'hydra:view'?: {
    'hydra:first'?: string;
    'hydra:last'?: string;
    'hydra:next'?: string;
    'hydra:previous'?: string;
  };
}

/**
 * API error response
 */
export interface ApiError {
  message: string;
  code?: string;
  details?: Record<string, any>;
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
  return { row: position.y, col: position.x };
}

/**
 * Convert GridPosition to Position
 */
export function gridToPosition(grid: GridPosition): Position {
  return { x: grid.col, y: grid.row };
} 