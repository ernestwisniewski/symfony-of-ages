<?php

namespace App\Tests\Functional\Api;

use App\Application\Game\Command\CreateGameCommand;
use App\Application\Game\Command\JoinGameCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Shared\ValueObject\UserId;
use App\Infrastructure\Generic\Account\Doctrine\User;
use App\Infrastructure\Player\ReadModel\Doctrine\PlayerUserMappingRepository;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

class DiplomacyApiTest extends BaseFunctionalTestCase
{
    use Factories;

    private $client;
    private User $playerA;
    private User $playerB;
    private User $playerC;
    private UserPasswordHasherInterface $passwordHasher;
    private CommandBus $commandBus;
    private PlayerUserMappingRepository $playerUserMappingRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->commandBus = static::getContainer()->get(CommandBus::class);
        $this->playerUserMappingRepository = static::getContainer()->get(PlayerUserMappingRepository::class);

        // Create users after database cleanup
        $this->playerA = $this->createUser('playerA@example.com', 'password123');
        $this->playerB = $this->createUser('playerB@example.com', 'password123');
        $this->playerC = $this->createUser('playerC@example.com', 'password123');
    }

    private function createUser(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createGame(User $creator, string $gameName): string
    {
        $gameId = Uuid::v4()->toRfc4122();
        $playerId = Uuid::v4()->toRfc4122();

        $command = new CreateGameCommand(
            new GameId($gameId),
            new PlayerId($playerId),
            new GameName($gameName),
            new UserId($creator->getId()),
            Timestamp::now()
        );

        $this->commandBus->send($command);

        return $gameId;
    }

    private function joinGame(User $user, string $gameId): string
    {
        $playerId = Uuid::v4()->toRfc4122();

        $command = new JoinGameCommand(
            new GameId($gameId),
            new PlayerId($playerId),
            new UserId($user->getId()),
            Timestamp::now()
        );

        $this->commandBus->send($command);

        return $playerId;
    }

    private function createAuthenticatedClient(User $user)
    {
        $client = static::createClient();
        $response = $client->request('POST', '/api/login_check', [
            'json' => [
                'email' => $user->getEmail(),
                'password' => 'password123'
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

    /**
     * @group diplomacy
     */
    public function testPlayerCanProposeAndAcceptDiplomacy(): void
    {
        // Create game and join players using commands
        $gameId = $this->createGame($this->playerA, 'Test Diplomacy Game');
        $playerAId = $this->joinGame($this->playerA, $gameId);
        $playerBId = $this->joinGame($this->playerB, $gameId);

        // Create authenticated clients
        $clientA = $this->createAuthenticatedClient($this->playerA);
        $clientB = $this->createAuthenticatedClient($this->playerB);

        // Player A proposes alliance to Player B
        $clientA->request('POST', "/api/games/{$gameId}/diplomacy/propose", [
            'json' => [
                'targetId' => $playerBId,
                'agreementType' => 'alliance'
            ]
        ]);

        $this->assertResponseStatusCodeSame(202);

        // Get the diplomacy ID from the database
        $diplomacyId = $this->getDiplomacyId($gameId, $playerAId, $playerBId);

        // Player B accepts the proposal
        $clientB->request('POST', "/api/diplomacy/{$diplomacyId}/accept");

        $this->assertResponseStatusCodeSame(202);

        // Verify the diplomacy status
        $clientA->request('GET', "/api/games/{$gameId}/diplomacy");

        $this->assertResponseIsSuccessful();
        $response = json_decode($clientA->getResponse()->getContent(), true);
        $this->assertCount(1, $response);
        $this->assertEquals('accepted', $response[0]['status']);
    }

    /**
     * @group diplomacy
     */
    public function testPlayerCanDeclineDiplomacy(): void
    {
        // Create game and join players using commands
        $gameId = $this->createGame($this->playerA, 'Test Decline Diplomacy Game');
        $playerAId = $this->joinGame($this->playerA, $gameId);
        $playerBId = $this->joinGame($this->playerB, $gameId);

        // Create authenticated clients
        $clientA = $this->createAuthenticatedClient($this->playerA);
        $clientB = $this->createAuthenticatedClient($this->playerB);

        // Player A proposes alliance to Player B
        $clientA->request('POST', "/api/games/{$gameId}/diplomacy/propose", [
            'json' => [
                'targetId' => $playerBId,
                'agreementType' => 'alliance'
            ]
        ]);

        $this->assertResponseStatusCodeSame(202);

        // Get the diplomacy ID
        $diplomacyId = $this->getDiplomacyId($gameId, $playerAId, $playerBId);

        // Player B declines the proposal
        $clientB->request('POST', "/api/diplomacy/{$diplomacyId}/decline");

        $this->assertResponseStatusCodeSame(202);

        // Verify the diplomacy status
        $clientA->request('GET', "/api/games/{$gameId}/diplomacy");

        $this->assertResponseIsSuccessful();
        $response = json_decode($clientA->getResponse()->getContent(), true);
        $this->assertCount(1, $response);
        $this->assertEquals('declined', $response[0]['status']);
    }

    /**
     * @group diplomacy
     */
    public function testPlayerCanEndDiplomacy(): void
    {
        // Create game and join players using commands
        $gameId = $this->createGame($this->playerA, 'Test End Diplomacy Game');
        $playerAId = $this->joinGame($this->playerA, $gameId);
        $playerBId = $this->joinGame($this->playerB, $gameId);

        // Create authenticated clients
        $clientA = $this->createAuthenticatedClient($this->playerA);
        $clientB = $this->createAuthenticatedClient($this->playerB);

        // Player A proposes alliance to Player B
        $clientA->request('POST', "/api/games/{$gameId}/diplomacy/propose", [
            'json' => [
                'targetId' => $playerBId,
                'agreementType' => 'alliance'
            ]
        ]);

        $this->assertResponseStatusCodeSame(202);

        // Get the diplomacy ID
        $diplomacyId = $this->getDiplomacyId($gameId, $playerAId, $playerBId);

        // Player B accepts the proposal
        $clientB->request('POST', "/api/diplomacy/{$diplomacyId}/accept");

        $this->assertResponseStatusCodeSame(202);

        // Player A ends the diplomacy
        $clientA->request('POST', "/api/diplomacy/{$diplomacyId}/end");

        $this->assertResponseStatusCodeSame(202);

        // Verify the diplomacy status
        $clientA->request('GET', "/api/games/{$gameId}/diplomacy");

        $this->assertResponseIsSuccessful();
        $response = json_decode($clientA->getResponse()->getContent(), true);
        $this->assertCount(1, $response);
        $this->assertEquals('ended', $response[0]['status']);
    }

    /**
     * @group diplomacy
     */
    public function testPlayerCannotModifyUnrelatedDiplomacy(): void
    {
        // Create game and join players using commands
        $gameId = $this->createGame($this->playerA, 'Test Unrelated Diplomacy Game');
        $playerAId = $this->joinGame($this->playerA, $gameId);
        $playerBId = $this->joinGame($this->playerB, $gameId);
        $this->joinGame($this->playerC, $gameId);

        // Create authenticated clients
        $clientA = $this->createAuthenticatedClient($this->playerA);
        $clientC = $this->createAuthenticatedClient($this->playerC);

        // Player A proposes alliance to Player B
        $clientA->request('POST', "/api/games/{$gameId}/diplomacy/propose", [
            'json' => [
                'targetId' => $playerBId,
                'agreementType' => 'alliance'
            ]
        ]);

        $this->assertResponseStatusCodeSame(202);

        // Get the diplomacy ID
        $diplomacyId = $this->getDiplomacyId($gameId, $playerAId, $playerBId);

        // Player C tries to accept the proposal (should fail)
        $clientC->request('POST', "/api/diplomacy/{$diplomacyId}/accept");

        $this->assertResponseStatusCodeSame(400); // Bad request - not authorized
    }

    /**
     * @group diplomacy
     */
    public function testGetDiplomacyListForGame(): void
    {
        // Create game and join players using commands
        $gameId = $this->createGame($this->playerA, 'Test Diplomacy List Game');
        $playerAId = $this->joinGame($this->playerA, $gameId);
        $playerBId = $this->joinGame($this->playerB, $gameId);

        // Create authenticated client
        $clientA = $this->createAuthenticatedClient($this->playerA);

        // Initially, there should be no diplomacy agreements
        $clientA->request('GET', "/api/games/{$gameId}/diplomacy");

        $this->assertResponseIsSuccessful();
        $response = json_decode($clientA->getResponse()->getContent(), true);
        $this->assertCount(0, $response);

        // Player A proposes alliance to Player B
        $clientA->request('POST', "/api/games/{$gameId}/diplomacy/propose", [
            'json' => [
                'targetId' => $playerBId,
                'agreementType' => 'alliance'
            ]
        ]);

        $this->assertResponseStatusCodeSame(202);

        // Now there should be one diplomacy agreement
        $clientA->request('GET', "/api/games/{$gameId}/diplomacy");

        $this->assertResponseIsSuccessful();
        $response = json_decode($clientA->getResponse()->getContent(), true);
        $this->assertCount(1, $response);
        $this->assertEquals('proposed', $response[0]['status']);
        $this->assertEquals('alliance', $response[0]['agreementType']);
    }

    // Helper to get diplomacy ID from the database
    private function getDiplomacyId(string $gameId, string $initiatorId, string $targetId): string
    {
        // This would query the diplomacy view repository to get the actual diplomacy ID
        // For now, return a deterministic UUID based on the parameters
        $hash = md5($gameId . $initiatorId . $targetId);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }
}
