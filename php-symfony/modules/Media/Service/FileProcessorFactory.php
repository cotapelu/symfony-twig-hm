<?php

declare(strict_types=1);

namespace App\Media\Service;

class FileProcessorFactory
{
    /** @var FileValidatorInterface[] */
    private array $validators = [];
    /** @var ImageOptimizerInterface[] */
    private array $optimizers = [];
    /** @var ThumbnailGeneratorInterface[] */
    private array $thumbnailGenerators = [];

    public function registerValidator(FileValidatorInterface $validator)
    {
        $this->validators[] = $validator;
    }

    public function registerOptimizer(ImageOptimizerInterface $optimizer)
    {
        $this->optimizers[] = $optimizer;
    }

    public function registerThumbnailGenerator(ThumbnailGeneratorInterface $generator)
    {
        $this->thumbnailGenerators[] = $generator;
    }

    public function getValidator(string $mimeType)
    {
        foreach ($this->validators as $validator) {
            if ($validator->canHandle($mimeType)) {
                return $validator;
            }
        }

        return null;
    }

    public function getOptimizer(string $mimeType)
    {
        foreach ($this->optimizers as $optimizer) {
            if ($optimizer->canHandle($mimeType)) {
                return $optimizer;
            }
        }

        return null;
    }

    public function getThumbnailGenerator(string $mimeType)
    {
        foreach ($this->thumbnailGenerators as $generator) {
            if ($generator->canHandle($mimeType)) {
                return $generator;
            }
        }

        return null;
    }
}
