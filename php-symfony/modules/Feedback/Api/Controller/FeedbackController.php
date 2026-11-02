<?php

declare(strict_types=1);

namespace App\Feedback\Api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Feedback\Application\Service\FeedbackService;
use App\Feedback\Application\DTO\SubmitFeedbackRequest;
use App\Shared\Controller\BaseController;
use InvalidArgumentException;
use DateTimeImmutable;

class FeedbackController extends BaseController
{
    public function __construct(private FeedbackService $feedbackService) { parent::__construct(); }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['success'=>false, 'error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("FeedbackController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['success'=>false, 'error'=>'Internal server error'], 500);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context]));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'tags'=>$tags, 'value'=>1]));
    }

    // ==================== Endpoints ====================

    public function submitFeedback(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->submitFeedbackAction($request));
    }

    private function submitFeedbackAction(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        // Ensure required keys exist to avoid ArgumentCountError when unpacking
        foreach (['type','wardCode','title','description','citizenName'] as $key) {
            if (!array_key_exists($key, $data)) $data[$key] = '';
        }
        $dto = new SubmitFeedbackRequest(...$data);
        $feedback = $this->feedbackService->submitFeedback($dto->type, $dto->wardCode, $dto->title, $dto->description, $dto->citizenName, $dto->citizenPhone, $dto->citizenEmail, $dto->department, $dto->serviceType, $dto->rating, $dto->attachments, $dto->isAnonymous);
        return $this->json(['success'=>true, 'data'=>$feedback->toArray()], 201);
    }

    public function getFeedback(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->getFeedbackAction($id));
    }

    private function getFeedbackAction(string $id): JsonResponse
    {
        if (empty($id)) throw new InvalidArgumentException('Feedback ID is required');
        $feedback = $this->feedbackService->getFeedback($id);
        if (!$feedback) return $this->json(['success'=>false, 'error'=>'Feedback not found'], 404);
        return $this->json(['success'=>true, 'data'=>$feedback->toArray()]);
    }

    public function getFeedbackByWard(Request $request, string $wardCode): JsonResponse
    {
        return $this->handle(fn() => $this->getFeedbackByWardAction($request, $wardCode));
    }

    private function getFeedbackByWardAction(Request $request, string $wardCode): JsonResponse
    {
        if (empty($wardCode)) throw new InvalidArgumentException('Ward code is required');
        $status = $request->query->get('status');
        $limit = $request->query->getInt('limit', 50);
        $feedbacks = $this->feedbackService->getFeedbackByWard($wardCode, $status, $limit);
        return $this->json(['success'=>true, 'data'=>array_map(fn($f)=>$f->toArray(), $feedbacks)]);
    }

    public function getMyFeedbacks(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getMyFeedbacksAction($request));
    }

    private function getMyFeedbacksAction(Request $request): JsonResponse
    {
        $phone = $request->query->get('phone');
        if (!$phone) return $this->json(['success'=>false, 'error'=>'Phone number is required'], 400);
        $feedbacks = $this->feedbackService->getFeedbackByPhone($phone);
        return $this->json(['success'=>true, 'data'=>array_map(fn($f)=>$f->toArray(), $feedbacks)]);
    }

    public function assignFeedback(Request $request, string $id): JsonResponse
    {
        return $this->handle(fn() => $this->assignFeedbackAction($request, $id));
    }

    private function assignFeedbackAction(Request $request, string $id): JsonResponse
    {
        if (empty($id)) throw new InvalidArgumentException('Feedback ID is required');
        $data = $this->getJsonData($request);
        $officerId = $data['officerId'] ?? null;
        if (!$officerId) throw new InvalidArgumentException('Officer ID is required');
        $feedback = $this->feedbackService->assignFeedback($id, $officerId);
        if (!$feedback) return $this->json(['success'=>false, 'error'=>'Feedback not found'], 404);
        return $this->json(['success'=>true, 'data'=>$feedback->toArray()]);
    }

    public function resolveFeedback(Request $request, string $id): JsonResponse
    {
        return $this->handle(fn() => $this->resolveFeedbackAction($request, $id));
    }

    private function resolveFeedbackAction(Request $request, string $id): JsonResponse
    {
        if (empty($id)) throw new InvalidArgumentException('Feedback ID is required');
        $data = $this->getJsonData($request);
        $summary = $data['summary'] ?? null;
        $resolvedBy = $data['resolvedBy'] ?? null;
        if (!$summary || !$resolvedBy) throw new InvalidArgumentException('Summary and resolvedBy are required');
        $feedback = $this->feedbackService->resolveFeedback($id, $summary, $resolvedBy);
        if (!$feedback) return $this->json(['success'=>false, 'error'=>'Feedback not found'], 404);
        return $this->json(['success'=>true, 'data'=>$feedback->toArray()]);
    }

    public function closeFeedback(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->closeFeedbackAction($id));
    }

    private function closeFeedbackAction(string $id): JsonResponse
    {
        if (empty($id)) throw new InvalidArgumentException('Feedback ID is required');
        $feedback = $this->feedbackService->closeFeedback($id);
        if (!$feedback) return $this->json(['success'=>false, 'error'=>'Feedback not found'], 404);
        return $this->json(['success'=>true, 'data'=>$feedback->toArray()]);
    }

    public function rateResolution(Request $request, string $id): JsonResponse
    {
        return $this->handle(fn() => $this->rateResolutionAction($request, $id));
    }

    private function rateResolutionAction(Request $request, string $id): JsonResponse
    {
        if (empty($id)) throw new InvalidArgumentException('Feedback ID is required');
        $data = $this->getJsonData($request);
        $satisfaction = $data['satisfaction'] ?? null;
        if ($satisfaction === null || $satisfaction < 1 || $satisfaction > 5) {
            throw new InvalidArgumentException('Satisfaction rating must be between 1 and 5');
        }
        $feedback = $this->feedbackService->rateResolution($id, $satisfaction);
        if (!$feedback) return $this->json(['success'=>false, 'error'=>'Feedback not found'], 404);
        return $this->json(['success'=>true, 'data'=>$feedback->toArray()]);
    }

    public function getStatistics(Request $request, string $wardCode): JsonResponse
    {
        return $this->handle(fn() => $this->getStatisticsAction($request, $wardCode));
    }

    private function getStatisticsAction(Request $request, string $wardCode): JsonResponse
    {
        if (empty($wardCode)) throw new InvalidArgumentException('Ward code is required');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $start = $startDate ? new \DateTimeImmutable($startDate) : null;
        $end = $endDate ? new \DateTimeImmutable($endDate) : null;
        $stats = $this->feedbackService->getStatistics($wardCode, $start, $end);
        return $this->json(['success'=>true, 'data'=>$stats]);
    }
}
