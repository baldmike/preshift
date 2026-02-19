import api from '@/composables/useApi'
import { usePreshiftStore } from '@/stores/preshift'

export function useAcknowledgments() {
  const store = usePreshiftStore()

  async function acknowledge(type: string, id: number) {
    await api.post('/api/acknowledge', { type, id })
    store.markAcknowledged(type, id)
  }

  function isAcknowledged(type: string, id: number): boolean {
    return store.acknowledgments.some(
      (ack) => ack.type === type && ack.id === id
    )
  }

  return {
    acknowledge,
    isAcknowledged,
  }
}
