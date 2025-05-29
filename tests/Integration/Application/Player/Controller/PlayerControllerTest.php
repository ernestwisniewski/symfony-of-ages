<?php

namespace App\Tests\Integration\Application\Player\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for PlayerController
 */
class PlayerControllerTest extends WebTestCase
{
    public function testCreatePlayerEndpoint(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/player/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test Player'])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('player', $data);
        $this->assertArrayHasKey('message', $data);
        
        $player = $data['player'];
        $this->assertEquals('Test Player', $player['name']);
        $this->assertArrayHasKey('id', $player);
        $this->assertArrayHasKey('position', $player);
        $this->assertArrayHasKey('movementPoints', $player);
        $this->assertArrayHasKey('maxMovementPoints', $player);
        $this->assertArrayHasKey('color', $player);
    }

    public function testCreatePlayerWithEmptyName(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/player/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => ''])
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Invalid player data', $data['message']);
    }

    public function testGetPlayerWhenNoPlayerExists(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/player');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('No player found. Create a player first.', $data['message']);
    }

    public function testGetPlayerAfterCreation(): void
    {
        $client = static::createClient();

        // First create a player
        $client->request(
            'POST',
            '/api/player/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test Player'])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Then get the player
        $client->request('GET', '/api/player');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('player', $data);
        $this->assertEquals('Test Player', $data['player']['name']);
    }

    public function testMovePlayerWithoutPlayer(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/player/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['row' => 10, 'col' => 10])
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('No player found. Create a player first.', $data['message']);
    }

    public function testMovePlayerWithValidMovement(): void
    {
        $client = static::createClient();

        // First create a player
        $client->request(
            'POST',
            '/api/player/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test Player'])
        );

        $createResponse = json_decode($client->getResponse()->getContent(), true);
        $currentPosition = $createResponse['player']['position'];

        // Try to move to an adjacent position
        $newRow = $currentPosition['row'] + 1;
        $newCol = $currentPosition['col'];

        $client->request(
            'POST',
            '/api/player/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['row' => $newRow, 'col' => $newCol])
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        // Movement might succeed or fail depending on terrain, but response should be valid
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
    }

    public function testStartNewTurnWithoutPlayer(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/player/new-turn');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('No player found. Create a player first.', $data['message']);
    }

    public function testStartNewTurnWithPlayer(): void
    {
        $client = static::createClient();

        // First create a player
        $client->request(
            'POST',
            '/api/player/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test Player'])
        );

        // Start new turn
        $client->request('POST', '/api/player/new-turn');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('player', $data);
        $this->assertEquals('New turn started. Movement points restored.', $data['message']);
        
        // Movement points should be restored to maximum
        $player = $data['player'];
        $this->assertEquals($player['maxMovementPoints'], $player['movementPoints']);
    }

    public function testGetPlayerStatus(): void
    {
        $client = static::createClient();

        // First create a player
        $client->request(
            'POST',
            '/api/player/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test Player'])
        );

        // Get player status
        $client->request('GET', '/api/player/status');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('player_status', $data);
        
        $status = $data['player_status'];
        $this->assertArrayHasKey('basic_info', $status);
        $this->assertArrayHasKey('position', $status);
        $this->assertArrayHasKey('movement', $status);
        $this->assertArrayHasKey('turn_status', $status);
    }

    public function testGetTacticalAnalysis(): void
    {
        $client = static::createClient();

        // First create a player
        $client->request(
            'POST',
            '/api/player/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test Player'])
        );

        // Get tactical analysis
        $client->request('GET', '/api/player/tactical-analysis');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('tactical_analysis', $data);
        
        $analysis = $data['tactical_analysis'];
        $this->assertArrayHasKey('current_position', $analysis);
        $this->assertArrayHasKey('current_terrain', $analysis);
        $this->assertArrayHasKey('movement_points', $analysis);
        $this->assertArrayHasKey('surrounding_terrain', $analysis);
        $this->assertArrayHasKey('movement_options', $analysis);
    }

    public function testValidatePosition(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/player/validate-position',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['row' => 50, 'col' => 50])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('validation', $data);
        
        $validation = $data['validation'];
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('reason', $validation);
        $this->assertArrayHasKey('code', $validation);
    }

    public function testValidatePositionWithInvalidData(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/player/validate-position',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['row' => 50]) // Missing 'col'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Row and column coordinates are required', $data['message']);
    }

    public function testInvalidJsonRequest(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/player/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }
} 