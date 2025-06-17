<?php

namespace App\Domain\Shared\ValueObject;

/**
 * Centralne miejsce dla wszystkich stałych walidacji w domenie
 * Zgodnie z zasadami DDD, wszystkie reguły biznesowe powinny być zdefiniowane w jednym miejscu
 */
final class ValidationConstants
{
    // City validation constants
    public const int MIN_CITY_NAME_LENGTH = 2;
    public const int MAX_CITY_NAME_LENGTH = 30;
    public const int MAX_CITY_NAME_LENGTH_DOMAIN = 255;

    // Unit validation constants
    public const int MIN_UNIT_NAME_LENGTH = 2;
    public const int MAX_UNIT_NAME_LENGTH = 50;

    // Game validation constants
    public const int MIN_GAME_NAME_LENGTH = 3;
    public const int MAX_GAME_NAME_LENGTH = 100;
    public const int MAX_GAME_NAME_LENGTH_DOMAIN = 120;

    // Player validation constants
    public const int MIN_PLAYER_NAME_LENGTH = 2;
    public const int MAX_PLAYER_NAME_LENGTH = 50;

    // Position validation constants
    public const int MIN_POSITION_VALUE = 0;
    public const int MAX_POSITION_VALUE = 1000;

    // Health validation constants
    public const int MIN_HEALTH_VALUE = 0;
    public const int MAX_HEALTH_VALUE = 1000;

    // Movement validation constants
    public const int MIN_MOVEMENT_RANGE = 1;
    public const int MAX_MOVEMENT_RANGE = 10;

    // Combat validation constants
    public const int MIN_ATTACK_POWER = 1;
    public const int MAX_ATTACK_POWER = 100;
    public const int MIN_DEFENSE_POWER = 0;
    public const int MAX_DEFENSE_POWER = 50;

    // Terrain analysis constants
    public const int MIN_DEFENSE_BONUS_FOR_DEFENSIVE_POSITION = 2;
    public const int MIN_MOVEMENT_COST_FOR_QUICK_TRAVERSAL = 1;
    public const int MAX_MOVEMENT_COST_FOR_QUICK_TRAVERSAL = 1;
    public const int MIN_MOVEMENT_COST_FOR_SPECIAL_MOVEMENT = 3;
    public const int MIN_RESOURCE_YIELD_FOR_ECONOMICALLY_VIABLE = 3;
    public const int MIN_DEFENSE_BONUS_FOR_STRATEGIC_IMPORTANCE = 3;
    public const int MIN_RESOURCE_YIELD_FOR_STRATEGIC_IMPORTANCE = 3;
    public const int MIN_MOVEMENT_COST_FOR_DIFFICULT_TRAVERSAL = 3;
    public const int MIN_DEFENSE_BONUS_FOR_FORTIFIED = 4;
    public const int MIN_RESOURCE_YIELD_FOR_RESOURCE_RICH = 4;

    // Game configuration constants
    public const int MIN_PLAYERS_PER_GAME = 2;
    public const int MAX_PLAYERS_PER_GAME = 4;
    public const int DEFAULT_MAP_SIZE = 32;
    public const int MIN_MAP_SIZE = 10;
    public const int MAX_MAP_SIZE = 100;

    // Combat system constants
    public const int ATTACK_RANGE = 1;
    public const int MIN_DAMAGE = 1;

    // Resource constants
    public const int MIN_RESOURCE_VALUE = 0;
    public const int MAX_RESOURCE_VALUE = 1000;

    // Time constants
    public const int MIN_TURN_NUMBER = 0;
    public const int MAX_TURN_NUMBER = 9999;

    // Authentication constants
    public const int MIN_PASSWORD_LENGTH = 6;
    public const int MAX_PASSWORD_LENGTH = 4096;
    public const int MIN_EMAIL_LENGTH = 5;
    public const int MAX_EMAIL_LENGTH = 255;
} 