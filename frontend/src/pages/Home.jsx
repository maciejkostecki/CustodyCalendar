import Calendar from './Calendar'

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
      <Calendar />
    </div>
  )
}

export default Home
