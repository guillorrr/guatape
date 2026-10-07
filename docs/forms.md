# Forms

Every form is `useForm` + controls from the kit, wrapped in `AppField`. The
living reference is `/app/dev/form-kit` (development builds), and each control
has a Storybook story under **Forms/**.

## The pattern

```vue
<script setup lang="ts">
const form = useForm({ name: '', manager_id: null as number | null, starts_on: null as string | null })

async function save() {
  try {
    await form.submit(() => projectService.create(form.data))
    toast.success('Proyecto creado')
  } catch (e) {
    // 422s are already in form.errors; anything else is for the user to see.
    if (e instanceof ApiError && !e.isValidation) toast.error(e.message)
  }
}
</script>

<template>
  <form id="project-form" @submit.prevent="save">
    <AppField label="Nombre" for="p-name" :error="form.error('name')" required>
      <InputText id="p-name" v-model="form.data.name" :invalid="form.hasError('name')" />
    </AppField>
    <AppField label="Responsable" for="p-manager" :error="form.error('manager_id')">
      <AppRemoteSelect v-model="form.data.manager_id" input-id="p-manager"
        :fetch-options="userService.options" :invalid="form.hasError('manager_id')" />
    </AppField>
    <AppField label="Inicio" for="p-start" :error="form.error('starts_on')">
      <AppDatePicker v-model="form.data.starts_on" input-id="p-start" />
    </AppField>
  </form>
  <Button label="Guardar" type="submit" form="project-form" :loading="form.processing.value" />
</template>
```

- `form.data` is a deep copy: editing arrays never touches the initial values
  or a store object passed to `reset()`.
- Field names in 422 errors match the payload keys; nested ones use dots
  (`phones.2.number`).

## Controls

| Need | Control | Binds |
|---|---|---|
| Text, number, password, select, multiselect, checkbox | PrimeVue (`InputText`, `InputNumber`, `Password`, `Select`, `MultiSelect`, `Checkbox`) | as PrimeVue |
| Pick a record from the API (search as you type) | `AppRemoteSelect` | id, or id[] with `multiple` |
| Select that depends on another | `AppRemoteSelect` with `:params="{ unit_id: form.data.unit_id }"` | resets when params change |
| Create the record from the select | `AppRemoteSelect creatable @create="openForm"` | parent sets the new id |
| Repeated sub-form (phones, lines) | `AppRepeater` | object[] |
| Date / time / range | `AppDatePicker` / `AppTimePicker` / `AppDateRange` | `"YYYY-MM-DD"` / `"HH:mm"` / `[from, to]` |
| Formatted text | `AppRichText` | HTML string (sanitized by the API) |
| Files picked now, sent with the form | `AppFileInput` | `File[]` |
| Files attached to a saved record | `AppAttachments type="…" :id` | — (talks to the API itself) |

### Remote options

The API side is any index endpoint with `search` (see `ListQuery`); the
service maps it to `{ value, label, description? }`:

```ts
async options(search: string, params: Record<string, unknown> = {}) {
  const { data } = await api.get<PaginatedResponse<Project>>('/projects', { params: { search, per_page: 20, ...params } })
  return data.data.map((p) => ({ value: p.id, label: p.name, description: p.code }))
}
```

On edit forms pass the current record in `initial-options` so its label shows
without a search.

### Rich text

Columns edited with `AppRichText` use the `SanitizedHtml` cast on the model.
That's what makes rendering them with `v-html` safe; never `v-html` a field
without it.

## Language

The UI is Spanish. The API answers in `APP_LOCALE` (`es` by default): field
names come from `lang/es/validation.php` → `attributes`; add new fields there
so messages read "El campo fecha de inicio es obligatorio". PrimeVue's own
texts (calendars, paginator) use `core/constants/primevue-locale-es.ts`.
