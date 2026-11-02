<?php

declare(strict_types=1);

namespace App\Shared\Constants;

final class MessageConstants
{
    public const MAX_CONTENT_LENGTH = 10000;
    public const MAX_ATTACHMENTS = 10;
    public const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;
    public const SUMMARY_MAX_LENGTH = 100;
    public const SMART_REPLY_MAX_LENGTH = 100;
}

final class FileConstants
{
    public const DEFAULT_MAX_SIZE_MB = 10;
    public const DEFAULT_MAX_SIZE_BYTES = 10 * 1024 * 1024;
    public const VIDEO_MAX_SIZE_MB = 100;
    public const MIN_FILE_SIZE_BYTES = 10;
    public const FILE_URL_EXPIRATION_SECONDS = 3600;
}

final class PaginationConstants
{
    public const DEFAULT_LIMIT = 10;
    public const MAX_LIMIT = 100;
    public const DEFAULT_PAGE = 1;
    public const MAP_DEFAULT_RADIUS_METERS = 1000;
}

final class SubscriptionConstants
{
    public const DEFAULT_MAX_MEMBERS = 100;
    public const DEFAULT_MAX_FILE_SIZE = 100;
    public const DEFAULT_MAX_MESSAGES = 10000;
}

final class RewardsConstants
{
    public const BRONZE_THRESHOLD = 100;
    public const SILVER_THRESHOLD = 1000;
    public const GOLD_THRESHOLD = 10000;
}

final class JwtConstants
{
    public const EXPIRATION_HOURS = 24;
    public const EXPIRATION_SECONDS = 86400;
}

final class VerificationConstants
{
    public const CODE_EXPIRY_MINUTES = 10;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const PHONE_NUMBER_MIN_LENGTH = 10;
}

final class StoryConstants
{
    public const EXPIRY_HOURS = 24;
    public const EXPIRY_SECONDS = 86400;
}

final class CacheConstants
{
    public const PROFILE_TTL_SECONDS = 600;
    public const DASHBOARD_TTL_SECONDS = 600;
    public const COUNT_TTL_SECONDS = 60;
    public const RECENT_MESSAGES_TTL_SECONDS = 600;
}

final class PerformanceConstants
{
    public const SLOW_API_THRESHOLD_MS = 1000;
    public const API_CALL_TIMEOUT_SECONDS = 10;
}

final class TypingConstants
{
    public const TIMEOUT_SECONDS = 10;
}

final class SearchConstants
{
    public const MAX_LIMIT = 100;
    public const DEFAULT_TRENDING_LIMIT = 10;
}
