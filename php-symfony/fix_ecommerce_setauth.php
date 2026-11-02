<?php
$file = "tests/Controller/Ecommerce/EcommerceControllerTest.php";
$code = file_get_contents($file);
// Find and replace setAuthenticatedUser method with correct version
$pattern = '/\n    private function setAuthenticatedUser\(\): void\n    \{(.*?)\n    \}/s'; // multiline

$replacement = <<<'EOD'
    private function setAuthenticatedUser(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        // Set tokenStorage
        $tokenStorageProp = $reflection->getProperty('tokenStorage');
        $tokenStorageProp->setAccessible(true);
        $tokenStorage = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class);
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn($this->currentUser);
        $tokenStorage->method('getToken')->willReturn($token);
        $tokenStorageProp->setValue($this->controller, $tokenStorage);
        // Set authChecker
        $authCheckerProp = $reflection->getProperty('authChecker');
        $authCheckerProp->setAccessible(true);
        $authChecker = $this->createMock(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);
        $authCheckerProp->setValue($this->controller, $authChecker);
    }
EOD;
$newCode = preg_replace($pattern, $replacement, $code);
if ($newCode === null) {
    echo "preg_replace error\n";
} else {
    file_put_contents($file, $newCode);
    echo "setAuthenticatedUser fixed.\n";
}
