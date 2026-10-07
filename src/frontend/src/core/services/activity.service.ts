import { api } from './api.service';
import type { ItemResponse, PaginatedResponse } from '@/core/models';

export type RunStatus = 'running' | 'completed' | 'failed';

export interface JobRun {
  id: number;
  name: string;
  queue: string | null;
  domain: string;
  status: RunStatus;
  summary: string | null;
  started_at: string | null;
  finished_at: string | null;
  duration_ms: number | null;
  exception: string | null;
  /** Only on the detail endpoint. */
  log?: string | null;
  created_at: string;
}

export interface LastRun {
  status: RunStatus;
  finished_at: string | null;
  duration_ms: number | null;
}

/** An entry of routes/console.php, read from the live Schedule. */
export interface ScheduledTask {
  name: string;
  description: string | null;
  expression: string;
  timezone: string;
  next_run: string | null;
  last_run: LastRun | null;
}

export interface ActivityStats {
  window_hours: number;
  completed: number;
  failed: number;
  running: number;
  by_domain: Record<string, { total: number; failed: number }>;
}

/** An artisan command as declared by its $signature (see CommandCatalog). */
export interface CommandArgument {
  name: string;
  description: string;
  required: boolean;
  default: string | null;
}

export interface CommandOption {
  name: string;
  description: string;
  accepts_value: boolean;
  default: string | number | boolean | null;
}

export interface CatalogCommand {
  name: string;
  description: string;
  domain: string;
  arguments: CommandArgument[];
  options: CommandOption[];
  example: string;
  lifecycle: 'recurring' | 'repair' | 'one_shot';
  lifecycle_label: string;
  note: string | null;
  schedule: { expression: string; next_run: string | null; last_run: LastRun | null } | null;
}

export const activityService = {
  list: (params?: Record<string, string | number>) => api.get<PaginatedResponse<JobRun>>('/system/activity', { params }),
  show: (id: number) => api.get<ItemResponse<JobRun>>(`/system/activity/${id}`),
  stats: () => api.get<ItemResponse<ActivityStats>>('/system/activity/stats'),
  schedule: () => api.get<{ data: ScheduledTask[] }>('/system/schedule'),
  commands: () => api.get<{ data: CatalogCommand[] }>('/system/commands'),
};
