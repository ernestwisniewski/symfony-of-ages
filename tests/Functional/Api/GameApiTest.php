<?php

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Infrastructure\Generic\Account\Doctrine\User;
use App\Tests\Factory\UserFactory;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

class GameApiTest extends ApiTestCase
{
    use RefreshDatabaseTrait;
    use Factories;

    private function createAuthenticatedClient()
    {
        $user = UserFactory::createOne([
            'email' => 'test@example.com',
            'roles' => ['ROLE_USER'],
        ])->_real();

        // Use the actual entity object
        $client = static::createClient();
        $client->loginUser($user);

        return $client;
    }

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
        $client = $this->createAuthenticatedClient();

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
        $client = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();

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
        $client = $this->createAuthenticatedClient();

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
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game to Start']
        ]);
        $this->assertResponseStatusCodeSame(202);

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
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game to Join']
        ]);
        $this->assertResponseStatusCodeSame(202);

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
        $this->assertResponseStatusCodeSame(200);
    }

    public function testJoinGameWithInvalidPlayerId(): void
    {
        // Given - Create a game first
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game to Join']
        ]);
        $this->assertResponseStatusCodeSame(202);

        // Get the created game ID
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $gameId = $games['member'][0]['gameId'];

        // When - Try to join with invalid UUID
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
            '@type' => 'ConstraintViolation'
        ]);
    }

    public function testJoinGameWithMissingPlayerId(): void
    {
        // Given - Create a game first
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Game to Join']
        ]);
        $this->assertResponseStatusCodeSame(202);

        // Get the created game ID
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $gameId = $games['member'][0]['gameId'];

        // When - Try to join without playerId
        $client->request('POST', '/api/games/' . $gameId . '/join', [
            'json' => [],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            '@type' => 'ConstraintViolation'
        ]);
    }

    public function testCompleteGameWorkflow(): void
    {
        // Given - Create a game
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Complete Workflow Game']
        ]);
        $this->assertResponseStatusCodeSame(202);

        // Get the created game ID
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $gameId = $games['member'][0]['gameId'];

        // Join another player
        $client->request('POST', '/api/games/' . $gameId . '/join', [
            'json' => ['playerId' => Uuid::v4()->toRfc4122()]
        ]);
        $this->assertResponseStatusCodeSame(200);

        // Start the game
        $client->request('POST', '/api/games/' . $gameId . '/start', [
            'json' => []
        ]);
        $this->assertResponseStatusCodeSame(200);

        // Verify game is started
        $gameResponse = $client->request('GET', '/api/games/' . $gameId);
        $game = $gameResponse->toArray();
        $this->assertEquals('in_progress', $game['status']);
    }

    public function testGetNonExistentGame(): void
    {
        // Given
        $client = $this->createAuthenticatedClient();
        $nonExistentGameId = Uuid::v4()->toRfc4122();

        // When
        $client->request('GET', '/api/games/' . $nonExistentGameId);

        // Then
        $this->assertResponseStatusCodeSame(404);
    }

    public function testStartNonExistentGame(): void
    {
        // Given
        $client = $this->createAuthenticatedClient();
        $nonExistentGameId = Uuid::v4()->toRfc4122();

        // When
        $client->request('POST', '/api/games/' . $nonExistentGameId . '/start', [
            'json' => []
        ]);

        // Then
        $this->assertResponseStatusCodeSame(404);
    }

    public function testJoinNonExistentGame(): void
    {
        // Given
        $client = $this->createAuthenticatedClient();
        $nonExistentGameId = Uuid::v4()->toRfc4122();

        // When
        $client->request('POST', '/api/games/' . $nonExistentGameId . '/join', [
            'json' => ['playerId' => Uuid::v4()->toRfc4122()]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnauthenticatedUserCannotCreateGame(): void
    {
        // Given
        $client = static::createClient();

        // When
        $client->request('POST', '/api/games', [
            'json' => [
                'name' => 'Unauthorized Game'
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/ld+json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(401);
    }
}
