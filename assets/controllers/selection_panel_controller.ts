import {Controller} from '@hotwired/stimulus'
import {Component, getComponent} from '@symfony/ux-live-component';
import {GameManager} from '../game/GameManager'

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

    private handleHexSelection(_hexData: any): void {
        this.component.emit('open', {'type': 'hex', 'payload': _hexData});
    }

    private handleUnitSelection(_unitData: any): void {
        this.component.emit('open', {'type': 'unit', 'payload': _unitData});
    }

    private handleCitySelection(_cityData: any): void {
    }

    private dispatchToSelectionPanel(action: string, args: any[] = []): void {
    }

    disconnect(): void {
        this.gameManager?.destroy()
        this.gameManager = null
    }
}
