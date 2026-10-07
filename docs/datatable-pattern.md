# DataTable pattern (CRUD lists)

Every CRUD list page uses the `AppCrudTable.vue` shell (molecule) with
`useDataTable`, backed by `ListQuery` on the API. Same toolbar, same behaviour
everywhere. The reference implementation is
`src/frontend/src/pages/settings/UserListPage.vue`.

## What the shell provides

- **Toolbar** (fixed order): `AppSearchBar` (debounced) · `#filters` slot ·
  "Columnas" picker (with reset).
- **PrimeVue DataTable** with server-side sort and declarative columns; wide
  tables scroll inside their own box (the page never scrolls sideways).
- **Paginator** with `[20, 50, 100]` rows per page and "1–20 de 547".
- **Persistence** in `localStorage`: table state (search, page, per page, sort,
  filters) under `persistKey` (useDataTable) and visible columns under
  `${persistKey}__columns` (AppCrudTable).

## Contract

```vue
<script setup lang="ts">
import AppCrudTable, { type CrudColumn } from '@/molecules/AppCrudTable.vue'
import { useDataTable } from '@/composables/useDataTable'

const table = useDataTable<Product>({
  fetchFn: (params) => productService.list(params),   // GET /api/v1/products
  defaultSortBy: 'name',
  persistKey: 'catalog.products',
})

const columns: CrudColumn[] = [
  { key: 'sku', label: 'SKU', sortable: true, width: '120px' },
  { key: 'name', label: 'Nombre', sortable: true, toggleable: false },
  { key: 'price', label: 'Precio', sortable: true, defaultVisible: false, align: 'right' },
]

// A filter is a computed over table.filters; setting it refetches from page 1.
const status = computed({
  get: () => (table.filters.status as string | undefined) ?? null,
  set: (v: string | null) => (v ? table.setFilter('status', v) : table.clearFilter('status')),
})

onMounted(table.fetch)
</script>

<template>
  <AppCrudTable
    v-model:search="table.search.value"
    :columns="columns"
    :data="table.data.value"
    :loading="table.loading.value"
    :current-page="table.currentPage.value"
    :last-page="table.lastPage.value"
    :total="table.total.value"
    :per-page="table.perPage.value"
    :sort-by="table.sortBy.value"
    :sort-dir="table.sortDir.value"
    persist-key="catalog.products"
    @page-change="table.goToPage"
    @per-page-change="table.setPerPage"
    @sort="table.setSort"
  >
    <template #filters>
      <Select v-model="status" :options="statusOptions" placeholder="Estado" show-clear size="small" />
    </template>
    <template #cell-price="{ item }">{{ formatMoney(item.price) }}</template>
    <template #actions="{ item }">
      <Button icon="pi pi-pencil" text rounded aria-label="Editar" @click="openEdit(item)" />
    </template>
  </AppCrudTable>
</template>
```

The matching API side (`ProductController@index`):

```php
return ProductResource::collection(
    ListQuery::for(Product::query(), $request)
        ->search(['sku', 'name'])
        ->sortable(['sku', 'name', 'price'], default: 'name')
        ->filter('status', fn ($q, string $s) => $q->where('status', $s))
        ->paginate()
);
```

Sortable keys on the SPA must be in the API's `sortable()` list, and filter
names must match `filter()` names.

## `CrudColumn`

| field | default | use |
|---|---|---|
| `key` | required | column id; its cell slot is `cell-{key}` |
| `label` | required | header and column-picker label |
| `sortable` | `false` | server-side sort on header click |
| `sortField` | `key` | sort key the API expects, if different |
| `defaultVisible` | `true` | `false` starts hidden, available in the picker |
| `toggleable` | `true` | `false` always renders and is not in the picker (use it for the identifying column) |
| `width` | — | fixed CSS width |
| `align` | `'left'` | cell and header alignment |

## Slots

| slot | scope | use |
|---|---|---|
| `#filters` | — | domain filters between search and the column picker |
| `#cell-{key}` | `{ item, value }` | custom cell; default renders `item[key]` or `—` |
| `#actions` | `{ item }` | trailing actions column (hide it with `:has-actions="false"`) |
| `#expansion` | `{ item }` | row details, with `expandable` |

## Forms and actions

- Create/edit in an `AppModal` with `useForm`: `form.submit(() => service.save(form.data))`
  stores 422 errors; show them with `form.error('field')` under each input.
- Destructive actions go through `useConfirm()` with `defaultFocus: 'reject'`,
  so a stray Enter cancels.
- Gate buttons with `usePermissions().can('resource.manage')`; the API checks
  it again.

## When not to use `AppCrudTable`

Lists that aren't a paginated CRUD (live dashboards, tables inside a form) or
with a handful of rows that need no paging or filters: a plain `<DataTable>`
(or `AppDataTable` + `useClientDataTable` for in-memory data) is enough.
