import {City} from './City';
import {Unit} from './Unit';
import {HexGrid} from '../map/HexGrid';
import {HexGeometry} from '../map/HexGeometry';
import {CameraController} from '../map/CameraController';
import type {CityData, UnitData} from '../core/types';
import type {MapConfig} from '../map/types';

/**
 * PlayerManager handles all player-related operations
 * Manages cities and units as pulsating dots on the map
 * Player no longer has direct representation on the map
 */
export class PlayerManager {
  private hexGrid: HexGrid;
  private cameraController: CameraController;
  private config: MapConfig;
  private cities: Map<string, City> = new Map();
  private units: Map<string, Unit> = new Map();

  constructor(hexGrid: HexGrid, cameraController: CameraController, config: MapConfig) {
    this.hexGrid = hexGrid;
    this.cameraController = cameraController;
    this.config = config;
  }

  /**
   * Adds a city to the map
   * @param cityData - City data from backend
   */
  addCity(cityData: CityData): void {
    // Remove existing city if any
    this.removeCity(cityData.id);

    // Create new city sprite
    const city = new City(cityData, this.config.size);
    this.cities.set(cityData.id, city);
    this.hexGrid.addChild(city.sprite);

    // Setup city click handling
    city.sprite.on('cityclick', (event: any) => {
      this.hexGrid.emit('cityclick', event);
    });
  }

  /**
   * Updates city position and data
   */
  updateCity(cityData: CityData): void {
    const city = this.cities.get(cityData.id);
    if (city) {
      city.updateData(cityData);
    } else {
      // If city doesn't exist, add it
      this.addCity(cityData);
    }
  }

  /**
   * Removes city from the map
   */
  removeCity(cityId: string): void {
    const city = this.cities.get(cityId);
    if (city) {
      this.hexGrid.removeChild(city.sprite);
      city.destroy();
      this.cities.delete(cityId);
    }
  }

  /**
   * Adds a unit to the map
   * @param unitData - Unit data from backend
   */
  addUnit(unitData: UnitData): void {
    // Remove existing unit if any
    this.removeUnit(unitData.id);

    // Create new unit sprite
    const unit = new Unit(unitData, this.config.size);
    this.units.set(unitData.id, unit);
    this.hexGrid.addChild(unit.sprite);

    // Setup unit click handling
    unit.sprite.on('unitclick', (event: any) => {
      this.hexGrid.emit('unitclick', event);
    });
  }

  /**
   * Updates unit position and data
   */
  updateUnit(unitData: UnitData): void {
    const unit = this.units.get(unitData.id);
    if (unit) {
      unit.updateData(unitData);
    } else {
      // If unit doesn't exist, add it
      this.addUnit(unitData);
    }
  }

  /**
   * Removes unit from the map
   */
  removeUnit(unitId: string): void {
    const unit = this.units.get(unitId);
    if (unit) {
      this.hexGrid.removeChild(unit.sprite);
      unit.destroy();
      this.units.delete(unitId);
    }
  }

  /**
   * Updates all cities with new data
   */
  updateCities(citiesData: CityData[]): void {
    // Remove cities that no longer exist
    const currentCityIds = new Set(this.cities.keys());
    const newCityIds = new Set(citiesData.map(city => city.id));

    // Remove cities that are no longer in the data
    for (const cityId of currentCityIds) {
      if (!newCityIds.has(cityId)) {
        this.removeCity(cityId);
      }
    }

    // Add or update cities
    citiesData.forEach(cityData => {
      this.updateCity(cityData);
    });
  }

  /**
   * Updates all units with new data
   */
  updateUnits(unitsData: UnitData[]): void {
    // Remove units that no longer exist
    const currentUnitIds = new Set(this.units.keys());
    const newUnitIds = new Set(unitsData.map(unit => unit.id));

    // Remove units that are no longer in the data
    for (const unitId of currentUnitIds) {
      if (!newUnitIds.has(unitId)) {
        this.removeUnit(unitId);
      }
    }

    // Add or update units
    unitsData.forEach(unitData => {
      this.updateUnit(unitData);
    });
  }

  /**
   * Gets all cities
   */
  getCities(): Map<string, City> {
    return this.cities;
  }

  /**
   * Gets all units
   */
  getUnits(): Map<string, Unit> {
    return this.units;
  }

  /**
   * Gets a specific city by ID
   */
  getCity(cityId: string): City | undefined {
    return this.cities.get(cityId);
  }

  /**
   * Gets a specific unit by ID
   */
  getUnit(unitId: string): Unit | undefined {
    return this.units.get(unitId);
  }

  /**
   * Gets city at specific position
   */
  getCityAtPosition(x: number, y: number): City | undefined {
    for (const city of this.cities.values()) {
      if (city.isAtPosition(x, y)) {
        return city;
      }
    }
    return undefined;
  }

  /**
   * Gets unit at specific position
   */
  getUnitAtPosition(x: number, y: number): Unit | undefined {
    for (const unit of this.units.values()) {
      if (unit.isAtPosition(x, y)) {
        return unit;
      }
    }
    return undefined;
  }

  /**
   * Centers camera on a specific position (for cities/units)
   */
  centerCameraOnPosition(x: number, y: number): void {
    const worldPos = this.calculateWorldPosition(x, y);
    this.cameraController.centerCameraOnPlayer(worldPos);
  }

  /**
   * Calculates world position from grid coordinates
   */
  private calculateWorldPosition(x: number, y: number): { x: number, y: number } {
    // Convert grid coordinates to world coordinates
    const hexGeometry = new HexGeometry(this.config.size);
    const worldPos = hexGeometry.calculatePosition(y, x);

    // Account for hexGrid transforms
    const hexGridWorldX = this.hexGrid.x;
    const hexGridWorldY = this.hexGrid.y;

    const worldX = hexGridWorldX + (worldPos.x - this.hexGrid.pivot.x) * this.hexGrid.scale.x;
    const worldY = hexGridWorldY + (worldPos.y - this.hexGrid.pivot.y) * this.hexGrid.scale.y;

    return {x: worldX, y: worldY};
  }

  /**
   * Removes all cities and units from the map
   */
  clearAll(): void {
    // Remove all cities
    for (const cityId of this.cities.keys()) {
      this.removeCity(cityId);
    }

    // Remove all units
    for (const unitId of this.units.keys()) {
      this.removeUnit(unitId);
    }
  }

  /**
   * Cleanup all resources
   */
  destroy(): void {
    this.clearAll();
  }
}
