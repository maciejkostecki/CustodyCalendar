const BASE = ''  // Vite proxy handles routing in dev

export async function getCalendar() {
  const res = await fetch(`${BASE}/calendar`, { credentials: 'include' })
  if (!res.ok) throw new Error('Failed to load calendar')
  return res.json()
}
