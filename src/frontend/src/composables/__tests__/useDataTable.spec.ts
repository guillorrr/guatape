import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { useDataTable } from '@/composables/useDataTable';
import type { PaginatedResponse } from '@/core/models';

type Row = { id: number };

function page(
  rows: Row[],
  current = 1,
  last = 1,
  total = rows.length,
): { data: PaginatedResponse<Row> } {
  return {
    data: {
      data: rows,
      meta: {
        current_page: current,
        last_page: last,
        per_page: 20,
        total,
        from: 1,
        to: rows.length,
      },
    },
  };
}

describe('useDataTable', () => {
  beforeEach(() => localStorage.clear());

  it('sends the list contract the API expects', async () => {
    const fetchFn = vi.fn().mockResolvedValue(page([{ id: 1 }], 2, 5, 81));
    const table = useDataTable<Row>({ fetchFn, defaultSortBy: 'name', defaultSortDir: 'asc' });

    table.setFilter('role', 'admin');
    await vi.waitFor(() => expect(table.total.value).toBe(81));

    expect(fetchFn).toHaveBeenLastCalledWith({
      page: 1,
      per_page: 20,
      role: 'admin',
      sort_by: 'name',
      sort_dir: 'asc',
    });
    expect(table.currentPage.value).toBe(2);
    expect(table.lastPage.value).toBe(5);
  });

  it('search resets to the first page', async () => {
    const fetchFn = vi.fn().mockResolvedValue(page([]));
    const table = useDataTable<Row>({ fetchFn });
    table.currentPage.value = 4;

    table.search.value = 'ana';
    await nextTick();

    expect(fetchFn).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1, search: 'ana' }));
  });

  it('persists and restores its state under persistKey', async () => {
    const fetchFn = vi.fn().mockResolvedValue(page([]));
    const first = useDataTable<Row>({ fetchFn, persistKey: 'users' });
    first.setSort('email', 'desc');
    first.setFilter('role', 'member');
    await nextTick();

    const second = useDataTable<Row>({ fetchFn, persistKey: 'users' });

    expect(second.sortBy.value).toBe('email');
    expect(second.sortDir.value).toBe('desc');
    expect(second.filters.role).toBe('member');
  });
});
