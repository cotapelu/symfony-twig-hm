<?php
$file = __DIR__ . '/../modules/Message/Repository/MessageRepositoryImpl.php';
$content = file_get_contents($file);

// Replace the block
$search = "        // Secure LIKE query: escape wildcard characters in user input
        // This prevents users from crafting queries with % or _ to bypass filters
        // or cause unexpected results
        \$escapedQuery = str_replace(['%', '_'], ['\\\\%', '\\\\_'], \$query);
        \$qb->andWhere('m.content LIKE :query')
           ->setParameter('query', '%' . \$escapedQuery . '%');";

$replace = "        // Full-text search using PostgreSQL tsvector
        \$escapedQuery = str_replace(['%', '_'], ['\\\\%', '\\\\_'], \$query);
        \$qb->andWhere(\"TO_TSVECTOR('english', m.content) @@ PLAINTO_TSQUERY('english', :query)\")
           ->setParameter('query', \$escapedQuery);";

$updated = str_replace($search, $replace, $content);

if ($updated === $content) {
    echo "No replacement made. Check if block matches.\n";
    exit(1);
}

file_put_contents($file, $updated);
echo "Updated searchMessages to use full-text search.\n";
