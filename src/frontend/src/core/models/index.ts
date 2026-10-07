// Shared TypeScript interfaces for the application.

/** Laravel JsonResource wrapper for a single item. */
export interface ItemResponse<T> {
  data: T;
}

/** Laravel paginated JsonResource collection ({data, links, meta}). */
export interface PaginatedResponse<T> {
  data: T[];
  links?: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}

export interface User {
  id: number;
  name: string;
  email: string;
  /** Saved UI/mail language; null follows the browser. */
  locale: string | null;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  roles: string[];
  /** Effective permissions (direct + via roles). Only on single-user payloads. */
  permissions?: string[];
}

export interface Role {
  id: number;
  name: string;
  permissions: string[];
}
