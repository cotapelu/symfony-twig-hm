# PRE-ANALYSIS: News Module

**Generated**: 2026-03-06  
**Module**: `App\Modules\News`  
**Status**: Pre-Analysis for Test Generation

---

## A) EXPORT MAP

### Entities

#### NewsArticle
- **File**: `Entity/NewsArticle.php`
- **Exports**: Class `NewsArticle`
- **Public Methods**:
  - `getId(): ?int`
  - `getAuthorId(): int`
  - `getTitle(): string`
  - `getContent(): string`
  - `getSummary(): string`
  - `getCategory(): string`
  - `getTags(): array`
  - `getStatus(): string`
  - `getFeaturedImage(): ?string`
  - `getViewCount(): int`
  - `getCreatedAt(): \DateTime`
  - `getPublishedAt(): ?\DateTime`
  - `setTitle(string $title): self`
  - `setContent(string $content): self`
  - `setTags(array $tags): self`
  - `setStatus(string $status): self` (auto-sets publishedAt when status = 'published')
  - `setFeaturedImage(?string $image): self`
  - `incrementViewCount(): self`

#### NewsComment
- **File**: `Entity/NewsComment.php`
- **Exports**: Class `NewsComment`
- **Public Methods**:
  - `getId(): ?int`
  - `getArticleId(): int`
  - `getUserId(): int`
  - `getContent(): string`
  - `getParentId(): ?int`
  - `getCreatedAt(): \DateTime`
  - `setContent(string $content): self`
  - `setParentId(?int $id): self`
  - `setId(int $id): self`

### Services

#### NewsService
- **File**: `Service/NewsService.php`
- **Exports**: Class `NewsService`
- **Public Methods**:
  - `createArticle(int $authorId, string $title, string $content, string $summary, string $category): NewsArticle`
  - `getArticle(int $id): ?NewsArticle` (auto-increments viewCount)
  - `getPublishedArticles(?string $category = null): array`
  - `getAuthorArticles(int $authorId): array`
  - `updateArticle(int $id, array $data): ?NewsArticle`
  - `deleteArticle(int $id): bool`
  - `searchArticles(string $query): array` (search in title and content, case-insensitive)
  - `addComment(int $articleId, int $userId, string $content, ?int $parentId = null): NewsComment`
  - `getArticleComments(int $articleId): array`
  - `deleteComment(int $id): bool`

### Controllers

#### NewsController
- **File**: `Controller/NewsController.php`
- **Exports**: Class `NewsController`
- **Public Methods**:
  - `registerRoutes($router): void`
  - `createArticle($request): array`
  - `getArticles($request): array`
  - `searchArticles($request): array`
  - `getArticle($request): array`
  - `updateArticle($request): array`
  - `deleteArticle($request): array`
  - `getComments($request): array`
  - `addComment($request): array`
  - `deleteComment($request): array`

---

## B) BEHAVIOR CONTRACT

### NewsArticle Entity
**Construction**:
- Requires: `authorId`, `title`, `content`, `summary`, `category`
- Initializes: `createdAt`, `updatedAt` to current datetime
- Defaults: `status = 'published'`, `viewCount = 0`, `tags = []`, `featuredImage = null`, `publishedAt = null`, `id = null`

**State Changes**:
- `setTitle()`: updates title, sets `updatedAt` to now
- `setContent()`: updates content, sets `updatedAt` to now
- `setStatus()`: updates status, sets `updatedAt` to now; if status === 'published', sets `publishedAt` to now
- `setTags()`: updates tags array
- `setFeaturedImage()`: updates featured image
- `incrementViewCount()`: increments viewCount by 1

### NewsComment Entity
**Construction**:
- Requires: `articleId`, `userId`, `content`
- Initializes: `createdAt` to now
- Defaults: `parentId = null`, `id = null`

**State Changes**:
- `setContent()`: updates content
- `setParentId()`: sets parent comment for threading
- `setId()`: sets id (used by service)

### NewsService
**Storage**: In-memory arrays (`$articles`, `$comments`) with auto-increment IDs

**createArticle()**:
- Creates new NewsArticle entity
- Assigns unique ID (auto-increment)
- Stores in `$articles`
- Returns created article

**getArticle()**:
- Looks up article by ID
- If found, increments viewCount (side effect)
- Returns article or null

**getPublishedArticles()**:
- Filters `$articles` where status === 'published'
- Optionally filters by category
- Returns array of articles (re-indexed)

**getAuthorArticles()**:
- Filters `$articles` by authorId
- Returns array

**updateArticle()**:
- Retrieves article by ID
- Updates fields present in `$data` (title, content, summary, tags, status, featured_image)
- Uses entity setters
- Returns updated article or null if not found

**deleteArticle()**:
- Removes article from `$articles`
- Does NOT cascade delete comments (potential orphan)
- Returns bool

**searchArticles()**:
- Case-insensitive search in title and content
- Returns matching articles

**addComment()**:
- Creates NewsComment
- Assigns unique comment ID
- Stores in `$comments`
- Returns comment

**getArticleComments()**:
- Filters `$comments` by articleId
- Returns array

**deleteComment()**:
- Removes comment by ID
- Returns bool

### NewsController
**Routing**: All routes prefixed with `/news/*`

**Request Format**:
- Expected `$request` array with keys: `body`, `query`, `params`, `user_id`
- Standard Symfony-style request array

**Responses**:
- Always returns array with `status` (HTTP code) and `data` (or `error`)

---

## C) BRANCH MAP

### NewsService::getArticle(int $id)
- Branch 1: article exists → increment viewCount → return article
- Branch 2: article not found → return null

### NewsService::getPublishedArticles(?string $category)
- Branch 1: no category filter → return all published
- Branch 2: category provided → filter by category
- Branch 3: no published articles → return empty array

### NewsService::updateArticle(int $id, array $data)
- Branch 1: article found → update fields → return article
- Branch 2: article not found → return null

### NewsService::deleteArticle(int $id)
- Branch 1: article exists → delete → return true
- Branch 2: article not exists → return false

### NewsService::searchArticles(string $query)
- Branch 1: query matches title → include
- Branch 2: query matches content → include
- Branch 3: no matches → return empty array
- Branch 4: empty query → return empty array (filter with stripos false)

### NewsController::getArticle($request)
- Branch 1: article exists → return 200 with data
- Branch 2: article not found → return 404 with error

### NewsController::updateArticle($request)
- Branch 1: article exists → update → 200
- Branch 2: article not found → 404

### NewsController::deleteArticle($request)
- Branch 1: article exists → delete → 204
- Branch 2: not found → 404

### NewsController::getComments($request)
- Branch 1: article has comments → 200 with data
- Branch 2: article has none → 200 with empty array

### NewsController::deleteComment($request)
- Branch 1: comment exists → 204
- Branch 2: comment not found → 404

---

## NOTES
- NewsService uses simple in-memory arrays; not production-ready but testable.
- Entity classes have no validation; setters are straightforward.
- Controller constructs its own NewsService (no DI).
- No repository pattern; service owns data.
- No database persistence; integration tests would need to adapt.

---

*Last Updated: 2026-03-06*
