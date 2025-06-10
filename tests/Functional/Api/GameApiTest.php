<?php

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Component\Uid\Uuid;

class GameApiTest extends ApiTestCase
{
    use RefreshDatabaseTrait;

    public function testCreateGame(): void
    {
        // Given
        $client = static::createClient();

        // When
        $client->request('POST', '/api/games', [
            'json' => [
                'name' => 'Epic Strategy Game'
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/ld+json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(202);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testCreateGameWithInvalidData(): void
    {
        // Given
        $client = static::createClient();

        // When
        $client->request('POST', '/api/games', [
            'json' => [
                'name' => '' // Empty name should fail validation
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/ld+json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            '@type' => 'ConstraintViolation'
        ]);
    }

    public function testCreateGameWithTooLongName(): void
    {
        // Given
        $client = static::createClient();
        $longName = str_repeat('A', 51);

        // When
        $client->request('POST', '/api/games', [
            'json' => [
                'name' => $longName
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/ld+json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            '@type' => 'ConstraintViolation'
        ]);
    }

    public function testGetGamesCollection(): void
    {
        // Given - Create some games first
        $client = static::createClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game One']
        ]);
        $this->assertResponseStatusCodeSame(202);

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game Two']
        ]);
        $this->assertResponseStatusCodeSame(202);

        // When
        $response = $client->request('GET', '/api/games');

        // Then
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Game',
            '@type' => 'Collection'
        ]);

        $responseData = $response->toArray();
        $this->assertArrayHasKey('member', $responseData);
        $this->assertCount(2, $responseData['member']);
    }

    public function testGetSingleGame(): void
    {
        // Given - Create a game first
        $client = static::createClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Test Game for Retrieval']
        ]);
        $this->assertResponseStatusCodeSame(202);

        // Extract gameId from created game (we need to get it from the collection)
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $gameId = $games['member'][0]['gameId'];

        // When
        $client->request('GET', '/api/games/' . $gameId);

        // Then
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Game',
            '@type' => 'Game',
            'gameId' => $gameId,
            'name' => 'Test Game for Retrieval',
            'status' => 'waiting_for_players'
        ]);
    }

    public function testStartGame(): void
    {
        // Given - Create a game first
        $client = static::createClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game to Start']
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Get the created game ID
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $gameId = $games['member'][0]['gameId'];

        // Add another player to have minimum players
        $client->request('POST', '/api/games/' . $gameId . '/join', [
            'json' => ['playerId' => Uuid::v4()->toRfc4122()]
        ]);

        // When
        $client->request('POST', '/api/games/' . $gameId . '/start', [
            'json' => [],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(200);
    }

    public function testJoinGame(): void
    {
        // Given - Create a game first
        $client = static::createClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game to Join']
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Get the created game ID
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $gameId = $games['member'][0]['gameId'];

        $playerId = Uuid::v4()->toRfc4122();

        // When
        $client->request('POST', '/api/games/' . $gameId . '/join', [
            'json' => [
                'playerId' => $playerId
            ],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(201);
    }

    public function testJoinGameWithInvalidPlayerId(): void
    {
        // Given - Create a game first
        $client = static::createClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game for Invalid Join']
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Get the created game ID
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $gameId = $games['member'][0]['gameId'];

        // When
        $client->request('POST', '/api/games/' . $gameId . '/join', [
            'json' => [
                'playerId' => 'invalid-uuid'
            ],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            '@type' => 'ConstraintViolationList'
        ]);
    }

    public function testJoinGameWithMissingPlayerId(): void
    {
        // Given - Create a game first
        $client = static::createClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game for Missing Player ID']
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Get the created game ID
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $gameId = $games['member'][0]['gameId'];

        // When
        $response = $client->request('POST', '/api/games/' . $gameId . '/join', [
            'json' => [], // Missing playerId
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            '@type' => 'ConstraintViolationList'
        ]);
    }

    public function testCompleteGameWorkflow(): void
    {
        // Given
        $client = static::createClient();

        // Step 1: Create a game
        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Complete Workflow Game']
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Step 2: Get the game and verify it's created
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $this->assertCount(1, $games['member']);

        $game = $games['member'][0];
        $gameId = $game['gameId'];
        $this->assertEquals('Complete Workflow Game', $game['name']);
        $this->assertEquals('waiting_for_players', $game['status']);

        // Step 3: Add a second player
        $secondPlayerId = Uuid::v4()->toRfc4122();
        $client->request('POST', '/api/games/' . $gameId . '/join', [
            'json' => ['playerId' => $secondPlayerId]
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Step 4: Start the game
        $client->request('POST', '/api/games/' . $gameId . '/start', [
            'json' => []
        ]);
        $this->assertResponseStatusCodeSame(200);

        // Step 5: Verify game status changed
        $updatedGameResponse = $client->request('GET', '/api/games/' . $gameId);
        $this->assertResponseIsSuccessful();
        $updatedGame = $updatedGameResponse->toArray();
        $this->assertEquals('in_progress', $updatedGame['status']);
        $this->assertArrayHasKey('startedAt', $updatedGame);
        $this->assertNotNull($updatedGame['startedAt']);
    }

    public function testGetNonExistentGame(): void
    {
        // Given
        $client = static::createClient();
        $nonExistentId = Uuid::v4()->toRfc4122();

        // When
        $response = $client->request('GET', '/api/games/' . $nonExistentId);

        // Then
        $this->assertResponseStatusCodeSame(404);
    }

    public function testStartNonExistentGame(): void
    {
        // Given
        $client = static::createClient();
        $nonExistentId = Uuid::v4()->toRfc4122();

        // When
        $response = $client->request('POST', '/api/games/' . $nonExistentId . '/start', [
            'json' => []
        ]);

        // Then
        $this->assertResponseStatusCodeSame(404);
    }

    public function testJoinNonExistentGame(): void
    {
        // Given
        $client = static::createClient();
        $nonExistentId = Uuid::v4()->toRfc4122();
        $playerId = Uuid::v4()->toRfc4122();

        // When
        $response = $client->request('POST', '/api/games/' . $nonExistentId . '/join', [
            'json' => ['playerId' => $playerId]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(404);
    }
}
