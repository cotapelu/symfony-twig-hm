<?php
$file = "modules/Poll/Entity/Poll.php";
$code = file_get_contents($file);
$code = str_replace(
    "public function getCreatedBy(): string { return \$this->createdBy; }",
    "public function getCreatedBy(): int { return \$this->createdBy; }",
    $code
);
$code = str_replace(
    "public function setCreatedBy(string \$createdBy): self { \$this->createdBy = \$createdBy; return \$this; }",
    "public function setCreatedBy(int \$createdBy): self { \$this->createdBy = \$createdBy; return \$this; }",
    $code
);
// Insert getCreatorId after getCreatedAt
$code = preg_replace('/(public function getCreatedAt\(\): \\\\DateTimeImmutable { return \\$this->createdAt; })/', "$1\n\n    public function getCreatorId(): int { return \$this->createdBy; }", $code);
file_put_contents($file, $code);
echo "Done.\n";
