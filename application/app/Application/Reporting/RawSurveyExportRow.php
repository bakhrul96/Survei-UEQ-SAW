<?php

namespace App\Application\Reporting;

use LogicException;

final readonly class RawSurveyExportRow
{
    /** @param list<int> $scores */
    public function __construct(
        public int $submissionId,
        public int $profileId,
        public string $unitCode,
        public string $unitName,
        public string $instrumentVersion,
        public string $startedAt,
        public string $completedAt,
        public int $durationSeconds,
        public int $sessionSequence,
        public array $scores,
    ) {}

    public static function fromRecord(object $record): self
    {
        return self::fromArray(get_object_vars($record));
    }

    /** @param array<string, mixed> $values */
    private static function fromArray(array $values): self
    {
        return new self(
            submissionId: self::integer($values, 'submission_id'),
            profileId: self::integer($values, 'profile_id'),
            unitCode: self::string($values, 'unit_code'),
            unitName: self::string($values, 'unit_name'),
            instrumentVersion: self::string($values, 'instrument_version'),
            startedAt: self::string($values, 'started_at'),
            completedAt: self::string($values, 'completed_at'),
            durationSeconds: self::integer($values, 'duration_seconds'),
            sessionSequence: self::integer($values, 'session_sequence'),
            scores: array_map(
                fn (int $order): int => self::integer($values, 'item_'.str_pad((string) $order, 2, '0', STR_PAD_LEFT)),
                range(1, 26),
            ),
        );
    }

    /** @param array<string, mixed> $values */
    private static function integer(array $values, string $key): int
    {
        $value = self::value($values, $key);

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return self::wholeNumber((float) $value, $key);
        }

        if (is_float($value)) {
            return self::wholeNumber($value, $key);
        }

        throw new LogicException("Kolom ekspor [{$key}] harus berupa bilangan bulat.");
    }

    private static function wholeNumber(float $value, string $key): int
    {
        if (
            is_finite($value)
            && floor($value) === $value
            && $value >= PHP_INT_MIN
            && $value <= PHP_INT_MAX
        ) {
            return (int) $value;
        }

        throw new LogicException("Kolom ekspor [{$key}] harus berupa bilangan bulat.");
    }

    /** @param array<string, mixed> $values */
    private static function string(array $values, string $key): string
    {
        $value = self::value($values, $key);

        if (is_string($value)) {
            return $value;
        }

        throw new LogicException("Kolom ekspor [{$key}] harus berupa teks.");
    }

    /** @param array<string, mixed> $values */
    private static function value(array $values, string $key): mixed
    {
        if (! array_key_exists($key, $values)) {
            throw new LogicException("Kolom ekspor [{$key}] tidak tersedia.");
        }

        return $values[$key];
    }
}
