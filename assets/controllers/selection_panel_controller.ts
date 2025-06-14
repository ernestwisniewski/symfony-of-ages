import {Controller} from '@hotwired/stimulus'
import {Component, getComponent} from '@symfony/ux-live-component';
import type { TerrainTile, UnitData, CityData, GridPosition } from '../game/core'

/**
 * Selection Panel Controller
 * Handles communication between game events and the Live Component selection panel
 */
export default class extends Controller<HTMLElement> {
    private component: Component | null = null;

    connect(): void {
        this.initializeComponent();
        this.setupInteractionHandlers();
    }

    async initializeComponent(): Promise<void> {
        try {
            this.component = await getComponent(this.element);
        } catch (error) {
            console.error('Failed to initialize Live Component:', error);
        }
    }

    setupInteractionHandlers(): void {
        // Listen for hex click events from the game map
        document.addEventListener('hexclick', (event: Event) => {
            const customEvent = event as CustomEvent;
            this.handleHexSelection(customEvent.detail);
        });

        // Listen for unit click events from the game map
        document.addEventListener('unitclick', (event: Event) => {
            const customEvent = event as CustomEvent;
            this.handleUnitSelection(customEvent.detail);
        });

        // Listen for city click events from the game map
        document.addEventListener('cityclick', (event: Event) => {
            const customEvent = event as CustomEvent;
            this.handleCitySelection(customEvent.detail);
        });

        // Listen for escape key to close panel
        document.addEventListener('keydown', (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                this.closePanel();
            }
        });
    }

    /**
     * Handle hex tile selection
     */
    private handleHexSelection(hexData: { row: number; col: number; terrainData: TerrainTile }): void {
        if (!this.component) return;

        const payload = this.formatHexPayload(hexData);
        this.component.emit('open', { type: 'hex', payload });
    }

    /**
     * Handle unit selection
     */
    private handleUnitSelection(unitData: { playerData: UnitData }): void {
        if (!this.component) return;

        const payload = this.formatUnitPayload(unitData.playerData);
        this.component.emit('open', { type: 'unit', payload });
    }

    /**
     * Handle city selection
     */
    private handleCitySelection(cityData: { cityData: CityData }): void {
        if (!this.component) return;

        const payload = this.formatCityPayload(cityData.cityData);
        this.component.emit('open', { type: 'city', payload });
    }

    /**
     * Format hex data for the Live Component payload
     */
    private formatHexPayload(hexData: { row: number; col: number; terrainData: TerrainTile }): Record<string, any> {
        const { row, col, terrainData } = hexData;
        
        // Ensure we have a valid terrain type
        const terrainType = terrainData.type || this.detectTerrainType(terrainData);
        const terrainName = this.getTerrainDisplayName(terrainData);
        
        return {
            terrainName: terrainName,
            terrainType: terrainType,
            position: { row, col },
            movementCost: terrainData.properties?.movementCost ?? 1,
            defense: terrainData.properties?.defenseBonus ?? 0,
            resources: this.calculateResources(terrainData.properties),
            properties: terrainData.properties,
            coordinates: `(${row}, ${col})`
        };
    }

    /**
     * Detect terrain type from terrain data if not explicitly set
     */
    private detectTerrainType(terrainData: TerrainTile): string {
        // Try to detect from properties or other fields
        if (terrainData.properties) {
            // Check if it's water (impassable)
            if (terrainData.properties.impassable) {
                return 'water';
            }
            
            // Check movement cost to guess terrain type
            if (terrainData.properties.movementCost === 2) {
                return 'forest';
            }
            if (terrainData.properties.movementCost === 3) {
                return 'mountain';
            }
        }
        
        // Default to plains if we can't determine
        return 'plains';
    }

    /**
     * Get display name for terrain type
     */
    private getTerrainDisplayName(terrainData: TerrainTile): string {
        // If terrain has a name, use it
        if (terrainData.name && terrainData.name !== 'Unknown') {
            return terrainData.name;
        }

        // Get terrain type (either from data or detected)
        const terrainType = terrainData.type || this.detectTerrainType(terrainData);

        // Generate name from type
        const typeNames: Record<string, string> = {
            'plains': 'Plains',
            'forest': 'Forest',
            'mountain': 'Mountain',
            'water': 'Water',
            'desert': 'Desert',
            'swamp': 'Swamp'
        };

        return typeNames[terrainType] || 'Unknown Terrain';
    }

    /**
     * Format unit data for the Live Component payload
     */
    private formatUnitPayload(unitData: UnitData): Record<string, any> {
        return {
            type: unitData.type,
            ownerId: unitData.ownerId,
            position: unitData.position,
            movementRange: unitData.movementRange,
            currentHealth: unitData.currentHealth,
            maxHealth: unitData.maxHealth,
            health: Math.round((unitData.currentHealth / unitData.maxHealth) * 100),
            attack: unitData.attackPower,
            defense: unitData.defensePower,
            isDead: unitData.isDead,
            unitId: unitData.unitId
        };
    }

    /**
     * Format city data for the Live Component payload
     */
    private formatCityPayload(cityData: CityData): Record<string, any> {
        return {
            name: cityData.name,
            ownerId: cityData.ownerId,
            position: cityData.position,
            cityId: cityData.cityId,
            // Default values for city properties (can be extended later)
            population: 1000,
            production: 10,
            food: 20,
            gold: 50
        };
    }

    /**
     * Calculate resources from terrain properties
     */
    private calculateResources(properties: any): number {
        if (!properties) return 0;
        
        let resources = 0;
        if (properties.foodBonus) resources += properties.foodBonus;
        if (properties.goldBonus) resources += properties.goldBonus;
        if (properties.productionBonus) resources += properties.productionBonus;
        
        return resources;
    }

    /**
     * Close the selection panel
     */
    private closePanel(): void {
        if (this.component) {
            this.component.emit('close', {});
        }
    }

    disconnect(): void {
        this.component = null;
    }
}
