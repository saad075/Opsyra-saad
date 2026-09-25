<?php

namespace App\Modules\Setup\Support;

use Closure;

/**
 * One line in the Setup wizard.
 *
 * A 'data' step is done when $check says so — it looks at real records, so
 * it's right however the data got there (SLMS360's and Horilla's approach).
 * A 'review' step covers settings Opsyra pre-fills from country defaults;
 * it's done once an admin confirms they've reviewed them.
 *
 * $check returns ['done' => bool, 'detail' => ?string], where detail is a
 * short progress note such as "3 of 5 staff have a salary".
 */
final class SetupStep
{
    public const GROUPS = [
        'company' => 'Company',
        'people' => 'People and access',
        'hr' => 'HR processes',
        'payroll' => 'Payroll',
    ];

    public function __construct(
        public readonly string $key,
        public readonly string $group,
        public readonly int $order,
        public readonly string $title,
        public readonly string $description,
        public readonly string $kind = 'data',
        public readonly bool $required = true,
        public readonly ?Closure $check = null,
    ) {}

    public static function review(string $key, string $group, int $order, string $title, string $description, bool $required = true): self
    {
        return new self($key, $group, $order, $title, $description, 'review', $required);
    }
}
