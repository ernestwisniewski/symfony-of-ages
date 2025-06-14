import {Controller} from '@hotwired/stimulus'
import {GameManager} from '../game/GameManager'
import type {CityResource, GameResource, MapResource, UnitResource, MapTileResource} from '../api'
import {TerrainType} from '../api'
import type {MapConfig, TerrainTile} from '../game/core'

export default class extends Controller<HTMLElement> {
    static values = {
        gameId: String,
        mapData: Object,
        gameData: Object,
        unitsData: Array,
        citiesData: Array
    }

    declare readonly gameIdValue: string
    declare readonly mapDataValue: MapResource
    declare readonly gameDataValue: GameResource
    declare readonly unitsDataValue: UnitResource[]
    declare readonly citiesDataValue: CityResource[]

    private gameManager: GameManager | null = null

    async connect(): Promise<void> {
        try {
            await this.initializeGame()
        } catch (error) {
            console.error('❌ Error initializing game:', error)
        }
    }

    async initializeGame(): Promise<void> {
        const mapData = this.mapDataValue
        const gameData = this.gameDataValue
        const unitsData = this.unitsDataValue || []
        const citiesData = this.citiesDataValue || []

        // Debug: Log the actual structure of map data
        console.log('Map data structure:', {
            width: mapData.width,
            height: mapData.height,
            tilesType: typeof mapData.tiles,
            tilesIsArray: Array.isArray(mapData.tiles),
            tilesLength: mapData.tiles?.length,
            firstTile: mapData.tiles?.[0],
            tilesSample: mapData.tiles?.slice(0, 3)
        });

        // Convert MapResource tiles to internal format
        const convertedTiles = this.convertMapTiles(mapData)

        const config: MapConfig = {
            cols: mapData.width || 0,
            rows: mapData.height || 0,
            size: 32,
            mapData: convertedTiles
        }

        this.gameManager = new GameManager(
            this.element,
            config,
            gameData,
            unitsData,
            citiesData
        )

        await this.gameManager.init()
    }

    /**
     * Convert MapResource tiles to internal TerrainTile format
     * Returns proper API types for consistency with backend
     */
    private convertMapTiles(mapResource: MapResource): TerrainTile[][] {
        if (!mapResource.tiles) {
            console.warn('No tiles data in map resource, returning empty array');
            return [];
        }

        // Check if tiles is already a 2D array
        if (Array.isArray(mapResource.tiles) && mapResource.tiles.length > 0 && Array.isArray(mapResource.tiles[0])) {
            // It's already a 2D array, convert directly
            return (mapResource.tiles as MapTileResource[][]).map(row => 
                row.map(tile => ({
                    type: this.getTerrainType(tile.terrain),
                    name: tile.terrain,
                    properties: {}, // Default empty properties
                    coordinates: { row: tile.y, col: tile.x }
                }))
            );
        }

        // If tiles is a flat array, we need to convert it to 2D array
        if (Array.isArray(mapResource.tiles)) {
            const width = mapResource.width || 0;
            const height = mapResource.height || 0;
            
            if (width === 0 || height === 0) {
                console.warn('Invalid map dimensions, returning empty array');
                return [];
            }

            // Convert flat array to 2D array
            const tiles2D: TerrainTile[][] = [];
            const flatTiles = mapResource.tiles as unknown as MapTileResource[];
            
            for (let row = 0; row < height; row++) {
                tiles2D[row] = [];
                for (let col = 0; col < width; col++) {
                    const index = row * width + col;
                    const tile = flatTiles[index];
                    
                    if (tile && typeof tile === 'object' && 'terrain' in tile) {
                        tiles2D[row][col] = {
                            type: this.getTerrainType(tile.terrain),
                            name: tile.terrain,
                            properties: {}, // Default empty properties
                            coordinates: { row: tile.y, col: tile.x }
                        };
                    } else {
                        // Fallback tile if data is missing
                        tiles2D[row][col] = {
                            type: TerrainType.PLAINS,
                            name: 'plains',
                            properties: {},
                            coordinates: { row, col }
                        };
                    }
                }
            }
            return tiles2D;
        }

        console.warn('Unexpected tiles data format:', typeof mapResource.tiles, mapResource.tiles);
        return [];
    }

    /**
     * Convert string terrain to TerrainType enum
     */
    private getTerrainType(terrain: string): TerrainType {
        // Try to match the terrain string to TerrainType enum values
        const terrainLower = terrain.toLowerCase();
        
        if (Object.values(TerrainType).includes(terrainLower as TerrainType)) {
            return terrainLower as TerrainType;
        }
        
        // Default to plains if we can't determine
        return TerrainType.PLAINS;
    }

    disconnect(): void {
        if (this.gameManager) {
            this.gameManager.destroy();
        }
        this.gameManager = null;
    }
}

