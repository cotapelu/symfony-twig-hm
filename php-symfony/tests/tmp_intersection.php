<?php

declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;

interface A {}
class B implements A {}

class C {
    private A&MockObject $var;
}
