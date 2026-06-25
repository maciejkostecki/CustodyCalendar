import Calendar from './Calendar'
import PendingRequests from './PendingRequests'

function Home({ user, onLogout }) {
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
      <PendingRequests />
      <Calendar />
    </div>
  )
}

export default Home
