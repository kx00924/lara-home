<?php

namespace App\Support;

/** Shared options for the admin tables (constants cannot live on a trait and be read from views). */
final class AdminTable
{
    /** @var array<int, int> */
    public const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    public const DEFAULT_PER_PAGE = 20;
}
