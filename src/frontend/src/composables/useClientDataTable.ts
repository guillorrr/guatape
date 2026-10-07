import { computed, reactive, ref, watch, type ComputedRef, type Ref } from 'vue';
import { DEFAULT_ROWS_PER_PAGE } from '@/core/constants/pagination';

type SortDir = 'asc' | 'desc';

interface UseClientDataTableOptions<T> {
  /** Reactive source of all rows (the page already loaded everything from a store). */
  source: ComputedRef<T[]> | Ref<T[]>;
  /** Fields to match against `search`. Case-insensitive substring search. */
  searchFields: Array<keyof T | string>;
  /** Custom predicates per filter key. Return true to keep the row. */
  filterPredicates?: Record<string, (row: T, value: string | number | boolean) => boolean>;
  defaultPerPage?: number;
  defaultSortBy?: string | null;
  defaultSortDir?: SortDir;
  persistKey?: string;
}

interface PersistedState {
  search?: string;
  currentPage?: number;
  perPage?: number;
  sortBy?: string | null;
  sortDir?: SortDir;
  filters?: Record<string, string | number | boolean>;
}

function readPersisted(key: string): PersistedState {
  try {
    const raw = localStorage.getItem(key);
    if (!raw) return {};
    const parsed = JSON.parse(raw) as PersistedState;
    return typeof parsed === 'object' && parsed !== null ? parsed : {};
  } catch {
    return {};
  }
}

function readNested(row: unknown, path: string): unknown {
  return path
    .split('.')
    .reduce<unknown>(
      (acc, key) =>
        acc != null && typeof acc === 'object' ? (acc as Record<string, unknown>)[key] : undefined,
      row,
    );
}

/**
 * Same return shape as `useDataTable` but operates entirely client-side over
 * a reactive source array. Pages backed by Pinia stores that fetch all rows
 * at once (categories, materials, taxonomies) plug straight into AppCrudTable
 * with this composable.
 */
export function useClientDataTable<T>(options: UseClientDataTableOptions<T>) {
  const search = ref('');
  const currentPage = ref(1);
  const perPage = ref(options.defaultPerPage ?? DEFAULT_ROWS_PER_PAGE);
  const sortBy = ref<string | null>(options.defaultSortBy ?? null);
  const sortDir = ref<SortDir>(options.defaultSortDir ?? 'desc');
  const filters = reactive<Record<string, string | number | boolean>>({});

  if (options.persistKey) {
    const saved = readPersisted(options.persistKey);
    if (typeof saved.search === 'string') search.value = saved.search;
    if (typeof saved.currentPage === 'number' && saved.currentPage >= 1)
      currentPage.value = saved.currentPage;
    if (typeof saved.perPage === 'number' && saved.perPage >= 1) perPage.value = saved.perPage;
    if (saved.sortBy !== undefined) sortBy.value = saved.sortBy;
    if (saved.sortDir === 'asc' || saved.sortDir === 'desc') sortDir.value = saved.sortDir;
    if (saved.filters && typeof saved.filters === 'object') {
      for (const [k, v] of Object.entries(saved.filters)) filters[k] = v;
    }
  }

  const filtered = computed<T[]>(() => {
    const all = options.source.value;
    const q = search.value.trim().toLowerCase();

    let rows = all;
    if (q) {
      rows = rows.filter((row) =>
        options.searchFields.some((field) => {
          const value = readNested(row, field as string);
          return value != null && String(value).toLowerCase().includes(q);
        }),
      );
    }

    const predicates = options.filterPredicates ?? {};
    for (const [key, value] of Object.entries(filters)) {
      const pred = predicates[key];
      if (pred) rows = rows.filter((row) => pred(row, value));
    }

    if (sortBy.value) {
      const field = sortBy.value;
      const dir = sortDir.value === 'asc' ? 1 : -1;
      rows = [...rows].sort((a, b) => {
        const av = readNested(a, field);
        const bv = readNested(b, field);
        if (av == null && bv == null) return 0;
        if (av == null) return 1;
        if (bv == null) return -1;
        if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
        return String(av).localeCompare(String(bv), 'es', { numeric: true }) * dir;
      });
    }

    return rows;
  });

  const total = computed(() => filtered.value.length);
  const lastPage = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)));

  // Clamp currentPage if filter shrinks the result set below it.
  watch([total, perPage], () => {
    if (currentPage.value > lastPage.value) currentPage.value = lastPage.value;
  });

  const data = computed<T[]>(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filtered.value.slice(start, start + perPage.value);
  });

  const loading = ref(false);

  // No-op: keeps API parity with useDataTable so AppCrudTable callers can swap
  // composables without changing their template.
  async function fetch() {
    /* client-side: data is already loaded by the parent store */
  }

  function goToPage(page: number) {
    currentPage.value = page;
  }

  function setPerPage(n: number) {
    if (n < 1 || n === perPage.value) return;
    perPage.value = n;
    currentPage.value = 1;
  }

  function setSort(field: string | null, dir: SortDir = 'asc') {
    sortBy.value = field;
    sortDir.value = dir;
    currentPage.value = 1;
  }

  function setFilter(key: string, value: string | number | boolean) {
    filters[key] = value;
    currentPage.value = 1;
  }

  function clearFilter(key: string) {
    delete filters[key];
    currentPage.value = 1;
  }

  watch(search, () => {
    currentPage.value = 1;
  });

  if (options.persistKey) {
    const key = options.persistKey;
    watch(
      [search, currentPage, perPage, sortBy, sortDir, filters],
      () => {
        try {
          const snapshot: PersistedState = {
            search: search.value,
            currentPage: currentPage.value,
            perPage: perPage.value,
            sortBy: sortBy.value,
            sortDir: sortDir.value,
            filters: { ...filters },
          };
          localStorage.setItem(key, JSON.stringify(snapshot));
        } catch {
          /* quota exceeded — silently drop */
        }
      },
      { deep: true },
    );
  }

  return {
    data,
    loading,
    search,
    currentPage,
    lastPage,
    total,
    perPage,
    sortBy,
    sortDir,
    filters,
    fetch,
    goToPage,
    setPerPage,
    setSort,
    setFilter,
    clearFilter,
  };
}
