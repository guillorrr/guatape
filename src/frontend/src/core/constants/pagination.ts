/**
 * Canonical pagination options for the whole app. Every paginated list — the
 * shared AppCrudTable stack and the raw PrimeVue DataTables — must offer the
 * same rows-per-page choices so the UI feels consistent.
 *
 * Page size is persisted per-list by useDataTable/useClientDataTable (localStorage);
 * raw tables that opt in should persist it the same way.
 */
export const ROWS_PER_PAGE_OPTIONS: number[] = [20, 50, 100]

export const DEFAULT_ROWS_PER_PAGE = 20
