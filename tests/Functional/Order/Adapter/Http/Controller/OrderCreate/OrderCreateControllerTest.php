<?php

declare(strict_types=1);

namespace Test\Functional\Order\Adapter\Http\Controller\OrderCreate;

use Common\Domain\Response\RESPONSE_STATUS;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Response;
use Test\Functional\WebClientTestCase;

class OrderCreateControllerTest extends WebClientTestCase
{
    use ReloadDatabaseTrait;

    private const ENDPOINT = '/api/v1/orders';
    private const METHOD = 'POST';
    private const PATH_IMAGE_UPLOAD = __DIR__.'/Fixtures/Image.png';
    private const PATH_FIXTURES = __DIR__.'/Fixtures';
    private const PATH_IMAGE_BACKUP = 'tests/Fixtures/Files/Image.png';
    private const PATH_IMAGE_NOT_ALLOWED = __DIR__.'/Fixtures/MimeTypeNotAllowed.txt';
    private const PATH_IMAGE_NOT_ALLOWED_BACKUP = 'tests/Fixtures/Files/MimeTypeNotAllowed.txt';
    private const PATH_IMAGES_GROUP_PUBLIC = 'public/assets/img/products';
    private const USER_HAS_NO_GROUP_EMAIL = 'email.other_2.active@host.com';
    private const USER_HAS_NO_GROUP_PASSWORD = '123456';
    private const GROUP_EXISTS_ID = '4b513296-14ac-4fb1-a574-05bc9b1dbe3f';
    private const GROUP_ID_EXISTS_USER_NOT_BELONGS = '4d52266f-aa7e-324e-b92d-6152635dd09e';

    private string $pathImageProduct;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function getOrdersData(): array
    {
        return [
            [
                'product_id' => 'afc62bc9-c42c-4c4d-8098-09ce51414a92',
                'shop_id' => 'e6c1d350-f010-403c-a2d4-3865c14630ec',
                'description' => 'order 1 description',
                'amount' => 10.56,
            ],
            [
                'product_id' => '7e3021d4-2d02-4386-8bbe-887cfe8697a8',
                'shop_id' => 'e6c1d350-f010-403c-a2d4-3865c14630ec',
                'description' => 'order 2 description',
                'amount' => 20.56,
            ],
            [
                'product_id' => '8b6d650b-7bb7-4850-bf25-36cda9bce801',
                'shop_id' => 'f6ae3da3-c8f2-4ccb-9143-0f361eec850e',
                'description' => 'order 3 description',
                'amount' => 30.56,
            ],
        ];
    }

    /** @test */
    public function itShouldCreateOrders(): void
    {
        $ordersData = $this->getOrdersData();
        $client = $this->getNewClientAuthenticatedUser();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => self::GROUP_EXISTS_ID,
                'orders_data' => $ordersData,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, ['id'], [], Response::HTTP_CREATED);
        $this->assertEquals(RESPONSE_STATUS::OK->value, $responseContent->status);
        $this->assertSame('Orders created', $responseContent->message);
        $this->assertIsArray($responseContent->data->id);
        $this->assertCount(count($ordersData), $responseContent->data->id);
    }

    /** @test */
    public function itShouldCreateOrdersDescriptionAndAmountIsNull(): void
    {
        $ordersData = $this->getOrdersData();
        $ordersData[0]['description'] = null;
        $ordersData[0]['amount'] = null;
        $ordersData[1]['description'] = null;
        $ordersData[1]['amount'] = null;
        $ordersData[2]['description'] = null;
        $ordersData[2]['amount'] = null;
        $client = $this->getNewClientAuthenticatedUser();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => self::GROUP_EXISTS_ID,
                'orders_data' => $ordersData,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, ['id'], [], Response::HTTP_CREATED);
        $this->assertEquals(RESPONSE_STATUS::OK->value, $responseContent->status);
        $this->assertSame('Orders created', $responseContent->message);
        $this->assertIsArray($responseContent->data->id);
        $this->assertCount(count($ordersData), $responseContent->data->id);
    }

    /** @test */
    public function itShouldCreateOrdersShopIdIsNull(): void
    {
        $ordersData = $this->getOrdersData();
        $ordersData[0]['shop_id'] = null;
        $ordersData[1]['shop_id'] = null;
        $ordersData[2]['shop_id'] = null;
        $client = $this->getNewClientAuthenticatedUser();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => self::GROUP_EXISTS_ID,
                'orders_data' => $ordersData,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, ['id'], [], Response::HTTP_CREATED);
        $this->assertEquals(RESPONSE_STATUS::OK->value, $responseContent->status);
        $this->assertSame('Orders created', $responseContent->message);
        $this->assertIsArray($responseContent->data->id);
        $this->assertCount(count($ordersData), $responseContent->data->id);
    }

    /** @test */
    public function itShouldFailCreatingOrdersGroupIdIsNull(): void
    {
        $ordersData = $this->getOrdersData();
        $client = $this->getNewClientAuthenticatedUser();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => null,
                'orders_data' => $ordersData,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, [], ['group_id'], Response::HTTP_BAD_REQUEST);
        $this->assertEquals(RESPONSE_STATUS::ERROR->value, $responseContent->status);
        $this->assertSame('Error', $responseContent->message);
        $this->assertEquals(['not_blank', 'not_null'], $responseContent->errors->group_id);
    }

    /** @test */
    public function itShouldFailCreatingOrdersGroupIdIsWrong(): void
    {
        $ordersData = $this->getOrdersData();
        $client = $this->getNewClientAuthenticatedUser();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => 'wrong id',
                'orders_data' => $ordersData,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, [], ['group_id'], Response::HTTP_BAD_REQUEST);
        $this->assertEquals(RESPONSE_STATUS::ERROR->value, $responseContent->status);
        $this->assertSame('Error', $responseContent->message);
        $this->assertEquals(['uuid_invalid_characters'], $responseContent->errors->group_id);
    }

    /** @test */
    public function itShouldFailCreatingOrdersIsNull(): void
    {
        $client = $this->getNewClientAuthenticatedUser();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => self::GROUP_EXISTS_ID,
                'orders_data' => null,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, [], ['orders_empty'], Response::HTTP_BAD_REQUEST);
        $this->assertEquals(RESPONSE_STATUS::ERROR->value, $responseContent->status);
        $this->assertSame('Error', $responseContent->message);
        $this->assertEquals(['not_blank'], $responseContent->errors->orders_empty);
    }

    /** @test */
    public function itShouldFailCreatingOrdersProductIdIsNull(): void
    {
        $ordersData = $this->getOrdersData();
        $ordersData[0]['product_id'] = null;
        $client = $this->getNewClientAuthenticatedUser();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => self::GROUP_EXISTS_ID,
                'orders_data' => $ordersData,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, [], [0], Response::HTTP_BAD_REQUEST);
        $this->assertEquals(RESPONSE_STATUS::ERROR->value, $responseContent->status);
        $this->assertSame('Error', $responseContent->message);
        $this->assertEquals(['not_blank', 'not_null'], $responseContent->errors[0]->product_id);
    }

    /** @test */
    public function itShouldFailCreatingOrdersProductIdINotFound(): void
    {
        $ordersData = $this->getOrdersData();
        $ordersData[0]['product_id'] = '96ca5611-1b9d-4c21-ba2f-0fe4c8a95387';
        $client = $this->getNewClientAuthenticatedUser();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => self::GROUP_EXISTS_ID,
                'orders_data' => $ordersData,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, [], ['product_not_found'], Response::HTTP_BAD_REQUEST);
        $this->assertEquals(RESPONSE_STATUS::ERROR->value, $responseContent->status);
        $this->assertSame('Product or products not found', $responseContent->message);
        $this->assertEquals('Product or products not found', $responseContent->errors->product_not_found);
    }

    /** @test */
    public function itShouldFailCreatingOrdersNoneProductIdIFound(): void
    {
        $ordersData = $this->getOrdersData();
        $ordersData[0]['product_id'] = '96ca5611-1b9d-4c21-ba2f-0fe4c8a95387';
        $ordersData[1]['product_id'] = '96ca5611-1b9d-4c21-ba2f-0fe4c8a95387';
        $ordersData[2]['product_id'] = '96ca5611-1b9d-4c21-ba2f-0fe4c8a95387';
        $client = $this->getNewClientAuthenticatedUser();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => self::GROUP_EXISTS_ID,
                'orders_data' => $ordersData,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, [], ['product_not_found'], Response::HTTP_BAD_REQUEST);
        $this->assertEquals(RESPONSE_STATUS::ERROR->value, $responseContent->status);
        $this->assertSame('Product or products not found', $responseContent->message);
        $this->assertEquals('Product or products not found', $responseContent->errors->product_not_found);
    }

    /** @test */
    public function itShouldFailCreatingOrdersUserNotBelongsToTheGroup(): void
    {
        $ordersData = $this->getOrdersData();
        $client = $this->getNewClientAuthenticatedAdmin();
        $client->request(
            method: self::METHOD,
            uri: self::ENDPOINT,
            content: json_encode([
                'group_id' => self::GROUP_EXISTS_ID,
                'orders_data' => $ordersData,
            ])
        );

        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertResponseStructureIsOk($response, [], ['group_error'], Response::HTTP_BAD_REQUEST);
        $this->assertEquals(RESPONSE_STATUS::ERROR->value, $responseContent->status);
        $this->assertSame('You not belong to the group', $responseContent->message);
        $this->assertEquals('You not belongs to the group', $responseContent->errors->group_error);
    }
}
