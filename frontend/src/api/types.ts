export type Role = 'manager' | 'employee';
export type Department = 'checkout' | 'fresh' | 'stock' | 'bakery';

export const DEPARTMENTS: Department[] = ['checkout', 'fresh', 'stock', 'bakery'];

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
  department: Department;
  contract_hours: number;
  team?: { id: number; name: string; is_demo: boolean };
}

export interface Shift {
  id: number;
  user_id: number;
  user_name?: string;
  date: string; // YYYY-MM-DD
  start_time: string; // HH:mm
  end_time: string;
  department: Department;
  note: string | null;
  hours: number;
  swap_status?: 'open' | 'pending' | null;
}

export type WarningType = 'overlap' | 'short_rest' | 'over_contract';

export interface RuleWarning {
  type: WarningType;
  user_id: number;
  shift_ids: number[];
  value: number | null;
}

export interface WeekShifts {
  data: Shift[];
  meta: {
    week_start: string;
    hours: Record<string, number>;
    warnings: RuleWarning[];
  };
}

export interface AvailabilityDay {
  weekday: number; // 1 = Monday
  available: boolean;
  from_time: string | null;
  until_time: string | null;
}

export interface Employee {
  id: number;
  name: string;
  department: Department;
  contract_hours: number;
  planned_hours: number;
  availability: AvailabilityDay[];
}

export type RequestStatus = 'open' | 'pending' | 'approved' | 'declined' | 'cancelled';

export interface SwapRequest {
  id: number;
  status: RequestStatus;
  note: string | null;
  shift: Shift;
  requester: { id: number; name: string };
  taker: { id: number; name: string } | null;
  created_at: string;
}

export interface TimeOff {
  id: number;
  user: { id: number; name: string };
  start_date: string;
  end_date: string;
  days: number;
  reason: string | null;
  status: 'pending' | 'approved' | 'declined';
  created_at: string;
}

export interface Activity {
  id: number;
  type: string;
  actor: string | null;
  data: Record<string, string | number | null>;
  created_at: string;
}

export interface Dashboard {
  week_start: string;
  my_hours: number;
  contract_hours: number;
  next_shift: Shift | null;
  open_swaps: number;
  pending_swaps: number;
  pending_time_off: number;
  warnings: number;
  team_hours: number;
  coverage: { date: string; shifts: number; hours: number }[];
  on_shift_today: Shift[];
  activity: Activity[];
}

export interface AuthResponse {
  token: string;
  user: User;
}

export interface ShiftInput {
  user_id: number;
  date: string;
  start_time: string;
  end_time: string;
  department: Department;
  note?: string | null;
}
