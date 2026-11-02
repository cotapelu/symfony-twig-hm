<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\Entity\User;
use App\User\Service\PhoneVerificationService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PhoneController extends BaseController
{
    public function __construct(
        private PhoneVerificationService $phoneVerificationService,
    ) {
    }

    #[Route('/api/phone/send-verification', name: 'api_phone_send_verification', methods: ['POST'])]
    public function sendVerification(Request $request): Response
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'Unauthorized'], 401);
            }

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $phoneNumber = $data['phone_number'] ?? null;

            if (!$phoneNumber) {
                return $this->json(['error' => 'Phone number is required'], 400);
            }

            $result = $this->phoneVerificationService->sendVerificationCode($user->getId(), $phoneNumber);

            return $this->json($result);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to send verification code'], 500);
        }
    }

    #[Route('/api/phone/verify', name: 'api_phone_verify', methods: ['POST'])]
    public function verifyPhone(Request $request): Response
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'Unauthorized'], 401);
            }

            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            $code = $data['code'] ?? null;

            if (!$code) {
                return $this->json(['error' => 'Verification code is required'], 400);
            }

            $result = $this->phoneVerificationService->verifyPhoneNumber($user->getId(), $code);

            return $this->json($result);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to verify phone number'], 500);
        }
    }
}
