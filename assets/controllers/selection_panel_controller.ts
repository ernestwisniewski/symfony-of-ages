import {Controller} from '@hotwired/stimulus'
import {Component, getComponent} from '@symfony/ux-live-component';
import {GameManager} from '../game/GameManager'
import type { TerrainTile, UnitData, CityData } from '../game/core'

export default class extends Controller<HTMLElement> {
    private gameManager: GameManager | null = null
    private component: Component;

    connect(): void {
        this.setupInteractionHandlers()
    }

    async initialize() {
        this.component = await getComponent(this.element);
    }

    setupInteractionHandlers(): void {
        document.addEventListener('hexclick', (event: any) => {
            this.handleHexSelection(event.detail)
        })
        document.addEventListener('unitclick', (event: any) => {
            this.handleUnitSelection(event.detail)
        })
        document.addEventListener('cityclick', (event: any) => {
            this.handleCitySelection(event.detail)
        })

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.dispatchToSelectionPanel('close')
            }
        })
    }

    private handleHexSelection(hexData: { row: number; col: number; terrainData: TerrainTile }): void {
        this.component.emit('open', {'type': 'hex', 'payload': hexData});
    }

    private handleUnitSelection(unitData: { playerData: UnitData }): void {
        this.component.emit('open', {'type': 'unit', 'payload': unitData});
    }

    private handleCitySelection(cityData: { cityData: CityData }): void {
        this.component.emit('open', {'type': 'city', 'payload': cityData});
    }

    private dispatchToSelectionPanel(action: string, args: any[] = []): void {
        // Implementation for dispatching to selection panel
    }

    disconnect(): void {
        this.gameManager?.destroy()
        this.gameManager = null
    }
}
