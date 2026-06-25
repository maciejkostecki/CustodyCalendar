import { useState } from 'react'
import Calendar from './Calendar'
import PendingRequests from './PendingRequests'

function Home({ user, onLogout }) {
  // Bumped when a swap decision is made, so the calendar refetches the
  // effective schedule (the two are sibling components).
  const [version, setVersion] = useState(0)

  return (
    <div className="home">
      <header className="home-header">
        <div>
          <h1>Custody calendar</h1>
          <p className="home-user">{user.name || user.email}</p>
        </div>
        <button type="button" onClick={onLogout}>
          Log out
        </button>
      </header>
      <PendingRequests onDecision={() => setVersion((v) => v + 1)} />
      <Calendar refreshSignal={version} />
    </div>
  )
}

export default Home
