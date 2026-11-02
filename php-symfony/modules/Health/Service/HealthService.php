<?php

namespace App\Health\Service;

use App\Health\Entity\HealthProfile;

class HealthService
{
    private array $profiles = [];
    private int $nextId = 1;

    public function createProfile(int $userId)
    {
        $profile = new HealthProfile($userId);
        $profile->setId($this->nextId++);
        $this->profiles[$profile->getId()] = $profile;
        return $profile;
    }

    public function getProfile(int $id)
    {
        return $this->profiles[$id] ?? null;
    }

    public function getUserProfile(int $userId)
    {
        foreach ($this->profiles as $profile) {
            if ($profile->getUserId() === $userId) {
                return $profile;
            }
        }
        return null;
    }

    public function updateProfile(int $id, array $data)
    {
        $profile = $this->getProfile($id);
        if (!$profile) return null;

        if (isset($data['height'])) $profile->setHeight((float)$data['height']);
        if (isset($data['weight'])) $profile->setWeight((float)$data['weight']);
        if (isset($data['age'])) $profile->setAge((int)$data['age']);
        if (isset($data['blood_type'])) $profile->setBloodType($data['blood_type']);
        if (isset($data['gender'])) $profile->setGender($data['gender']);

        return $profile;
    }

    public function addAllergy(int $id, string $allergy)
    {
        $profile = $this->getProfile($id);
        if ($profile) {
            $profile->addAllergy($allergy);
        }
        return $profile;
    }

    public function addCondition(int $id, string $condition)
    {
        $profile = $this->getProfile($id);
        if ($profile) {
            $profile->addCondition($condition);
        }
        return $profile;
    }

    public function addMedication(int $id, string $medication)
    {
        $profile = $this->getProfile($id);
        if ($profile) {
            $profile->addMedication($medication);
        }
        return $profile;
    }

    public function calculateBMI(int $id)
    {
        $profile = $this->getProfile($id);
        return $profile ? $profile->getBMI() : null;
    }

    public function getHealthSummary(int $userId)
    {
        $profile = $this->getUserProfile($userId);
        if (!$profile) {
            return ['error' => 'Profile not found'];
        }

        return [
            'profile' => $profile,
            'bmi' => $profile->getBMI(),
            'bmi_category' => $this->getBMICategory($profile->getBMI()),
            'allergies_count' => count($profile->getAllergies()),
            'conditions_count' => count($profile->getConditions()),
            'medications_count' => count($profile->getMedications()),
        ];
    }

    private function getBMICategory(?float $bmi)
    {
        if ($bmi === null) return 'Unknown';
        if ($bmi < 18.5) return 'Underweight';
        if ($bmi < 25) return 'Normal';
        if ($bmi < 30) return 'Overweight';
        return 'Obese';
    }

    public function deleteProfile(int $id)
    {
        if (isset($this->profiles[$id])) {
            unset($this->profiles[$id]);
            return true;
        }
        return false;
    }

    public function profileExists(int $userId): bool
    {
        return $this->getUserProfile($userId) !== null;
    }

    public function createHealthRecord(int $userId, string $type, ?array $value = null, ?array $metadata = null)
    {
        $record = new \App\Health\Entity\HealthRecord($userId, $type, $value, $metadata);
        // Simulate auto-increment ID
        $record->setId(random_int(1, 10000));
        return $record;
    }
}
