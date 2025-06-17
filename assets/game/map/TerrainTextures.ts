import {Assets} from 'pixi.js';
import {TerrainType} from '../../api';

// Terrain texture imports for Vite asset handling
import plainsImg from '../../images/terrain/plains.png';
import forestImg from '../../images/terrain/forest.png';
import mountainImg from '../../images/terrain/mountain.png';
import waterImg from '../../images/terrain/water.png';
import desertImg from '../../images/terrain/desert.png';
import swampImg from '../../images/terrain/swamp.png';

/**
 * Interface for terrain texture configuration
 */
interface TerrainTextureConfig {
  [key: string]: string;
}

/**
 * TerrainTextures module for managing terrain-specific textures and visual assets
 * Provides texture loading and management for different terrain types
 * Handles texture caching and provides fallback solid color textures
 */

/**
 * Terrain texture mapping object
 * Maps terrain type names to their imported texture URLs
 * Vite automatically handles asset versioning and optimization
 */
const TERRAIN_TEXTURES: TerrainTextureConfig = {
  [TerrainType.PLAINS]: plainsImg,
  [TerrainType.FOREST]: forestImg,
  [TerrainType.MOUNTAIN]: mountainImg,
  [TerrainType.WATER]: waterImg,
  [TerrainType.DESERT]: desertImg,
  [TerrainType.SWAMP]: swampImg
};

/**
 * Preloads all terrain textures into PIXI's asset cache
 * Loads texture files for terrain types that have associated texture paths
 * Provides fallback handling for missing or failed texture loads
 *
 * @returns Promise that resolves when all textures are loaded
 */
export async function preloadTerrainTextures(): Promise<void> {
  const loadPromises = Object.entries(TERRAIN_TEXTURES).map(async ([terrainType, texturePath]) => {
    try {
      await Assets.load(texturePath);
    } catch (error) {
      console.warn(`Failed to load texture for ${terrainType}:`, error);
    }
  });

  await Promise.all(loadPromises);
}

/**
 * Gets the texture URL for a given terrain type
 * Returns the Vite-processed texture URL for direct loading
 *
 * @param terrainType - The terrain type name (e.g., 'plains', 'forest')
 * @returns The texture URL or null if not found
 */
export function getTerrainTexture(terrainType: TerrainType): string | null {
  const texture = TERRAIN_TEXTURES[terrainType] || null;
  return texture;
}
