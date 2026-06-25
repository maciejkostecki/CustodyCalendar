const BASE = ''  // Vite proxy handles routing in dev

export async function getIncomingSwapRequests() {
  const res = await fetch(`${BASE}/swap-requests`, { credentials: 'include' })
  if (!res.ok) throw new Error('Failed to load swap requests')
  const data = await res.json()
  return data.requests
}

async function decideSwapRequest(id, action, comment, fallbackMessage) {
  const res = await fetch(`${BASE}/swap-requests/${id}/${action}`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ comment: comment || null }),
  })

  if (!res.ok) {
    let message = fallbackMessage
    try {
      const data = await res.json()
      if (data?.error) message = data.error
    } catch {
      // keep default
    }
    throw new Error(message)
  }

  return res.json()
}

export function approveSwapRequest(id, comment) {
  return decideSwapRequest(id, 'approve', comment, 'Could not approve the request. Please try again.')
}

export function rejectSwapRequest(id, comment) {
  return decideSwapRequest(id, 'reject', comment, 'Could not reject the request. Please try again.')
}

export async function createSwapRequest({ date, comment }) {
  const res = await fetch(`${BASE}/swap-requests`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ date, comment: comment || null }),
  })

  if (!res.ok) {
    let message = 'Could not create the swap request. Please try again.'
    try {
      const data = await res.json()
      if (data?.error) message = data.error
    } catch {
      // non-JSON response; keep the default message
    }
    throw new Error(message)
  }

  return res.json()
}
