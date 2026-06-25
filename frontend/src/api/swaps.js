const BASE = ''  // Vite proxy handles routing in dev

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
