<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\AdminTable;
use Illuminate\Http\Request;

/**
 * Shared sort / per-page handling for the admin tables: only whitelisted
 * columns are sortable and only a few page sizes are allowed.
 */
trait ListsRecords
{
    /**
     * @param  array<int, string>  $sortable
     * @return array{sort: string, dir: string, perPage: int}
     */
    protected function listOptions(Request $request, array $sortable, string $defaultSort, string $defaultDir = 'asc'): array
    {
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : $defaultSort;
        $dir = in_array($request->query('dir'), ['asc', 'desc'], true) ? $request->query('dir') : ($sort === $defaultSort ? $defaultDir : 'asc');
        $perPage = in_array((int) $request->query('per_page'), AdminTable::PER_PAGE_OPTIONS, true) ? (int) $request->query('per_page') : AdminTable::DEFAULT_PER_PAGE;

        return ['sort' => $sort, 'dir' => $dir, 'perPage' => $perPage];
    }
}
