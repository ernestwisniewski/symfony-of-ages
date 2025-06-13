<?php

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Infrastructure\Generic\Account\Doctrine\User;
use App\Tests\Factory\UserFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class GameApiTest extends ApiTestCase
{
    use Factories;

    private User $testUser;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Clean database before each test
        $this->cleanDatabase();
        
        // Create user with properly hashed password
        $this->testUser = UserFactory::createOne([
            'email' => 'test' . uniqid() . '@example.com',
            'roles' => ['ROLE_USER'],
        ])->_real();
        
        // Re-hash the password with the correct hasher
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->testUser->setPassword($passwordHasher->hashPassword($this->testUser, 'password'));
        
        // Persist the user
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    private function cleanDatabase(): void
    {
        // Get all entity classes
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        // Disable foreign key checks for PostgreSQL
        $this->entityManager->getConnection()->executeStatement('SET session_replication_role = replica');
        
        foreach ($metadatas as $metadata) {
            $tableName = $metadata->getTableName();
            $quotedTableName = '"' . $tableName . '"';
            $this->entityManager->getConnection()->executeStatement("TRUNCATE TABLE $quotedTableName CASCADE");
        }
        
        // Re-enable foreign key checks for PostgreSQL
        $this->entityManager->getConnection()->executeStatement('SET session_replication_role = DEFAULT');
    }

    private function createAuthenticatedClient()
    {
        $client = static::createClient();
        $response = $client->request('POST', '/api/login_check', [
            'json' => [
                'email' => $this->testUser->getEmail(),
                'password' => 'password'
            ]
        ]);

        $data = $response->toArray(false);
        $token = $data['token'] ?? null;

        $client->setDefaultOptions([
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/ld+json'
            ]
        ]);

        return $client;
    }

    public function testCreateGame(): void
    {
        // Given
        $client = $this->createAuthenticatedClient();

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
        $this->assertGreaterThanOrEqual(2, count($responseData['member']));
    }

    public function testGetSingleGame(): void
    {
        // Given - Create a game first
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/games', [
            'json' => ['name' => 'Test Game for Retrieval']
        ]);
        $this->assertResponseStatusCodeSame(202);

        // Get the created game ID
        $gamesResponse = $client->request('GET', '/api/games');
        $games = $gamesResponse->toArray();
        $gameId = $games['member'][0]['gameId'];

        // When
        $response = $client->request('GET', '/api/games/' . $gameId);

        // Then
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Game',
            '@type' => 'Game',
            'gameId' => $gameId,
            'name' => 'Test Game for Retrieval',
            'status' => 'waiting'
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

        // Add a second player (minimum 2 players required)
        $client->request('POST', '/api/games/' . $gameId . '/join', [
            'json' => ['playerId' => Uuid::v4()->toRfc4122()]
        ]);
        $this->assertResponseStatusCodeSame(202);

        // When
        $client->request('POST', '/api/games/' . $gameId . '/start', [
            'json' => [],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        // Then
        $this->assertResponseStatusCodeSame(202);
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
        $this->assertResponseStatusCodeSame(202);
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
        $this->assertResponseStatusCodeSame(202);

        // Start the game
        $client->request('POST', '/api/games/' . $gameId . '/start', [
            'json' => []
        ]);
        $this->assertResponseStatusCodeSame(202);

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
        $this->assertResponseStatusCodeSame(500);
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
