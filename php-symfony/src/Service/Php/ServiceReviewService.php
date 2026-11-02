<?php

declare(strict_types=1);

namespace App\Service\Php;

use App\Entity\Php\ServiceReview;
use App\Repository\Php\ServiceReviewRepository;
use Doctrine\ORM\EntityManagerInterface;

class ServiceReviewService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ServiceReviewRepository $repository
    ) {}

    public function createServiceReview(string $businessId, string $customerId, int $rating, ?string $bookingId = null, ?string $comment = null, ?array $metadata = null)
    {
        if (empty(trim($businessId))) throw new \InvalidArgumentException('Business ID is required');
        if (empty(trim($customerId))) throw new \InvalidArgumentException('Customer ID is required');
        if ($rating < 1 || $rating > 5) throw new \InvalidArgumentException('Rating must be between 1 and 5');
        $id = bin2hex(random_bytes(16));
        $review = new ServiceReview($id, $businessId, $customerId, $rating);
        $review->setBookingId($bookingId)->setComment($comment)->setMetadata($metadata);
        $this->em->persist($review); $this->em->flush();
        return $review;
    }

    public function getServiceReview(string $id)
    {
        $review = $this->repository->find($id);
        if (!$review) {
            throw new \RuntimeException('ServiceReview not found');
        }
        return $review;
    }

    public function updateServiceReview(string $id, array $data)
    {
        $review = $this->getServiceReview($id);
        $this->applyServiceReviewFields($review, $data);
        $this->em->flush();
        return $review;
    }

    private function applyServiceReviewFields(ServiceReview $review, array $data): void
    {
        $this->applyBusinessId($review, $data);
        $this->applyCustomerId($review, $data);
        $this->applyRating($review, $data);
        $this->applyBookingId($review, $data);
        $this->applyComment($review, $data);
        $this->applyStatus($review, $data);
        $this->applyMetadata($review, $data);
    }

    private function applyBusinessId(ServiceReview $review, array $data): void
    {
        if (!array_key_exists('businessId', $data)) {
            return;
        }
        $businessId = trim($data['businessId']);
        if ($businessId === '') {
            throw new \InvalidArgumentException('Business ID cannot be empty');
        }
        $review->setBusinessId($businessId);
    }

    private function applyCustomerId(ServiceReview $review, array $data): void
    {
        if (!array_key_exists('customerId', $data)) {
            return;
        }
        $customerId = trim($data['customerId']);
        if ($customerId === '') {
            throw new \InvalidArgumentException('Customer ID cannot be empty');
        }
        $review->setCustomerId($customerId);
    }

    private function applyRating(ServiceReview $review, array $data): void
    {
        if (!array_key_exists('rating', $data)) {
            return;
        }
        $rating = (int)$data['rating'];
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $review->setRating($rating);
    }

    private function applyBookingId(ServiceReview $review, array $data): void
    {
        if (array_key_exists('bookingId', $data)) {
            $review->setBookingId($data['bookingId']);
        }
    }

    private function applyComment(ServiceReview $review, array $data): void
    {
        if (array_key_exists('comment', $data)) {
            $review->setComment($data['comment']);
        }
    }

    private function applyStatus(ServiceReview $review, array $data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }
        $status = trim($data['status']);
        if ($status === '') {
            throw new \InvalidArgumentException('Status cannot be empty');
        }
        $review->setStatus($status);
    }

    private function applyMetadata(ServiceReview $review, array $data): void
    {
        if (array_key_exists('metadata', $data)) {
            $review->setMetadata($data['metadata']);
        }
    }

    public function deleteServiceReview(string $id)
    {
        $review = $this->getServiceReview($id);
        $this->em->remove($review);
        $this->em->flush();
    }

    public function findByBusinessId(string $businessId)
    {
        return $this->repository->findBy(['businessId' => $businessId]);
    }

    public function findByCustomerId(string $customerId)
    {
        return $this->repository->findBy(['customerId' => $customerId]);
    }

    public function findByRating(int $rating)
    {
        return $this->repository->findBy(['rating' => $rating]);
    }

    public function findByStatus(string $status)
    {
        return $this->repository->findBy(['status' => $status]);
    }

    public function getAverageRating(string $businessId)
    {
        $qb = $this->repository->createQueryBuilder('r');
        $qb->select('AVG(r.rating)')
            ->where('r.businessId = :businessId')
            ->setParameter('businessId', $businessId);

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result !== false ? (float)$result : null;
    }
}
