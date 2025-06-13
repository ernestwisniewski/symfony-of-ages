/**
 * API Service for communicating with the Symfony API Platform backend
 * Handles all API calls for games, units, cities, maps, and turns
 */

export interface GameData {
  gameId: string;
  name: string;
  status: string;
  players: string[];
  currentTurn: number;
  activePlayer: string;
  createdAt: string;
  startedAt?: string;
}

export interface UnitData {
  unitId: string;
  ownerId: string;
  gameId: string;
  type: string;
  position: { x: number; y: number };
  currentHealth: number;
  maxHealth: number;
  isDead: boolean;
  attackPower: number;
  defensePower: number;
  movementRange: number;
}

export interface CityData {
  cityId: string;
  ownerId: string;
  gameId: string;
  name: string;
  position: { x: number; y: number };
}

export interface MapData {
  gameId: string;
  width: number;
  height: number;
  tiles: any[][];
  generatedAt: string;
}

export interface TurnData {
  gameId: string;
  activePlayer: string;
  currentTurn: number;
  turnEndedAt?: string;
}

export interface CreateGameRequest {
  name: string;
}

export interface JoinGameRequest {
  playerId: string;
}

export interface CreateUnitRequest {
  playerId: string;
  unitType: string;
  x: number;
  y: number;
}

export interface MoveUnitRequest {
  toX: number;
  toY: number;
}

export interface AttackUnitRequest {
  targetUnitId: string;
}

export interface CreateCityRequest {
  playerId: string;
  name: string;
  x: number;
  y: number;
}

export class ApiService {
  private baseUrl: string;

  constructor(baseUrl: string = '/api') {
    this.baseUrl = baseUrl;
  }

  /**
   * Get all games
   */
  async getGames(): Promise<GameData[]> {
    const response = await fetch(`${this.baseUrl}/games`, {
      headers: {
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch games: ${response.statusText}`);
    }

    const data = await response.json();
    return data['hydra:member'] || [];
  }

  /**
   * Get user's games
   */
  async getUserGames(): Promise<GameData[]> {
    const response = await fetch(`${this.baseUrl}/user/games`, {
      headers: {
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch user games: ${response.statusText}`);
    }

    const data = await response.json();
    return data['hydra:member'] || [];
  }

  /**
   * Get a specific game
   */
  async getGame(gameId: string): Promise<GameData> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}`, {
      headers: {
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch game: ${response.statusText}`);
    }

    return await response.json();
  }

  /**
   * Create a new game
   */
  async createGame(request: CreateGameRequest): Promise<void> {
    const response = await fetch(`${this.baseUrl}/games`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        'Accept': 'application/ld+json',
      },
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      throw new Error(`Failed to create game: ${response.statusText}`);
    }
  }

  /**
   * Start a game
   */
  async startGame(gameId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}/start`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to start game: ${response.statusText}`);
    }
  }

  /**
   * Join a game
   */
  async joinGame(gameId: string, request: JoinGameRequest): Promise<void> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}/join`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        'Accept': 'application/ld+json',
      },
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      throw new Error(`Failed to join game: ${response.statusText}`);
    }
  }

  /**
   * Get map for a game
   */
  async getMap(gameId: string): Promise<MapData> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}/map`, {
      headers: {
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch map: ${response.statusText}`);
    }

    return await response.json();
  }

  /**
   * Get units for a game
   */
  async getUnits(gameId: string): Promise<UnitData[]> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}/units`, {
      headers: {
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch units: ${response.statusText}`);
    }

    const data = await response.json();
    return data['hydra:member'] || [];
  }

  /**
   * Get a specific unit
   */
  async getUnit(unitId: string): Promise<UnitData> {
    const response = await fetch(`${this.baseUrl}/units/${unitId}`, {
      headers: {
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch unit: ${response.statusText}`);
    }

    return await response.json();
  }

  /**
   * Create a unit
   */
  async createUnit(gameId: string, request: CreateUnitRequest): Promise<void> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}/units`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        'Accept': 'application/ld+json',
      },
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      throw new Error(`Failed to create unit: ${response.statusText}`);
    }
  }

  /**
   * Move a unit
   */
  async moveUnit(unitId: string, request: MoveUnitRequest): Promise<void> {
    const response = await fetch(`${this.baseUrl}/units/${unitId}/move`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/ld+json',
        'Accept': 'application/ld+json',
      },
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      throw new Error(`Failed to move unit: ${response.statusText}`);
    }
  }

  /**
   * Attack with a unit
   */
  async attackUnit(unitId: string, request: AttackUnitRequest): Promise<void> {
    const response = await fetch(`${this.baseUrl}/units/${unitId}/attack`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        'Accept': 'application/ld+json',
      },
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      throw new Error(`Failed to attack unit: ${response.statusText}`);
    }
  }

  /**
   * Get cities for a game
   */
  async getCities(gameId: string): Promise<CityData[]> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}/cities`, {
      headers: {
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch cities: ${response.statusText}`);
    }

    const data = await response.json();
    return data['hydra:member'] || [];
  }

  /**
   * Get a specific city
   */
  async getCity(cityId: string): Promise<CityData> {
    const response = await fetch(`${this.baseUrl}/cities/${cityId}`, {
      headers: {
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch city: ${response.statusText}`);
    }

    return await response.json();
  }

  /**
   * Create a city
   */
  async createCity(gameId: string, request: CreateCityRequest): Promise<void> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}/cities`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        'Accept': 'application/ld+json',
      },
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      throw new Error(`Failed to create city: ${response.statusText}`);
    }
  }

  /**
   * Get current turn for a game
   */
  async getCurrentTurn(gameId: string): Promise<TurnData> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}/current-turn`, {
      headers: {
        'Accept': 'application/ld+json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch current turn: ${response.statusText}`);
    }

    return await response.json();
  }

  /**
   * End turn for a game
   */
  async endTurn(gameId: string, playerId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/games/${gameId}/end-turn`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        'Accept': 'application/ld+json',
      },
      body: JSON.stringify({ playerId }),
    });

    if (!response.ok) {
      throw new Error(`Failed to end turn: ${response.statusText}`);
    }
  }
} 