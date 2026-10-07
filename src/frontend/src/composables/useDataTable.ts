import { ref, reactive, watch } from 'vue';
import type { PaginatedResponse } from '@/core/models';
import { DEFAULT_ROWS_PER_PAGE } from '@/core/constants/pagination';

type SortDir = 'asc' | 'desc';

interface UseDataTableOptions<T> {
  fetchFn: (params: Record<string, string | number>) => Promise<{ data: PaginatedResponse<T> }>;
  defaultPerPage?: number;
  defaultSortBy?: string | null;
  defaultSortDir?: SortDir;
  // When set, persists {search, currentPage, perPage, sortBy, sortDir, filters}
  // to localStorage under this key. Restored synchronously on setup so the
  // user's list state survives navigation to a detail page and back.
  persistKey?: string;
}

interface PersistedState {
  search?: string;
  currentPage?: number;
  perPage?: number;
  sortBy?: string | null;
  sortDir?: SortDir;
  filters?: Record<string, string | number>;
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

export function useDataTable<T>(options: UseDataTableOptions<T>) {
  const data = ref<T[]>([]) as { value: T[] };
  const loading = ref(false);
  const search = ref('');
  const currentPage = ref(1);
  const lastPage = ref(1);
  const total = ref(0);
  const perPage = ref(options.defaultPerPage ?? DEFAULT_ROWS_PER_PAGE);
  const sortBy = ref<string | null>(options.defaultSortBy ?? null);
  const sortDir = ref<SortDir>(options.defaultSortDir ?? 'desc');
  const filters = reactive<Record<string, string | number>>({});

  // Restore persisted state BEFORE watches are registered so hydration
  // doesn't trigger a fetch on top of the one onMounted fires.
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

  async function fetch() {
    loading.value = true;
    try {
      const params: Record<string, string | number> = {
        page: currentPage.value,
        per_page: perPage.value,
        ...filters,
      };
      if (search.value) params.search = search.value;
      if (sortBy.value) {
        params.sort_by = sortBy.value;
        params.sort_dir = sortDir.value;
      }

      const response = await options.fetchFn(params);
      const paginated = response.data;
      data.value = paginated.data;
      currentPage.value = paginated.meta.current_page;
      lastPage.value = paginated.meta.last_page;
      total.value = paginated.meta.total;
    } catch (error) {
      console.error('Failed to fetch data:', error);
    } finally {
      loading.value = false;
    }
  }

  function goToPage(page: number) {
    currentPage.value = page;
    fetch();
  }

  function setPerPage(n: number) {
    if (n < 1 || n === perPage.value) return;
    perPage.value = n;
    currentPage.value = 1;
    fetch();
  }

  function setSort(field: string | null, dir: SortDir = 'asc') {
    sortBy.value = field;
    sortDir.value = dir;
    currentPage.value = 1;
    fetch();
  }

  function setFilter(key: string, value: string | number) {
    filters[key] = value;
    currentPage.value = 1;
    fetch();
  }

  function clearFilter(key: string) {
    delete filters[key];
    currentPage.value = 1;
    fetch();
  }

  watch(search, () => {
    currentPage.value = 1;
    fetch();
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
          /* quota exceeded or storage disabled — silently drop */
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
