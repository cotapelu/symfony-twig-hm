<?php declare(strict_types=1);

namespace Tests\Unit\Shared\Controller;

use App\Shared\Controller\GatewayController;
use App\Shared\Service\InternalServiceClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GatewayControllerTest extends TestCase
{
    private InternalServiceClient&MockObject $clientMock;
    private GatewayController $controller;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(InternalServiceClient::class);
        $this->controller = new GatewayController($this->clientMock);
        // Set a minimal container to satisfy AbstractController requirements
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $this->controller->setContainer($container);
    }

    private function assertJsonResponse(JsonResponse $response, int $status, ?array $expectedData = null): void
    {
        $this->assertSame($status, $response->getStatusCode());
        if ($expectedData !== null) {
            $this->assertSame($expectedData, json_decode($response->getContent(), true));
        }
    }

    // ==================== GET /api/gateway/health ====================

    public function testGetHealthStatus_Success(): void
    {
        $this->clientMock->expects($this->once())->method('getHealthStatus')->willReturn(['status' => 'ok']);
        $response = $this->controller->getHealthStatus();
        $this->assertJsonResponse($response, 200, ['status' => 'ok']);
    }

    public function testGetHealthStatus_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('getHealthStatus')->willThrowException(new \Exception('Down'));
        $response = $this->controller->getHealthStatus();
        $this->assertJsonResponse($response, 503, ['error' => 'Down']);
    }

    // ==================== POST /api/gateway/payments/wallets ====================

    public function testCreateWallet_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->with('/api/payments/wallets', [])->willReturn(['walletId' => 'w1']);
        $response = $this->controller->createWallet();
        $this->assertJsonResponse($response, 201, ['walletId' => 'w1']);
    }

    public function testCreateWallet_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->willThrowException(new \Exception('Service fail'));
        $response = $this->controller->createWallet();
        $this->assertJsonResponse($response, 503, ['error' => 'Service fail']);
    }

    // ==================== GET /api/gateway/payments/wallets/{userId} ====================

    public function testGetWallet_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goGet')->with('/api/payments/wallets/123')->willReturn(['balance' => 100]);
        $response = $this->controller->getWallet('123');
        $this->assertJsonResponse($response, 200, ['balance' => 100]);
    }

    public function testGetWallet_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goGet')->willThrowException(new \Exception('Not found'));
        $response = $this->controller->getWallet('123');
        $this->assertJsonResponse($response, 503, ['error' => 'Not found']);
    }

    // ==================== POST /api/gateway/payments/transactions ====================

    public function testProcessPayment_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->with('/api/payments/transactions', [])->willReturn(['txnId' => 't1']);
        $response = $this->controller->processPayment();
        $this->assertJsonResponse($response, 200, ['txnId' => 't1']);
    }

    public function testProcessPayment_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->willThrowException(new \Exception('Payment fail'));
        $response = $this->controller->processPayment();
        $this->assertJsonResponse($response, 503, ['error' => 'Payment fail']);
    }

    // ==================== POST /api/gateway/services/bookings ====================

    public function testCreateBooking_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->with('/api/services/bookings', [])->willReturn(['bookingId' => 'b1']);
        $response = $this->controller->createBooking();
        $this->assertJsonResponse($response, 201, ['bookingId' => 'b1']);
    }

    public function testCreateBooking_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->willThrowException(new \Exception(' fail'));
        $response = $this->controller->createBooking();
        $this->assertJsonResponse($response, 503, ['error' => ' fail']);
    }

    // ==================== GET /api/gateway/services/nearby ====================

    public function testGetNearbyServices_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goGet')->with('/api/services/nearby')->willReturn(['providers' => []]);
        $response = $this->controller->getNearbyServices();
        $this->assertJsonResponse($response, 200, ['providers' => []]);
    }

    public function testGetNearbyServices_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goGet')->willThrowException(new \Exception('No nearby'));
        $response = $this->controller->getNearbyServices();
        $this->assertJsonResponse($response, 503, ['error' => 'No nearby']);
    }

    // ==================== POST /api/gateway/location/nearby ====================

    public function testUpdateLocation_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->with('/api/location/update', [])->willReturn(['ok' => true]);
        $response = $this->controller->updateLocation();
        $this->assertJsonResponse($response, 200, ['ok' => true]);
    }

    public function testUpdateLocation_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->willThrowException(new \Exception('Loc fail'));
        $response = $this->controller->updateLocation();
        $this->assertJsonResponse($response, 503, ['error' => 'Loc fail']);
    }

    // ==================== GET /api/gateway/ecommerce/products ====================

    public function testGetProducts_Success(): void
    {
        $this->clientMock->expects($this->once())->method('pythonGet')->with('/api/ecommerce/products')->willReturn(['products' => []]);
        $response = $this->controller->getProducts();
        $this->assertJsonResponse($response, 200, ['products' => []]);
    }

    public function testGetProducts_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('pythonGet')->willThrowException(new \Exception('Ecom down'));
        $response = $this->controller->getProducts();
        $this->assertJsonResponse($response, 503, ['error' => 'Ecom down']);
    }

    // ==================== GET /api/gateway/ecommerce/recommendations ====================

    public function testGetProductRecommendations_Success(): void
    {
        $this->clientMock->expects($this->once())->method('pythonGet')->with('/api/ecommerce/recommendations')->willReturn(['items' => []]);
        $response = $this->controller->getProductRecommendations();
        $this->assertJsonResponse($response, 200, ['items' => []]);
    }

    public function testGetProductRecommendations_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('pythonGet')->willThrowException(new \Exception('Rec fail'));
        $response = $this->controller->getProductRecommendations();
        $this->assertJsonResponse($response, 503, ['error' => 'Rec fail']);
    }

    // ==================== GET /api/gateway/search ====================

    public function testSearch_Success(): void
    {
        $this->clientMock->expects($this->once())->method('pythonGet')->with('/api/search')->willReturn(['results' => []]);
        $response = $this->controller->search();
        $this->assertJsonResponse($response, 200, ['results' => []]);
    }

    public function testSearch_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('pythonGet')->willThrowException(new \Exception('Search fail'));
        $response = $this->controller->search();
        $this->assertJsonResponse($response, 503, ['error' => 'Search fail']);
    }

    // ==================== POST /api/gateway/broadcast/send ====================

    public function testSendBroadcast_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->with('/api/broadcast/send', [])->willReturn(['sent' => 5]);
        $response = $this->controller->sendBroadcast();
        $this->assertJsonResponse($response, 200, ['sent' => 5]);
    }

    public function testSendBroadcast_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->willThrowException(new \Exception('Broadcast fail'));
        $response = $this->controller->sendBroadcast();
        $this->assertJsonResponse($response, 503, ['error' => 'Broadcast fail']);
    }

    // ==================== GET /api/gateway/broadcast/status/{id} ====================

    public function testGetBroadcastStatus_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goGet')->with('/api/broadcast/status/abc123')->willReturn(['status' => 'sent']);
        $response = $this->controller->getBroadcastStatus('abc123');
        $this->assertJsonResponse($response, 200, ['status' => 'sent']);
    }

    public function testGetBroadcastStatus_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goGet')->willThrowException(new \Exception('Not found'));
        $response = $this->controller->getBroadcastStatus('abc123');
        $this->assertJsonResponse($response, 503, ['error' => 'Not found']);
    }

    // ==================== POST /api/gateway/videoconference/create ====================

    public function testCreateVideoConference_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->with('/api/videoconference/create', [])->willReturn(['roomId' => 'r1']);
        $response = $this->controller->createVideoConference();
        $this->assertJsonResponse($response, 201, ['roomId' => 'r1']);
    }

    public function testCreateVideoConference_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->willThrowException(new \Exception('Conf fail'));
        $response = $this->controller->createVideoConference();
        $this->assertJsonResponse($response, 503, ['error' => 'Conf fail']);
    }

    // ==================== POST /api/gateway/videoconference/{id}/join ====================

    public function testJoinVideoConference_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->with('/api/videoconference/r1/join', [])->willReturn(['joined' => true]);
        $response = $this->controller->joinVideoConference('r1');
        $this->assertJsonResponse($response, 200, ['joined' => true]);
    }

    public function testJoinVideoConference_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->willThrowException(new \Exception('Join fail'));
        $response = $this->controller->joinVideoConference('r1');
        $this->assertJsonResponse($response, 503, ['error' => 'Join fail']);
    }

    // ==================== POST /api/gateway/integrations/webhooks ====================

    public function testCreateWebhook_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->with('/api/integrations/webhooks', [])->willReturn(['webhookId' => 'wh1']);
        $response = $this->controller->createWebhook();
        $this->assertJsonResponse($response, 201, ['webhookId' => 'wh1']);
    }

    public function testCreateWebhook_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goPost')->willThrowException(new \Exception('Hook fail'));
        $response = $this->controller->createWebhook();
        $this->assertJsonResponse($response, 503, ['error' => 'Hook fail']);
    }

    // ==================== DELETE /api/gateway/integrations/{id}/webhooks/{webhookId} ====================

    public function testDeleteWebhook_Success(): void
    {
        $this->clientMock->expects($this->once())->method('goDelete')->with('/api/integrations/i1/webhooks/wh1')->willReturn(['deleted' => true]);
        $response = $this->controller->deleteWebhook('i1', 'wh1');
        $this->assertJsonResponse($response, 200, ['deleted' => true]);
    }

    public function testDeleteWebhook_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('goDelete')->willThrowException(new \Exception('Del fail'));
        $response = $this->controller->deleteWebhook('i1', 'wh1');
        $this->assertJsonResponse($response, 503, ['error' => 'Del fail']);
    }

    // ==================== POST /api/gateway/bot/automate ====================

    public function testAutomateBot_Success(): void
    {
        $this->clientMock->expects($this->once())->method('pythonPost')->with('/api/bot/automate', [])->willReturn(['automated' => true]);
        $response = $this->controller->automateBot();
        $this->assertJsonResponse($response, 200, ['automated' => true]);
    }

    public function testAutomateBot_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('pythonPost')->willThrowException(new \Exception('Bot fail'));
        $response = $this->controller->automateBot();
        $this->assertJsonResponse($response, 503, ['error' => 'Bot fail']);
    }

    // ==================== POST /api/gateway/analytics/events ====================

    public function testTrackAnalyticsEvent_Success(): void
    {
        $this->clientMock->expects($this->once())->method('pythonPost')->with('/api/analytics/events', [])->willReturn(['tracked' => true]);
        $response = $this->controller->trackAnalyticsEvent();
        $this->assertJsonResponse($response, 200, ['tracked' => true]);
    }

    public function testTrackAnalyticsEvent_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('pythonPost')->willThrowException(new \Exception('Track fail'));
        $response = $this->controller->trackAnalyticsEvent();
        $this->assertJsonResponse($response, 503, ['error' => 'Track fail']);
    }

    // ==================== GET /api/gateway/analytics/reports ====================

    public function testGetAnalyticsReport_Success(): void
    {
        $this->clientMock->expects($this->once())->method('pythonGet')->with('/api/analytics/reports')->willReturn(['report' => []]);
        $response = $this->controller->getAnalyticsReport();
        $this->assertJsonResponse($response, 200, ['report' => []]);
    }

    public function testGetAnalyticsReport_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('pythonGet')->willThrowException(new \Exception('Report fail'));
        $response = $this->controller->getAnalyticsReport();
        $this->assertJsonResponse($response, 503, ['error' => 'Report fail']);
    }

    // ==================== POST /api/gateway/encryption/keys ====================

    public function testGenerateKey_Success(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->with('/api/encryption/keys', [])->willReturn(['keyId' => 'k1']);
        $response = $this->controller->generateKey();
        $this->assertJsonResponse($response, 201, ['keyId' => 'k1']);
    }

    public function testGenerateKey_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->willThrowException(new \Exception('Key fail'));
        $response = $this->controller->generateKey();
        $this->assertJsonResponse($response, 503, ['error' => 'Key fail']);
    }

    // ==================== POST /api/gateway/encryption/encrypt ====================

    public function testEncryptData_Success(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->with('/api/encryption/encrypt', [])->willReturn(['cipher' => 'xxx']);
        $response = $this->controller->encryptData();
        $this->assertJsonResponse($response, 200, ['cipher' => 'xxx']);
    }

    public function testEncryptData_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->willThrowException(new \Exception('Enc fail'));
        $response = $this->controller->encryptData();
        $this->assertJsonResponse($response, 503, ['error' => 'Enc fail']);
    }

    // ==================== POST /api/gateway/encryption/decrypt ====================

    public function testDecryptData_Success(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->with('/api/encryption/decrypt', [])->willReturn(['plain' => 'text']);
        $response = $this->controller->decryptData();
        $this->assertJsonResponse($response, 200, ['plain' => 'text']);
    }

    public function testDecryptData_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->willThrowException(new \Exception('Dec fail'));
        $response = $this->controller->decryptData();
        $this->assertJsonResponse($response, 503, ['error' => 'Dec fail']);
    }

    // ==================== POST /api/gateway/fileshare/upload ====================

    public function testUploadFile_Success(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->with('/api/fileshare/upload', [])->willReturn(['fileId' => 'f1']);
        $response = $this->controller->uploadFile();
        $this->assertJsonResponse($response, 201, ['fileId' => 'f1']);
    }

    public function testUploadFile_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->willThrowException(new \Exception('Upload fail'));
        $response = $this->controller->uploadFile();
        $this->assertJsonResponse($response, 503, ['error' => 'Upload fail']);
    }

    // ==================== GET /api/gateway/fileshare/download/{fileId} ====================

    public function testDownloadFile_Success(): void
    {
        $this->clientMock->expects($this->once())->method('rustGet')->with('/api/fileshare/download/f1')->willReturn(['content' => '...']);
        $response = $this->controller->downloadFile('f1');
        $this->assertJsonResponse($response, 200, ['content' => '...']);
    }

    public function testDownloadFile_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('rustGet')->willThrowException(new \Exception('Download fail'));
        $response = $this->controller->downloadFile('f1');
        $this->assertJsonResponse($response, 503, ['error' => 'Download fail']);
    }

    // ==================== GET /api/gateway/marketplace/listings ====================

    public function testGetListings_Success(): void
    {
        $this->clientMock->expects($this->once())->method('rustGet')->with('/api/marketplace/listings')->willReturn(['listings' => []]);
        $response = $this->controller->getListings();
        $this->assertJsonResponse($response, 200, ['listings' => []]);
    }

    public function testGetListings_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('rustGet')->willThrowException(new \Exception('Market down'));
        $response = $this->controller->getListings();
        $this->assertJsonResponse($response, 503, ['error' => 'Market down']);
    }

    // ==================== POST /api/gateway/marketplace/listings ====================

    public function testCreateListing_Success(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->with('/api/marketplace/listings', [])->willReturn(['listingId' => 'l1']);
        $response = $this->controller->createListing();
        $this->assertJsonResponse($response, 201, ['listingId' => 'l1']);
    }

    public function testCreateListing_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('rustPost')->willThrowException(new \Exception('List fail'));
        $response = $this->controller->createListing();
        $this->assertJsonResponse($response, 503, ['error' => 'List fail']);
    }

    // ==================== POST /api/gateway/voicemail/record ====================

    public function testRecordVoiceMail_Success(): void
    {
        $this->clientMock->expects($this->once())->method('zigPost')->with('/api/voicemail/record', [])->willReturn(['voiceMailId' => 'v1']);
        $response = $this->controller->recordVoiceMail();
        $this->assertJsonResponse($response, 201, ['voiceMailId' => 'v1']);
    }

    public function testRecordVoiceMail_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('zigPost')->willThrowException(new \Exception('VM fail'));
        $response = $this->controller->recordVoiceMail();
        $this->assertJsonResponse($response, 503, ['error' => 'VM fail']);
    }

    // ==================== GET /api/gateway/voicemail/transcribe/{voiceMailId} ====================

    public function testTranscribeVoiceMail_Success(): void
    {
        $this->clientMock->expects($this->once())->method('zigGet')->with('/api/voicemail/transcribe/v1')->willReturn(['text' => 'Hello']);
        $response = $this->controller->transcribeVoiceMail('v1');
        $this->assertJsonResponse($response, 200, ['text' => 'Hello']);
    }

    public function testTranscribeVoiceMail_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('zigGet')->willThrowException(new \Exception('Transcribe fail'));
        $response = $this->controller->transcribeVoiceMail('v1');
        $this->assertJsonResponse($response, 503, ['error' => 'Transcribe fail']);
    }

    // ==================== POST /api/gateway/content/moderate ====================

    public function testModerateContent_Success(): void
    {
        $this->clientMock->expects($this->once())->method('zigPost')->with('/api/content/moderate', [])->willReturn(['moderated' => true]);
        $response = $this->controller->moderateContent();
        $this->assertJsonResponse($response, 200, ['moderated' => true]);
    }

    public function testModerateContent_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('zigPost')->willThrowException(new \Exception('Mod fail'));
        $response = $this->controller->moderateContent();
        $this->assertJsonResponse($response, 503, ['error' => 'Mod fail']);
    }

    // ==================== GET /api/gateway/content/check/{contentId} ====================

    public function testCheckContent_Success(): void
    {
        $this->clientMock->expects($this->once())->method('zigGet')->with('/api/content/check/c1')->willReturn(['status' => 'ok']);
        $response = $this->controller->checkContent('c1');
        $this->assertJsonResponse($response, 200, ['status' => 'ok']);
    }

    public function testCheckContent_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('zigGet')->willThrowException(new \Exception('Check fail'));
        $response = $this->controller->checkContent('c1');
        $this->assertJsonResponse($response, 503, ['error' => 'Check fail']);
    }

    // ==================== POST /api/gateway/events ====================

    public function testCreateEvent_Success(): void
    {
        $this->clientMock->expects($this->once())->method('elixirPost')->with('/api/events', [])->willReturn(['eventId' => 'e1']);
        $response = $this->controller->createEvent();
        $this->assertJsonResponse($response, 201, ['eventId' => 'e1']);
    }

    public function testCreateEvent_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('elixirPost')->willThrowException(new \Exception('Event fail'));
        $response = $this->controller->createEvent();
        $this->assertJsonResponse($response, 503, ['error' => 'Event fail']);
    }

    // ==================== GET /api/gateway/events/{id} ====================

    public function testGetEvent_Success(): void
    {
        $this->clientMock->expects($this->once())->method('elixirGet')->with('/api/events/e1')->willReturn(['event' => []]);
        $response = $this->controller->getEvent('e1');
        $this->assertJsonResponse($response, 200, ['event' => []]);
    }

    public function testGetEvent_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('elixirGet')->willThrowException(new \Exception('Get fail'));
        $response = $this->controller->getEvent('e1');
        $this->assertJsonResponse($response, 503, ['error' => 'Get fail']);
    }

    // ==================== GET /api/gateway/wallet/balance/{userId} ====================

    public function testGetDistributedWalletBalance_Success(): void
    {
        $this->clientMock->expects($this->once())->method('elixirGet')->with('/api/wallet/balance/u1')->willReturn(['balance' => 500]);
        $response = $this->controller->getDistributedWalletBalance('u1');
        $this->assertJsonResponse($response, 200, ['balance' => 500]);
    }

    public function testGetDistributedWalletBalance_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('elixirGet')->willThrowException(new \Exception('Bal fail'));
        $response = $this->controller->getDistributedWalletBalance('u1');
        $this->assertJsonResponse($response, 503, ['error' => 'Bal fail']);
    }

    // ==================== POST /api/gateway/wallet/transactions ====================

    public function testCreateDistributedTransaction_Success(): void
    {
        $this->clientMock->expects($this->once())->method('elixirPost')->with('/api/wallet/transactions', [])->willReturn(['txnId' => 'tx1']);
        $response = $this->controller->createDistributedTransaction();
        $this->assertJsonResponse($response, 200, ['txnId' => 'tx1']);
    }

    public function testCreateDistributedTransaction_Exception(): void
    {
        $this->clientMock->expects($this->once())->method('elixirPost')->willThrowException(new \Exception('Txn fail'));
        $response = $this->controller->createDistributedTransaction();
        $this->assertJsonResponse($response, 503, ['error' => 'Txn fail']);
    }
}
