<?php

class A {
    public function test() {
        $this->incidentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Incident $inc) {
                return $inc->getTitle() === 'Updated Title'
                    && $inc->getDescription() === 'Updated desc'
                    && $inc->getSeverity() === 'high'
                    && $inc->getStatus() === 'closed';
            }));
    }
}
