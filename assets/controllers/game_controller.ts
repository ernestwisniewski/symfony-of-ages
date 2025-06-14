import {Controller} from '@hotwired/stimulus'
import {GameManager} from '../game/GameManager'
import type {CityData, GameData, MapData, UnitData} from '../game/core'

interface MapConfig {
    cols: number
    rows: number
    size: number
    mapData: any[][]
}

export default class extends Controller<HTMLElement> {
    static values = {
        gameId: String,
        mapData: Object,
        gameData: Object,
        unitsData: Array,
        citiesData: Array
    }

    declare readonly gameIdValue: string
    declare readonly mapDataValue: MapData
    declare readonly gameDataValue: GameData
    declare readonly unitsDataValue: UnitData[]
    declare readonly citiesDataValue: CityData[]

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

        const config: MapConfig = {
            cols: mapData.width,
            rows: mapData.height,
            size: 32,
            mapData: mapData.tiles
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

    disconnect(): void {
        if (this.gameManager) {
            this.gameManager.destroy();
        }
        this.gameManager = null;
    }
}

