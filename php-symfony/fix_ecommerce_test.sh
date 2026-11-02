#!/bin/bash
file="tests/Controller/Ecommerce/EcommerceControllerTest.php"
# Add property orderService
sed -i "/private EcommerceService|MockObject \\\$ecommerceService;/a\    private OrderService|MockObject \\\$orderService;" "$file"
# Add orderService mock creation after ecommerceService line
sed -i "/\\\$this->ecommerceService = \\\$this->createMock(EcommerceService::class);/a\        \\$this->orderService = \\$this->createMock(OrderService::class);" "$file"
# Replace EcommerceController instantiation to include orderService
sed -i 's/return new EcommerceController(\\$this->ecommerceService);/return new EcommerceController(\\$this->ecommerceService, \\$this->orderService);/' "$file"
# Also update setAuthenticatedUser method to token injection
sed -i "/private function setAuthenticatedUser(): void/,/}/c\\    private function setAuthenticatedUser(): void\n    {\n        \\$reflection = new \\ReflectionClass(\\$this->controller);\n        // Set tokenStorage\n        \\$tokenStorageProp = \\$reflection->getProperty('tokenStorage');\n        \\$tokenStorageProp->setAccessible(true);\n        \\$tokenStorage = \\$this->createMock(\\Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface::class);\n        \\$token = \\$this->createMock(\\Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface::class);\n        \\$token->method('getUser')->willReturn(\\$this->currentUser);\n        \\$tokenStorage->method('getToken')->willReturn(\\$token);\n        \\$tokenStorageProp->setValue(\\$this->controller, \\$tokenStorage);\n        // Set authChecker\n        \\$authCheckerProp = \\$reflection->getProperty('authChecker');\n        \\$authCheckerProp->setAccessible(true);\n        \\$authChecker = \\$this->createMock(\\Symfony\\Component\\Security\\Core\\Authorization\\AuthorizationCheckerInterface::class);\n        \\$authChecker->method('isGranted')->willReturn(true);\n        \\$authCheckerProp->setValue(\\$this->controller, \\$authChecker);\n    }" "$file"
echo "Ecommerce test fixed.\n"
