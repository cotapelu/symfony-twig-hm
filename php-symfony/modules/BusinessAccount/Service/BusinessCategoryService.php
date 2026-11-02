<?php

declare(strict_types=1);

namespace App\BusinessAccount\Service;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\BusinessCategory;
use App\BusinessAccount\Repository\BusinessCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class BusinessCategoryService
{
    private EntityManagerInterface $em;
    private BusinessCategoryRepository $categoryRepo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        /** @var BusinessCategoryRepository $categoryRepo */
        $categoryRepo = $em->getRepository(BusinessCategory::class);
        $this->categoryRepo = $categoryRepo;
    }

    public function createCategory(string $name, string $slug, ?string $description = null)
    {
        $category = new BusinessCategory();
        $category->setName($name);
        $category->setSlug($slug);
        $category->setDescription($description);
        $category->setIsActive(true);

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    public function getCategoryById(int $id)
    {
        return $this->categoryRepo->find($id);
    }

    public function getAllCategories()
    {
        return $this->categoryRepo->findAll();
    }
}
