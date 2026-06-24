const BASE = ''  // Vite proxy handles routing in dev

export async function getMe() {
  const res = await fetch(`${BASE}/me`, { credentials: 'include' })
  if (!res.ok) return null
  return res.json()
}

export async function logout() {
  await fetch(`${BASE}/logout`, { method: 'POST', credentials: 'include' })
}

export function loginWithGoogle() {
  window.location.href = '/auth/google/redirect'
}
