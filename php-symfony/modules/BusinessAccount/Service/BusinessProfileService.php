<?php

declare(strict_types=1);

namespace App\BusinessAccount\Service;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\BusinessCategory;
use App\BusinessAccount\Repository\BusinessRepository;
use App\BusinessAccount\Repository\BusinessCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BusinessProfileService
{
    private EntityManagerInterface $em;
    private BusinessCategoryRepository $categoryRepo;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->em = $em;
        /** @var BusinessCategoryRepository $categoryRepo */
        $categoryRepo = $em->getRepository(BusinessCategory::class);
        $this->categoryRepo = $categoryRepo;
        $this->passwordHasher = $passwordHasher;
    }

    public function createBusiness(array $data)
    {
        $business = $this->createBusinessEntity($data);
        $this->applyCategoryIfPresent($business, $data);
        $this->applyPasswordIfPresent($business, $data);
        $business->setStatus('pending');
        $business->setIsVerified(false);
        $this->em->persist($business);
        $this->em->flush();
        return $business;
    }

    private function createBusinessEntity(array $data): Business
    {
        $business = new Business();
        $business->setName($data['name']);
        $business->setEmail($data['email']);
        $business->setPhone($data['phone'] ?? null);
        $business->setDescription($data['description'] ?? null);
        $business->setAddress($data['address'] ?? null);
        $business->setLatitude($data['latitude'] ?? null);
        $business->setLongitude($data['longitude'] ?? null);
        $business->setWebsite($data['website'] ?? null);
        $business->setInstagram($data['instagram'] ?? null);
        $business->setFacebook($data['facebook'] ?? null);
        return $business;
    }

    private function applyCategoryIfPresent(Business $business, array $data): void
    {
        if (isset($data['category'])) {
            if ($data['category'] instanceof BusinessCategory) {
                $business->setCategory($data['category']->getName());
            } else {
                $category = $this->categoryRepo->find($data['category']);
                if ($category) {
                    $business->setCategory($category->getName());
                }
            }
        }
    }

    private function applyPasswordIfPresent(Business $business, array $data): void
    {
        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($business, $data['password']);
            $business->setPassword($hashedPassword);
        }
    }

    public function updateBusiness(Business $business, array $data)
    {
        $this->applyNameIfSet($business, $data);
        $this->applyDescriptionIfSet($business, $data);
        $this->applyPhoneIfSet($business, $data);
        $this->applyAddressIfSet($business, $data);
        $this->applyLatitudeIfSet($business, $data);
        $this->applyLongitudeIfSet($business, $data);
        $this->applyWebsiteIfSet($business, $data);
        $this->applyInstagramIfSet($business, $data);
        $this->applyFacebookIfSet($business, $data);
        $this->applyLogoIfSet($business, $data);
        $this->applyCoverImageIfSet($business, $data);
        $this->applyCategoryIfPresent($business, $data);
        $this->em->flush();
        return $business;
    }

    private function applyNameIfSet(Business $business, array $data): void
    {
        if (isset($data['name'])) {
            $business->setName($data['name']);
        }
    }

    private function applyDescriptionIfSet(Business $business, array $data): void
    {
        if (isset($data['description'])) {
            $business->setDescription($data['description']);
        }
    }

    private function applyPhoneIfSet(Business $business, array $data): void
    {
        if (isset($data['phone'])) {
            $business->setPhone($data['phone']);
        }
    }

    private function applyAddressIfSet(Business $business, array $data): void
    {
        if (isset($data['address'])) {
            $business->setAddress($data['address']);
        }
    }

    private function applyLatitudeIfSet(Business $business, array $data): void
    {
        if (isset($data['latitude'])) {
            $business->setLatitude($data['latitude']);
        }
    }

    private function applyLongitudeIfSet(Business $business, array $data): void
    {
        if (isset($data['longitude'])) {
            $business->setLongitude($data['longitude']);
        }
    }

    private function applyWebsiteIfSet(Business $business, array $data): void
    {
        if (isset($data['website'])) {
            $business->setWebsite($data['website']);
        }
    }

    private function applyInstagramIfSet(Business $business, array $data): void
    {
        if (isset($data['instagram'])) {
            $business->setInstagram($data['instagram']);
        }
    }

    private function applyFacebookIfSet(Business $business, array $data): void
    {
        if (isset($data['facebook'])) {
            $business->setFacebook($data['facebook']);
        }
    }

    private function applyLogoIfSet(Business $business, array $data): void
    {
        if (isset($data['logo'])) {
            $business->setLogo($data['logo']);
        }
    }

    private function applyCoverImageIfSet(Business $business, array $data): void
    {
        if (isset($data['coverImage'])) {
            $business->setCoverImage($data['coverImage']);
        }
    }
}
