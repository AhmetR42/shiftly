import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from './client';

/** Query keys in one place so invalidation stays consistent. */
export const keys = {
  dashboard: (week?: string) => ['dashboard', week ?? 'current'] as const,
  shifts: (week: string, mine: boolean) => ['shifts', week, mine] as const,
  employees: (week: string) => ['employees', week] as const,
  availability: ['availability'] as const,
  timeOff: ['time-off'] as const,
  swaps: ['swaps'] as const,
  activity: ['activity'] as const,
};

export const useDashboard = () => useQuery({ queryKey: keys.dashboard(), queryFn: () => api.dashboard() });
export const useShifts = (week: string, mine = false) =>
  useQuery({ queryKey: keys.shifts(week, mine), queryFn: () => api.shifts(week, mine), placeholderData: (prev) => prev });
export const useEmployees = (week: string) =>
  useQuery({ queryKey: keys.employees(week), queryFn: () => api.employees(week), placeholderData: (prev) => prev });
export const useAvailability = () => useQuery({ queryKey: keys.availability, queryFn: api.availability });
export const useTimeOff = () => useQuery({ queryKey: keys.timeOff, queryFn: api.timeOff });
export const useSwaps = () => useQuery({ queryKey: keys.swaps, queryFn: api.swaps });

/**
 * Most actions change several screens at once (a swap approval changes the
 * rota, the dashboard and the swap list), so we simply refresh everything
 * that is rota-related after a successful mutation.
 */
export function useRotaMutation<TArgs, TResult>(fn: (args: TArgs) => Promise<TResult>) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: fn,
    onSuccess: () => {
      for (const key of ['shifts', 'employees', 'dashboard', 'swaps', 'time-off', 'availability', 'activity']) {
        queryClient.invalidateQueries({ queryKey: [key] });
      }
    },
  });
}
