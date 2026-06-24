import { useState, useEffect } from 'react'
import { BrowserRouter, Routes, Route, Navigate, useNavigate } from 'react-router-dom'
import { getMe, logout } from './api/auth'
import Login from './pages/Login'
import Home from './pages/Home'

function AppRoutes() {
  const [status, setStatus] = useState('loading') // 'loading' | 'authenticated' | 'unauthenticated'
  const [user, setUser] = useState(null)
  const navigate = useNavigate()

  useEffect(() => {
    getMe()
      .then((data) => {
        if (data) {
          setUser(data)
          setStatus('authenticated')
        } else {
          setStatus('unauthenticated')
        }
      })
      .catch(() => setStatus('unauthenticated'))
  }, [])

  const handleLogout = async () => {
    try {
      await logout()
    } finally {
      setUser(null)
      setStatus('unauthenticated')
      navigate('/login')
    }
  }

  if (status === 'loading') {
    return <p>Loading…</p>
  }

  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route
        path="/"
        element={
          status === 'authenticated'
            ? <Home user={user} onLogout={handleLogout} />
            : <Navigate to="/login" />
        }
      />
      <Route path="*" element={<Navigate to="/" />} />
    </Routes>
  )
}

function App() {
  return (
    <BrowserRouter>
      <AppRoutes />
    </BrowserRouter>
  )
}

export default App
