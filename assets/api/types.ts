/**
 * API Types - Exact matches for Symfony API Resources
 * 
 * These types correspond directly to the App\UI\Api\Resource classes
 * and represent the data structures used in API communication.
 */

// ============================================================================
// GAME RESOURCE
// ============================================================================

/**
 * GameResource - matches App\UI\Api\Resource\GameResource
 */
export interface GameResource {
  gameId?: string | null;
  name?: string | null;
  status?: string | null;
  players?: string[] | null;
  currentTurn?: number | null;
  activePlayer?: string | null;
  createdAt?: string | null;
  startedAt?: string | null;
  playerId?: string | null; // For join operations
}

// ============================================================================
// UNIT RESOURCE
// ============================================================================

/**
 * UnitResource - matches App\UI\Api\Resource\UnitResource
 */
export interface UnitResource {
  unitId?: string | null;
  ownerId?: string | null;
  gameId?: string | null;
  type?: string | null;
  position?: { x: number; y: number } | null;
  currentHealth?: number | null;
  maxHealth?: number | null;
  isDead?: boolean | null;
  attackPower?: number | null;
  defensePower?: number | null;
  movementRange?: number | null;
  
  // Create operation fields
  playerId?: string | null;
  unitType?: string | null;
  x?: number | null;
  y?: number | null;
  
  // Move operation fields
  toX?: number | null;
  toY?: number | null;
  
  // Attack operation fields
  targetUnitId?: string | null;
}

// ============================================================================
// CITY RESOURCE
// ============================================================================

/**
 * CityResource - matches App\UI\Api\Resource\CityResource
 */
export interface CityResource {
  cityId?: string | null;
  ownerId?: string | null;
  gameId?: string | null;
  name?: string | null;
  position?: { x: number; y: number } | null;
  
  // Create operation fields
  playerId?: string | null;
  x?: number | null;
  y?: number | null;
}

// ============================================================================
// MAP RESOURCE
// ============================================================================

/**
 * MapTileResource - represents individual map tile data
 */
export interface MapTileResource {
  x: number;
  y: number;
  terrain: string;
  isOccupied: boolean;
}

/**
 * MapResource - matches App\UI\Api\Resource\MapResource
 */
export interface MapResource {
  gameId?: string | null;
  width?: number | null;
  height?: number | null;
  tiles?: MapTileResource[][] | null;
  generatedAt?: string | null;
  
  // Generate operation fields
  mapWidth?: number | null;
  mapHeight?: number | null;
}

// ============================================================================
// TURN RESOURCE
// ============================================================================

/**
 * TurnResource - matches App\UI\Api\Resource\TurnResource
 */
export interface TurnResource {
  gameId?: string | null;
  activePlayer?: string | null;
  currentTurn?: number | null;
  turnEndedAt?: string | null;
  
  // End turn operation fields
  playerId?: string | null;
}

// ============================================================================
// TERRAIN TYPES
// ============================================================================

/**
 * TerrainType enum - matches App\Domain\Map\ValueObject\TerrainType
 */
export enum TerrainType {
  PLAINS = 'plains',
  FOREST = 'forest',
  MOUNTAIN = 'mountain',
  WATER = 'water',
  DESERT = 'desert',
  SWAMP = 'swamp'
}

// ============================================================================
// UNIT TYPES
// ============================================================================

/**
 * UnitType enum - matches App\Domain\Unit\ValueObject\UnitType
 */
export enum UnitType {
  WARRIOR = 'warrior',
  SETTLER = 'settler',
  ARCHER = 'archer',
  CAVALRY = 'cavalry',
  SCOUT = 'scout',
  SIEGE_ENGINE = 'siege_engine'
}

// ============================================================================
// POSITION TYPES
// ============================================================================

/**
 * Position interface - matches App\Domain\Shared\ValueObject\Position
 */
export interface Position {
  x: number;
  y: number;
}

// ============================================================================
// API RESPONSE TYPES
// ============================================================================

/**
 * API Response wrapper for collections
 */
export interface ApiCollectionResponse<T> {
  'hydra:member': T[];
  'hydra:totalItems': number;
}

/**
 * API Response wrapper for single items
 */
export interface ApiItemResponse<T> {
  '@context': string;
  '@id': string;
  '@type': string;
  [key: string]: any;
} 