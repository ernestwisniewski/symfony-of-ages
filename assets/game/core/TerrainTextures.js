// Terrain texture imports for Vite asset handling
import plainsImg from '../../images/terrain/plains.png';
import forestImg from '../../images/terrain/forest.png';
import mountainImg from '../../images/terrain/mountain.png';
import waterImg from '../../images/terrain/water.png';
import desertImg from '../../images/terrain/desert.png';
import swampImg from '../../images/terrain/swamp.png';

/**
 * Terrain texture mapping object
 * Maps terrain type names to their imported texture URLs
 * Vite automatically handles asset versioning and optimization
 */
export const TerrainTextures = {
  plains: plainsImg,
  forest: forestImg,
  mountain: mountainImg,
  water: waterImg,
  desert: desertImg,
  swamp: swampImg
};

/**
 * Gets the texture URL for a given terrain type
 *
 * @param {string} terrainType - The terrain type name (e.g., 'plains', 'forest')
 * @returns {string|null} The texture URL or null if not found
 */
export function getTerrainTexture(terrainType) {
  return TerrainTextures[terrainType] || null;
}

/**
 * Gets all terrain texture URLs for preloading
 *
 * @returns {string[]} Array of all texture URLs
 */
export function getAllTerrainTextures() {
  return Object.values(TerrainTextures);
}
