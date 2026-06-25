const BASE = ''  // Vite proxy handles routing in dev

export async function getIncomingSwapRequests() {
  const res = await fetch(`${BASE}/swap-requests`, { credentials: 'include' })
  if (!res.ok) throw new Error('Failed to load swap requests')
  const data = await res.json()
  return data.requests
}

export async function approveSwapRequest(id, comment) {
  const res = await fetch(`${BASE}/swap-requests/${id}/approve`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ comment: comment || null }),
  })

  if (!res.ok) {
    let message = 'Could not approve the request. Please try again.'
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
