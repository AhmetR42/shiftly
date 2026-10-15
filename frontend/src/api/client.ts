import type {
  Activity,
  AuthResponse,
  AvailabilityDay,
  Dashboard,
  Employee,
  Role,
  RuleWarning,
  Shift,
  ShiftInput,
  SwapRequest,
  TimeOff,
  User,
  WeekShifts,
} from './types';

const TOKEN_KEY = 'shiftly.token';

export const tokenStore = {
  get: (): string | null => {
    try {
      return localStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  },
  set: (token: string | null) => {
    try {
      if (token) localStorage.setItem(TOKEN_KEY, token);
      else localStorage.removeItem(TOKEN_KEY);
    } catch {
      /* private mode, nothing we can do */
    }
  },
};

/** Error with the Laravel validation messages attached, if there were any. */
export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors: Record<string, string[]> = {},
  ) {
    super(message);
  }

  firstError(): string {
    const first = Object.values(this.errors)[0];
    return first?.[0] ?? this.message;
  }
}

let onUnauthorized: () => void = () => {};
export function setUnauthorizedHandler(handler: () => void) {
  onUnauthorized = handler;
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  const token = tokenStore.get();
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';

  const res = await fetch(`/api/v1${path}`, {
    method,
    headers,
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  if (res.status === 204) return undefined as T;

  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (res.status === 401 && token) onUnauthorized();
    throw new ApiError(res.status, json.message ?? res.statusText, json.errors);
  }
  return json as T;
}

const get = <T>(path: string) => request<T>('GET', path);
const post = <T>(path: string, body?: unknown) => request<T>('POST', path, body ?? {});

type WithWarnings<T> = { data: T; meta: { warnings: RuleWarning[] } };

export const api = {
  login: (email: string, password: string) => post<AuthResponse>('/auth/login', { email, password }),
  startDemo: (role: Role) => post<AuthResponse>('/demo', { role }),
  forgotPassword: (email: string) => post<{ message: string }>('/auth/forgot-password', { email }),
  me: () => get<{ data: User }>('/auth/me').then((r) => r.data),
  logout: () => post<void>('/auth/logout'),

  dashboard: (week?: string) => get<Dashboard>(`/dashboard${week ? `?week=${week}` : ''}`),
  employees: (week: string) => get<{ data: Employee[] }>(`/employees?week=${week}`).then((r) => r.data),
  activity: () => get<{ data: Activity[] }>('/activity').then((r) => r.data),

  shifts: (week: string, mine = false) => get<WeekShifts>(`/shifts?week=${week}${mine ? '&mine=1' : ''}`),
  createShift: (input: ShiftInput) => post<WithWarnings<Shift>>('/shifts', input),
  updateShift: (id: number, input: Partial<ShiftInput>) => request<WithWarnings<Shift>>('PATCH', `/shifts/${id}`, input),
  deleteShift: (id: number) => request<void>('DELETE', `/shifts/${id}`),
  copyWeek: (week: string) => post<{ created: number }>('/shifts/copy-week', { week }),

  availability: () => get<{ data: AvailabilityDay[] }>('/availability').then((r) => r.data),
  saveAvailability: (days: AvailabilityDay[]) =>
    request<{ data: AvailabilityDay[] }>('PUT', '/availability', { days }).then((r) => r.data),

  timeOff: () => get<{ data: TimeOff[] }>('/time-off').then((r) => r.data),
  requestTimeOff: (input: { start_date: string; end_date: string; reason?: string }) =>
    post<{ data: TimeOff }>('/time-off', input),
  reviewTimeOff: (id: number, decision: 'approve' | 'decline') => post<{ data: TimeOff }>(`/time-off/${id}/${decision}`),
  withdrawTimeOff: (id: number) => request<void>('DELETE', `/time-off/${id}`),

  swaps: () => get<{ data: SwapRequest[] }>('/swaps').then((r) => r.data),
  offerShift: (shiftId: number, note?: string) => post<WithWarnings<SwapRequest>>('/swaps', { shift_id: shiftId, note }),
  pickUpSwap: (id: number) => post<WithWarnings<SwapRequest>>(`/swaps/${id}/pick-up`),
  reviewSwap: (id: number, decision: 'approve' | 'decline') => post<WithWarnings<SwapRequest>>(`/swaps/${id}/${decision}`),
  cancelSwap: (id: number) => request<void>('DELETE', `/swaps/${id}`),
};
