import { useApi } from './useApi'
import { formatMoney } from '../utils/format'

/**
 * Live policy values from the backend, so public copy always matches what the policy engine enforces.
 */
export function useRefundPolicy() {
  const { data, loading, error } = useApi('/refund-policy')

  return {
    loading,
    error,
    windowDays: data?.window_days ?? '…',
    reviewLimit: data ? formatMoney(data.auto_approve_limit) : '…',
  }
}
